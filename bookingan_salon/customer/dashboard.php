<?php
require_once '../config/database.php';
include '../templates/customer_header.php';

$user_id = $_SESSION['user_id'];

// Fetch stats counts
try {
    // Total bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_bookings = $stmt->fetchColumn();

    // Active bookings (Menunggu or Diproses)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id = ? AND (status = 'Menunggu' OR status = 'Diproses')");
    $stmt->execute([$user_id]);
    $active_bookings = $stmt->fetchColumn();

    // Completed bookings (Selesai)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id = ? AND status = 'Selesai'");
    $stmt->execute([$user_id]);
    $completed_bookings = $stmt->fetchColumn();

    // Fetch 3 most recent bookings
    $stmt = $pdo->prepare("
        SELECT b.*, l.nama_layanan, l.harga, l.durasi 
        FROM booking b 
        JOIN layanan l ON b.layanan_id = l.id 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $recent_bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $total_bookings = 0;
    $active_bookings = 0;
    $completed_bookings = 0;
    $recent_bookings = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card card-salon p-4 border-0 text-white" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);">
            <h2 class="font-outfit mb-1 text-white">Selamat Datang, <?= htmlspecialchars($_SESSION['nama']); ?>!</h2>
            <p class="mb-0 text-white-50">Senang melihat Anda kembali. Kelola pemesanan perawatan kecantikan Anda dengan praktis di sini.</p>
        </div>
    </div>
</div>

<!-- Stats Counter Rows -->
<div class="row g-3 mb-4 font-outfit">
    <!-- Stat 1 -->
    <div class="col-md-4">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-primary-subtle text-primary rounded-circle me-3"><i class="fa-solid fa-calendar-days fa-xl"></i></div>
            <div>
                <h5 class="text-muted small mb-1">Total Booking</h5>
                <h3 class="mb-0 fw-bold"><?= $total_bookings; ?></h3>
            </div>
        </div>
    </div>
    <!-- Stat 2 -->
    <div class="col-md-4">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-warning-subtle text-warning rounded-circle me-3"><i class="fa-solid fa-spinner fa-xl"></i></div>
            <div>
                <h5 class="text-muted small mb-1">Booking Aktif</h5>
                <h3 class="mb-0 fw-bold"><?= $active_bookings; ?></h3>
            </div>
        </div>
    </div>
    <!-- Stat 3 -->
    <div class="col-md-4">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-success-subtle text-success rounded-circle me-3"><i class="fa-solid fa-circle-check fa-xl"></i></div>
            <div>
                <h5 class="text-muted small mb-1">Booking Selesai</h5>
                <h3 class="mb-0 fw-bold"><?= $completed_bookings; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Action Navigation -->
    <div class="col-lg-4 mb-4">
        <div class="card card-salon p-4">
            <h4 class="mb-3 font-outfit">Menu Cepat</h4>
            <div class="d-grid gap-2">
                <a href="booking.php" class="btn btn-salon text-start py-2.5 px-3">
                    <i class="fa-solid fa-calendar-plus me-2"></i>Booking Layanan Baru
                </a>
                <a href="riwayat.php" class="btn btn-salon-outline text-start py-2.5 px-3">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Booking Saya
                </a>
                <a href="http://localhost/bookingan_salon/index.php" target="_blank" class="btn btn-light text-start py-2.5 px-3 border">
                    <i class="fa-solid fa-gem text-primary me-2"></i>Lihat Katalog Layanan
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Bookings list -->
    <div class="col-lg-8 mb-4">
        <div class="card card-salon p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 font-outfit">Pemesanan Terbaru</h4>
                <a href="riwayat.php" class="text-decoration-none small fw-semibold">Lihat Semua</a>
            </div>
            
            <?php if (empty($recent_bookings)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-calendar-times fa-3x mb-3 text-muted"></i>
                    <p class="mb-0 small">Anda belum pernah melakukan booking layanan.</p>
                    <a href="booking.php" class="btn btn-salon btn-sm mt-3">Mulai Booking</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="small text-muted font-outfit">
                                <th>Layanan</th>
                                <th>Tanggal & Waktu</th>
                                <th>Harga</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $b): ?>
                                <?php
                                $status_class = '';
                                if ($b['status'] === 'Menunggu') $status_class = 'badge-menunggu';
                                elseif ($b['status'] === 'Diproses') $status_class = 'badge-diproses';
                                elseif ($b['status'] === 'Selesai') $status_class = 'badge-selesai';
                                elseif ($b['status'] === 'Dibatalkan') $status_class = 'badge-dibatalkan';
                                ?>
                                <tr class="small">
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($b['nama_layanan']); ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= $b['durasi']; ?> Menit</small>
                                    </td>
                                    <td>
                                        <div><i class="fa-regular fa-calendar me-1 text-primary"></i><?= date('d-m-Y', strtotime($b['tanggal_booking'])); ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1 text-primary"></i><?= date('H:i', strtotime($b['jam_booking'])); ?> WIB</small>
                                    </td>
                                    <td class="fw-semibold">Rp <?= number_format($b['harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="<?= $status_class; ?>"><?= $b['status']; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../templates/customer_footer.php'; ?>
