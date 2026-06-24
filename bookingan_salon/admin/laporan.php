<?php
require_once '../config/database.php';
include '../templates/admin_header.php';

// Filter by month/year if provided
$filter_bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('m'));
$filter_tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));

try {
    // Overall statistics
    $stmt = $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'Menunggu'");
    $count_menunggu = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'Diproses'");
    $count_diproses = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'Selesai'");
    $count_selesai = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'Dibatalkan'");
    $count_dibatalkan = $stmt->fetchColumn();

    $total_all = $count_menunggu + $count_diproses + $count_selesai + $count_dibatalkan;

    // Total revenue from completed bookings
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(l.harga), 0) AS total_pendapatan
        FROM booking b
        JOIN layanan l ON b.layanan_id = l.id
        WHERE b.status = 'Selesai'
    ");
    $total_pendapatan = $stmt->fetchColumn();

    // Monthly revenue
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(l.harga), 0) AS pendapatan_bulan
        FROM booking b
        JOIN layanan l ON b.layanan_id = l.id
        WHERE b.status = 'Selesai' 
        AND MONTH(b.tanggal_booking) = ? 
        AND YEAR(b.tanggal_booking) = ?
    ");
    $stmt->execute([$filter_bulan, $filter_tahun]);
    $pendapatan_bulan = $stmt->fetchColumn();

    // Monthly booking count by status
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) AS menunggu,
            SUM(CASE WHEN status = 'Diproses' THEN 1 ELSE 0 END) AS diproses,
            SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) AS selesai,
            SUM(CASE WHEN status = 'Dibatalkan' THEN 1 ELSE 0 END) AS dibatalkan
        FROM booking
        WHERE MONTH(tanggal_booking) = ? AND YEAR(tanggal_booking) = ?
    ");
    $stmt->execute([$filter_bulan, $filter_tahun]);
    $monthly_stats = $stmt->fetch();

    // Top 5 most booked services (all time)
    $stmt = $pdo->query("
        SELECT l.nama_layanan, l.harga, COUNT(b.id) AS total_booking
        FROM booking b
        JOIN layanan l ON b.layanan_id = l.id
        GROUP BY b.layanan_id
        ORDER BY total_booking DESC
        LIMIT 5
    ");
    $top_layanan = $stmt->fetchAll();

    // Detailed booking list for the selected month
    $stmt = $pdo->prepare("
        SELECT b.*, u.nama AS nama_customer, l.nama_layanan, l.harga
        FROM booking b
        JOIN users u ON b.user_id = u.id
        JOIN layanan l ON b.layanan_id = l.id
        WHERE MONTH(b.tanggal_booking) = ? AND YEAR(b.tanggal_booking) = ?
        ORDER BY b.tanggal_booking DESC, b.jam_booking DESC
    ");
    $stmt->execute([$filter_bulan, $filter_tahun]);
    $monthly_bookings = $stmt->fetchAll();

    // Revenue breakdown by payment method for selected month
    $stmt = $pdo->prepare("
        SELECT 
            b.metode_pembayaran,
            COALESCE(SUM(l.harga), 0) AS total_metode
        FROM booking b
        JOIN layanan l ON b.layanan_id = l.id
        WHERE b.status = 'Selesai'
        AND MONTH(b.tanggal_booking) = ? AND YEAR(b.tanggal_booking) = ?
        GROUP BY b.metode_pembayaran
    ");
    $stmt->execute([$filter_bulan, $filter_tahun]);
    $revenue_by_method = $stmt->fetchAll();

} catch (PDOException $e) {
    $count_menunggu = 0;
    $count_diproses = 0;
    $count_selesai = 0;
    $count_dibatalkan = 0;
    $total_all = 0;
    $total_pendapatan = 0;
    $pendapatan_bulan = 0;
    $monthly_stats = ['total' => 0, 'menunggu' => 0, 'diproses' => 0, 'selesai' => 0, 'dibatalkan' => 0];
    $top_layanan = [];
    $monthly_bookings = [];
    $revenue_by_method = [];
}

$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold font-outfit mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Laporan Booking</h3>
        <p class="text-muted small mb-0 font-outfit">Pantau performa, pendapatan, dan statistik booking salon Anda</p>
    </div>
</div>

<!-- Overall Revenue Cards -->
<div class="row g-3 mb-4 font-outfit">
    <div class="col-md-4">
        <div class="card card-salon p-4 border-0 text-white" style="background: linear-gradient(135deg, #d63384 0%, #6f42c1 100%);">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="small text-white-50 mb-1">Total Pendapatan (Selesai)</p>
                    <h3 class="fw-bold mb-0">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h3>
                </div>
                <i class="fa-solid fa-wallet fa-2x text-white-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-salon p-4 border-0 text-white" style="background: linear-gradient(135deg, #198754 0%, #0d6efd 100%);">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="small text-white-50 mb-1">Pendapatan <?= $nama_bulan[$filter_bulan]; ?> <?= $filter_tahun; ?></p>
                    <h3 class="fw-bold mb-0">Rp <?= number_format($pendapatan_bulan, 0, ',', '.'); ?></h3>
                </div>
                <i class="fa-solid fa-chart-line fa-2x text-white-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-salon p-4 border-0 text-white" style="background: linear-gradient(135deg, #2b1625 0%, #170714 100%);">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="small text-white-50 mb-1">Total Seluruh Booking</p>
                    <h3 class="fw-bold mb-0"><?= $total_all; ?> Booking</h3>
                </div>
                <i class="fa-solid fa-calendar-days fa-2x text-white-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Status Breakdown Cards -->
<div class="row g-3 mb-4 font-outfit">
    <div class="col-md-3">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-warning-subtle text-warning rounded-circle me-3"><i class="fa-solid fa-hourglass-half fa-lg"></i></div>
            <div>
                <h6 class="text-muted small mb-0">Menunggu</h6>
                <h4 class="mb-0 fw-bold"><?= $count_menunggu; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-info-subtle text-info rounded-circle me-3"><i class="fa-solid fa-spinner fa-lg"></i></div>
            <div>
                <h6 class="text-muted small mb-0">Diproses</h6>
                <h4 class="mb-0 fw-bold"><?= $count_diproses; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-success-subtle text-success rounded-circle me-3"><i class="fa-solid fa-circle-check fa-lg"></i></div>
            <div>
                <h6 class="text-muted small mb-0">Selesai</h6>
                <h4 class="mb-0 fw-bold"><?= $count_selesai; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-danger-subtle text-danger rounded-circle me-3"><i class="fa-solid fa-ban fa-lg"></i></div>
            <div>
                <h6 class="text-muted small mb-0">Dibatalkan</h6>
                <h4 class="mb-0 fw-bold"><?= $count_dibatalkan; ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Top Services -->
    <div class="col-lg-5">
        <div class="card card-salon p-4 h-100">
            <h5 class="font-outfit fw-bold mb-3"><i class="fa-solid fa-ranking-star text-primary me-2"></i>Layanan Terpopuler</h5>
            <?php if (empty($top_layanan)): ?>
                <p class="text-muted small text-center py-3">Belum ada data booking.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php $rank = 1; foreach ($top_layanan as $tl): ?>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center border-0 border-bottom">
                            <div class="d-flex align-items-center">
                                <span class="badge rounded-pill me-3 px-2 py-1 fw-bold <?= $rank <= 3 ? 'bg-primary' : 'bg-secondary'; ?>"><?= $rank; ?></span>
                                <div>
                                    <div class="fw-semibold text-dark small"><?= htmlspecialchars($tl['nama_layanan']); ?></div>
                                    <small class="text-muted">Rp <?= number_format($tl['harga'], 0, ',', '.'); ?></small>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 font-outfit"><?= $tl['total_booking']; ?>x</span>
                        </div>
                    <?php $rank++; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Monthly Filter + Summary -->
    <div class="col-lg-7">
        <div class="card card-salon p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="font-outfit fw-bold mb-0"><i class="fa-regular fa-calendar-days text-primary me-2"></i>Laporan Bulanan</h5>
                
                <!-- Month/Year Filter Form -->
                <form action="laporan.php" method="GET" class="d-flex gap-2 align-items-center font-outfit">
                    <select name="bulan" class="form-select form-select-sm" style="width: auto;">
                        <?php foreach ($nama_bulan as $num => $nm): ?>
                            <option value="<?= $num; ?>" <?= $num == $filter_bulan ? 'selected' : ''; ?>><?= $nm; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tahun" class="form-select form-select-sm" style="width: auto;">
                        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                            <option value="<?= $y; ?>" <?= $y == $filter_tahun ? 'selected' : ''; ?>><?= $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-salon btn-sm px-3"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                </form>
            </div>

            <!-- Monthly Summary Stats -->
            <div class="row g-2 mb-3 font-outfit">
                <div class="col-6 col-md-3">
                    <div class="bg-light rounded p-2 text-center">
                        <small class="text-muted d-block">Total</small>
                        <strong class="fs-5"><?= $monthly_stats['total'] ?? 0; ?></strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-warning-subtle rounded p-2 text-center">
                        <small class="text-muted d-block">Menunggu</small>
                        <strong class="fs-5 text-warning"><?= $monthly_stats['menunggu'] ?? 0; ?></strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-success-subtle rounded p-2 text-center">
                        <small class="text-muted d-block">Selesai</small>
                        <strong class="fs-5 text-success"><?= $monthly_stats['selesai'] ?? 0; ?></strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-danger-subtle rounded p-2 text-center">
                        <small class="text-muted d-block">Dibatalkan</small>
                        <strong class="fs-5 text-danger"><?= $monthly_stats['dibatalkan'] ?? 0; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Visual Status Bar -->
            <?php if (($monthly_stats['total'] ?? 0) > 0): ?>
                <div class="mb-3">
                    <small class="text-muted font-outfit d-block mb-1">Distribusi Status:</small>
                    <div class="progress" style="height: 20px; border-radius: 10px;">
                        <?php 
                        $t = $monthly_stats['total'];
                        $pct_selesai = round(($monthly_stats['selesai'] / $t) * 100);
                        $pct_diproses = round(($monthly_stats['diproses'] / $t) * 100);
                        $pct_menunggu = round(($monthly_stats['menunggu'] / $t) * 100);
                        $pct_batal = round(($monthly_stats['dibatalkan'] / $t) * 100);
                        ?>
                        <div class="progress-bar bg-success" style="width: <?= $pct_selesai; ?>%" title="Selesai <?= $pct_selesai; ?>%"><?= $pct_selesai > 5 ? $pct_selesai . '%' : ''; ?></div>
                        <div class="progress-bar bg-info" style="width: <?= $pct_diproses; ?>%" title="Diproses <?= $pct_diproses; ?>%"><?= $pct_diproses > 5 ? $pct_diproses . '%' : ''; ?></div>
                        <div class="progress-bar bg-warning" style="width: <?= $pct_menunggu; ?>%" title="Menunggu <?= $pct_menunggu; ?>%"><?= $pct_menunggu > 5 ? $pct_menunggu . '%' : ''; ?></div>
                        <div class="progress-bar bg-danger" style="width: <?= $pct_batal; ?>%" title="Dibatalkan <?= $pct_batal; ?>%"><?= $pct_batal > 5 ? $pct_batal . '%' : ''; ?></div>
                    </div>
                    <div class="d-flex gap-3 mt-2 small text-muted font-outfit flex-wrap">
                        <span><span class="badge bg-success">&nbsp;</span> Selesai</span>
                        <span><span class="badge bg-info">&nbsp;</span> Diproses</span>
                        <span><span class="badge bg-warning">&nbsp;</span> Menunggu</span>
                        <span><span class="badge bg-danger">&nbsp;</span> Dibatalkan</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Payment Method Revenue Breakdown -->
            <?php if (!empty($revenue_by_method)): ?>
                <div class="mt-4 border-top pt-3">
                    <small class="text-muted font-outfit d-block mb-2">Pendapatan Bulan Ini per Metode Pembayaran (Selesai):</small>
                    <div class="d-flex gap-4 font-outfit flex-wrap">
                        <?php foreach ($revenue_by_method as $rev): ?>
                            <?php
                            $icon = 'fa-solid fa-coins text-success';
                            if ($rev['metode_pembayaran'] === 'Transfer Bank') $icon = 'fa-solid fa-building-columns text-primary';
                            elseif ($rev['metode_pembayaran'] === 'QRIS') $icon = 'fa-solid fa-qrcode text-warning';
                            ?>
                            <div class="d-flex align-items-center bg-white p-2 rounded border shadow-sm">
                                <span class="bg-light p-2 rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="<?= $icon; ?> small"></i></span>
                                <div>
                                    <small class="text-muted d-block" style="font-size: 0.7rem; line-height: 1.1;"><?= htmlspecialchars($rev['metode_pembayaran']); ?></small>
                                    <strong class="text-dark font-outfit small">Rp <?= number_format($rev['total_metode'], 0, ',', '.'); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Monthly Booking Detail Table -->
<div class="card card-salon p-4">
    <h5 class="font-outfit fw-bold mb-3">
        <i class="fa-solid fa-table-list text-primary me-2"></i>Detail Booking — <?= $nama_bulan[$filter_bulan]; ?> <?= $filter_tahun; ?>
    </h5>

    <?php if (empty($monthly_bookings)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-regular fa-folder-open fa-3x mb-3"></i>
            <p class="mb-0">Tidak ada booking untuk bulan <?= $nama_bulan[$filter_bulan]; ?> <?= $filter_tahun; ?>.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-salon align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pelanggan</th>
                        <th>Layanan</th>
                        <th>Jadwal Kedatangan</th>
                        <th>Biaya</th>
                        <th>Pembayaran</th>
                        <th>Status Booking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($monthly_bookings as $mb): ?>
                        <?php
                        $sc = '';
                        if ($mb['status'] === 'Menunggu') $sc = 'badge bg-secondary-subtle text-secondary';
                        elseif ($mb['status'] === 'Diproses') $sc = 'badge bg-primary-subtle text-primary';
                        elseif ($mb['status'] === 'Selesai') $sc = 'badge bg-success-subtle text-success';
                        elseif ($mb['status'] === 'Dibatalkan') $sc = 'badge bg-danger-subtle text-danger';

                        $pay_status_class = '';
                        if ($mb['status_pembayaran'] === 'Belum Bayar') $pay_status_class = 'badge bg-danger-subtle text-danger border border-danger-subtle';
                        elseif ($mb['status_pembayaran'] === 'Menunggu Verifikasi') $pay_status_class = 'badge bg-warning-subtle text-warning border border-warning-subtle';
                        elseif ($mb['status_pembayaran'] === 'Lunas') $pay_status_class = 'badge bg-success-subtle text-success border border-success-subtle';
                        elseif ($mb['status_pembayaran'] === 'Batal') $pay_status_class = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle';
                        ?>
                        <tr class="small">
                            <td><?= $no++; ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($mb['nama_customer']); ?></td>
                            <td><?= htmlspecialchars($mb['nama_layanan']); ?></td>
                            <td>
                                <div class="font-outfit text-dark fw-semibold"><i class="fa-regular fa-calendar me-1 text-primary"></i><?= date('d-m-Y', strtotime($mb['tanggal_booking'])); ?></div>
                                <small class="text-muted font-outfit"><i class="fa-regular fa-clock me-1 text-primary"></i><?= date('H:i', strtotime($mb['jam_booking'])); ?> WIB</small>
                            </td>
                            <td class="fw-semibold text-primary font-outfit">Rp <?= number_format($mb['harga'], 0, ',', '.'); ?></td>
                            <td>
                                <div class="small font-outfit fw-bold text-dark mb-1"><?= htmlspecialchars($mb['metode_pembayaran']); ?></div>
                                <span class="font-outfit small rounded-pill px-2 py-0.5 <?= $pay_status_class; ?>"><?= $mb['status_pembayaran']; ?></span>
                            </td>
                            <td><span class="<?= $sc; ?>"><?= $mb['status']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold bg-light">
                        <td colspan="4" class="text-end font-outfit">Total Pendapatan Bulan Ini (Selesai):</td>
                        <td colspan="3" class="text-primary font-outfit fs-5">Rp <?= number_format($pendapatan_bulan, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../templates/admin_footer.php'; ?>
