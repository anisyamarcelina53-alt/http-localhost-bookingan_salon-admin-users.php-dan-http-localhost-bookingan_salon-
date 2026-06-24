<?php
require_once '../config/database.php';
include '../templates/admin_header.php';

try {
    // Total Customers
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $total_customers = $stmt->fetchColumn();

    // Total Layanan
    $stmt = $pdo->query("SELECT COUNT(*) FROM layanan");
    $total_layanan = $stmt->fetchColumn();

    // Total Bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM booking");
    $total_bookings = $stmt->fetchColumn();

    // Pending Bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'Menunggu'");
    $pending_bookings = $stmt->fetchColumn();

    // Fetch 5 recent bookings with customer name and service name
    $stmt = $pdo->query("
        SELECT b.*, u.nama AS nama_customer, l.nama_layanan 
        FROM booking b
        JOIN users u ON b.user_id = u.id
        JOIN layanan l ON b.layanan_id = l.id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $recent_bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $total_customers = 0;
    $total_layanan = 0;
    $total_bookings = 0;
    $pending_bookings = 0;
    $recent_bookings = [];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card card-salon p-4 border-0 text-white" style="background: linear-gradient(135deg, var(--dark-color) 0%, #170714 100%);">
            <h2 class="font-outfit mb-1 text-white">Selamat Datang di Panel Admin!</h2>
            <p class="mb-0 text-white-50">Kelola operasional harian, layanan salon, dan pantau pemesanan jadwal customer secara terpusat.</p>
        </div>
    </div>
</div>

<!-- Stats Counter Rows -->
<div class="row g-3 mb-4 font-outfit">
    <!-- Stat 1: Customers -->
    <div class="col-md-3">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-primary-subtle text-primary rounded-circle me-3"><i class="fa-solid fa-users fa-xl"></i></div>
            <div>
                <h5 class="text-muted small mb-1">Customer</h5>
                <h3 class="mb-0 fw-bold"><?= $total_customers; ?></h3>
            </div>
        </div>
    </div>
    <!-- Stat 2: Services -->
    <div class="col-md-3">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-info-subtle text-info rounded-circle me-3"><i class="fa-solid fa-scissors fa-xl"></i></div>
            <div>
                <h5 class="text-muted small mb-1">Layanan</h5>
                <h3 class="mb-0 fw-bold"><?= $total_layanan; ?></h3>
            </div>
        </div>
    </div>
    <!-- Stat 3: Total Bookings -->
    <div class="col-md-3">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-success-subtle text-success rounded-circle me-3"><i class="fa-solid fa-calendar-check fa-xl"></i></div>
            <div>
                <h5 class="text-muted small mb-1">Total Booking</h5>
                <h3 class="mb-0 fw-bold"><?= $total_bookings; ?></h3>
            </div>
        </div>
    </div>
    <!-- Stat 4: Pending Bookings -->
    <div class="col-md-3">
        <div class="card card-salon p-3 d-flex flex-row align-items-center">
            <div class="p-3 bg-warning-subtle text-warning rounded-circle me-3"><i class="fa-solid fa-clock fa-xl"></i></div>
            <div>
                <h5 class="text-muted small mb-1">Menunggu</h5>
                <h3 class="mb-0 fw-bold"><?= $pending_bookings; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Booking Orders -->
    <div class="col-12">
        <div class="card card-salon p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 font-outfit">Pemesanan Terbaru</h4>
                <a href="booking.php" class="btn btn-salon btn-sm font-outfit">Kelola Semua Booking</a>
            </div>

            <?php if (empty($recent_bookings)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-calendar-times fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">Belum ada pemesanan masuk.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="small text-muted font-outfit">
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Layanan</th>
                                <th>Tanggal & Waktu</th>
                                <th>Status</th>
                                <th>Aksi</th>
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
                                    <td class="fw-bold text-muted">#<?= $b['id']; ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($b['nama_customer']); ?></td>
                                    <td><?= htmlspecialchars($b['nama_layanan']); ?></td>
                                    <td>
                                        <div><i class="fa-regular fa-calendar me-1 text-primary"></i><?= date('d-m-Y', strtotime($b['tanggal_booking'])); ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1 text-primary"></i><?= date('H:i', strtotime($b['jam_booking'])); ?> WIB</small>
                                    </td>
                                    <td>
                                        <span class="<?= $status_class; ?>"><?= $b['status']; ?></span>
                                    </td>
                                    <td>
                                        <a href="booking.php" class="btn btn-sm btn-light border px-2 py-1 rounded-pill small">
                                            <i class="fa-solid fa-gear text-primary"></i> Proses
                                        </a>
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

<?php include '../templates/admin_footer.php'; ?>
