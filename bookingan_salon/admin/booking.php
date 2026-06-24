<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control Middleware for Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Akses ditolak. Silakan login sebagai administrator.';
    header('Location: http://localhost/bookingan_salon/login.php');
    exit;
}

$error = '';
$success = '';

// Handle status updates (state-machine)
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];
    $allowed_statuses = ['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'];

    if (in_array($status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE booking SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $_SESSION['success'] = "Status booking #$id berhasil diubah menjadi: <strong>$status</strong>.";
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Gagal memperbarui status booking: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Status tidak valid.';
    }
    header('Location: booking.php');
    exit;
}

// Handle payment status updates
if (isset($_GET['action']) && $_GET['action'] === 'update_payment' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];
    $allowed_pay_statuses = ['Belum Bayar', 'Menunggu Verifikasi', 'Lunas', 'Batal'];

    if (in_array($status, $allowed_pay_statuses)) {
        try {
            // Fetch current booking information
            $stmt = $pdo->prepare("SELECT status, status_pembayaran FROM booking WHERE id = ?");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();

            if ($booking) {
                // If payment marked as Lunas and booking is Menunggu, transition booking status to Diproses automatically!
                if ($status === 'Lunas' && $booking['status'] === 'Menunggu') {
                    $up_stmt = $pdo->prepare("UPDATE booking SET status_pembayaran = ?, status = 'Diproses' WHERE id = ?");
                    $up_stmt->execute([$status, $id]);
                    $_SESSION['success'] = "Pembayaran booking #$id berhasil dikonfirmasi LUNAS, dan status booking diubah menjadi Diproses.";
                } else {
                    $up_stmt = $pdo->prepare("UPDATE booking SET status_pembayaran = ? WHERE id = ?");
                    $up_stmt->execute([$status, $id]);
                    $_SESSION['success'] = "Status pembayaran booking #$id berhasil diubah menjadi: <strong>$status</strong>.";
                }
            } else {
                $_SESSION['error'] = 'Booking tidak ditemukan.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Gagal memperbarui status pembayaran: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Status pembayaran tidak valid.';
    }
    header('Location: booking.php');
    exit;
}

// Session check
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Fetch bookings list
try {
    $stmt = $pdo->query("
        SELECT b.*, u.nama AS nama_customer, u.email AS email_customer, l.nama_layanan, l.harga, l.durasi 
        FROM booking b
        JOIN users u ON b.user_id = u.id
        JOIN layanan l ON b.layanan_id = l.id
        ORDER BY b.tanggal_booking DESC, b.jam_booking DESC
    ");
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
    $error = 'Gagal mengambil data booking: ' . $e->getMessage();
}

include '../templates/admin_header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card card-salon p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold font-outfit mb-1">Kelola Booking Customer</h3>
                    <p class="text-muted small mb-0 font-outfit">Lihat dan ubah status jadwal serta verifikasi pembayaran pelanggan</p>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success small py-2 px-3 mb-3" role="alert">
                    <i class="fa-solid fa-circle-check me-1"></i><?= $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small py-2 px-3 mb-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($bookings)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-calendar-xmark fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">Belum ada pemesanan jadwal salon saat ini.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-salon align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>Layanan</th>
                                <th>Jadwal Booking</th>
                                <th>Biaya</th>
                                <th>Pembayaran</th>
                                <th>Status Booking</th>
                                <th class="text-center">Aksi / Kontrol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <?php
                                $status_class = '';
                                if ($b['status'] === 'Menunggu') $status_class = 'badge bg-secondary-subtle text-secondary';
                                elseif ($b['status'] === 'Diproses') $status_class = 'badge bg-primary-subtle text-primary';
                                elseif ($b['status'] === 'Selesai') $status_class = 'badge bg-success-subtle text-success';
                                elseif ($b['status'] === 'Dibatalkan') $status_class = 'badge bg-danger-subtle text-danger';
                                ?>
                                <tr>
                                    <td class="fw-bold text-muted">#<?= $b['id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($b['nama_customer']); ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($b['email_customer']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($b['nama_layanan']); ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= $b['durasi']; ?> Menit</small>
                                    </td>
                                    <td>
                                        <div class="font-outfit text-dark fw-semibold"><i class="fa-regular fa-calendar me-1 text-primary"></i><?= date('d-m-Y', strtotime($b['tanggal_booking'])); ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1 text-primary"></i><?= date('H:i', strtotime($b['jam_booking'])); ?> WIB</small>
                                    </td>
                                    <td class="fw-semibold text-primary font-outfit">
                                        Rp <?= number_format($b['harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <div class="small font-outfit fw-bold text-dark mb-1"><i class="fa-solid fa-credit-card me-1 text-secondary"></i><?= htmlspecialchars($b['metode_pembayaran']); ?></div>
                                        
                                        <?php
                                        $pay_status_class = '';
                                        if ($b['status_pembayaran'] === 'Belum Bayar') $pay_status_class = 'badge bg-danger-subtle text-danger border border-danger-subtle';
                                        elseif ($b['status_pembayaran'] === 'Menunggu Verifikasi') $pay_status_class = 'badge bg-warning-subtle text-warning border border-warning-subtle';
                                        elseif ($b['status_pembayaran'] === 'Lunas') $pay_status_class = 'badge bg-success-subtle text-success border border-success-subtle';
                                        elseif ($b['status_pembayaran'] === 'Batal') $pay_status_class = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle';
                                        ?>
                                        <span class="font-outfit small rounded-pill px-2.5 py-0.5 <?= $pay_status_class; ?>"><?= $b['status_pembayaran']; ?></span>
                                        
                                        <?php if (!empty($b['bukti_pembayaran'])): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-light border py-1 px-2 mt-2 font-outfit rounded-pill small d-block" 
                                                    style="font-size: 0.75rem; box-shadow: none;"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#receiptModal<?= $b['id']; ?>">
                                                <i class="fa-solid fa-file-image text-primary me-1"></i>Lihat Bukti
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= $status_class; ?>"><?= $b['status']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column gap-1 align-items-center">
                                            <!-- Booking Status Actions -->
                                            <div class="d-flex gap-1 justify-content-center">
                                                <?php if ($b['status'] === 'Menunggu'): ?>
                                                    <a href="booking.php?action=update_status&id=<?= $b['id']; ?>&status=Diproses" 
                                                       class="btn btn-sm btn-outline-primary rounded-pill small px-2.5 py-1">
                                                        <i class="fa-solid fa-play me-1"></i>Proses
                                                    </a>
                                                    <a href="booking.php?action=update_status&id=<?= $b['id']; ?>&status=Dibatalkan" 
                                                       class="btn btn-sm btn-outline-danger rounded-pill small px-2.5 py-1"
                                                       onclick="return confirm('Apakah Anda yakin ingin membatalkan booking ini?');">
                                                        <i class="fa-solid fa-xmark me-1"></i>Batalkan
                                                    </a>
                                                <?php elseif ($b['status'] === 'Diproses'): ?>
                                                    <a href="booking.php?action=update_status&id=<?= $b['id']; ?>&status=Selesai" 
                                                       class="btn btn-sm btn-outline-success rounded-pill small px-2.5 py-1">
                                                        <i class="fa-solid fa-check me-1"></i>Selesai
                                                    </a>
                                                    <a href="booking.php?action=update_status&id=<?= $b['id']; ?>&status=Dibatalkan" 
                                                       class="btn btn-sm btn-outline-danger rounded-pill small px-2.5 py-1"
                                                       onclick="return confirm('Apakah Anda yakin ingin membatalkan booking ini?');">
                                                        <i class="fa-solid fa-xmark me-1"></i>Batalkan
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small italic">-</span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Payment Verification Quick Actions -->
                                            <div class="d-flex gap-1 justify-content-center mt-1">
                                                <?php if ($b['status_pembayaran'] === 'Menunggu Verifikasi'): ?>
                                                    <a href="booking.php?action=update_payment&id=<?= $b['id']; ?>&status=Lunas" 
                                                       class="btn btn-sm btn-success rounded-pill px-2 py-0.5" 
                                                       style="font-size: 0.75rem;" 
                                                       title="Konfirmasi Lunas">
                                                        <i class="fa-solid fa-check me-1"></i>Konfirmasi Lunas
                                                    </a>
                                                    <a href="booking.php?action=update_payment&id=<?= $b['id']; ?>&status=Batal" 
                                                       class="btn btn-sm btn-danger rounded-pill px-2 py-0.5" 
                                                       style="font-size: 0.75rem;" 
                                                       title="Tolak Pembayaran"
                                                       onclick="return confirm('Apakah Anda yakin ingin menolak pembayaran ini?');">
                                                        <i class="fa-solid fa-xmark me-1"></i>Tolak
                                                    </a>
                                                <?php elseif ($b['metode_pembayaran'] === 'Bayar di Tempat' && $b['status_pembayaran'] === 'Belum Bayar' && in_array($b['status'], ['Diproses', 'Selesai'])): ?>
                                                    <a href="booking.php?action=update_payment&id=<?= $b['id']; ?>&status=Lunas" 
                                                       class="btn btn-sm btn-outline-success rounded-pill px-2 py-0.5" 
                                                       style="font-size: 0.75rem;" 
                                                       title="Tandai Lunas">
                                                        <i class="fa-solid fa-coins me-1"></i>Tandai Lunas
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
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

<!-- Render Bukti Pembayaran Modals -->
<?php foreach ($bookings as $b): ?>
    <?php if (!empty($b['bukti_pembayaran'])): ?>
        <div class="modal fade" id="receiptModal<?= $b['id']; ?>" tabindex="-1" aria-labelledby="receiptModalLabel<?= $b['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content card-salon border-0">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title font-outfit fw-bold text-dark" id="receiptModalLabel<?= $b['id']; ?>">
                            <i class="fa-solid fa-receipt text-primary me-2"></i>Bukti Pembayaran #<?= $b['id']; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body font-outfit px-4 text-center">
                        <div class="alert alert-secondary text-start py-2 px-3 small border-0 rounded-3 mb-3" style="background-color: rgba(214, 51, 132, 0.05); color: var(--dark-color);">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Pelanggan:</span>
                                <strong class="text-dark"><?= htmlspecialchars($b['nama_customer']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Layanan / Biaya:</span>
                                <strong class="text-dark"><?= htmlspecialchars($b['nama_layanan']); ?> (Rp <?= number_format($b['harga'], 0, ',', '.'); ?>)</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Metode Pembayaran:</span>
                                <span class="badge bg-dark rounded-pill px-2.5"><?= htmlspecialchars($b['metode_pembayaran']); ?></span>
                            </div>
                        </div>
                        
                        <div class="p-2 bg-white rounded border mb-3">
                            <img src="../assets/uploads/bukti_pembayaran/<?= htmlspecialchars($b['bukti_pembayaran']); ?>" alt="Bukti Pembayaran" class="img-fluid rounded" style="max-height: 400px; object-fit: contain;">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex justify-content-between">
                        <button type="button" class="btn btn-salon-outline btn-sm py-2 px-3 rounded-pill" data-bs-dismiss="modal">Tutup</button>
                        
                        <?php if ($b['status_pembayaran'] === 'Menunggu Verifikasi'): ?>
                            <div class="d-flex gap-2">
                                <a href="booking.php?action=update_payment&id=<?= $b['id']; ?>&status=Batal" 
                                   class="btn btn-sm btn-danger py-2 px-3 rounded-pill"
                                   onclick="return confirm('Apakah Anda yakin ingin menolak pembayaran ini?');">
                                    <i class="fa-solid fa-xmark me-1"></i>Tolak (Batal)
                                </a>
                                <a href="booking.php?action=update_payment&id=<?= $b['id']; ?>&status=Lunas" 
                                   class="btn btn-sm btn-success py-2 px-4 rounded-pill">
                                    <i class="fa-solid fa-check me-1"></i>Konfirmasi Lunas
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php include '../templates/admin_footer.php'; ?>

