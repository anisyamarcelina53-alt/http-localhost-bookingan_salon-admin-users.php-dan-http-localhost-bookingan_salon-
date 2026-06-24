<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control Middleware for Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    $_SESSION['error'] = 'Akses ditolak. Silakan login sebagai customer.';
    header('Location: http://localhost/bookingan_salon/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle proof of payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_bukti'])) {
    $booking_id = intval($_POST['booking_id']);
    
    try {
        // Double check booking belongs to user
        $stmt = $pdo->prepare("
            SELECT b.*, l.harga 
            FROM booking b
            JOIN layanan l ON b.layanan_id = l.id
            WHERE b.id = ? AND b.user_id = ?
        ");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch();
        
        if ($booking && in_array($booking['metode_pembayaran'], ['Transfer Bank', 'QRIS'])) {
            if (isset($_FILES['bukti_file']) && $_FILES['bukti_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['bukti_file']['tmp_name'];
                $file_name = $_FILES['bukti_file']['name'];
                $file_size = $_FILES['bukti_file']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_exts = ['jpg', 'jpeg', 'png'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($file_ext, $allowed_exts)) {
                    $_SESSION['error'] = 'Format file tidak diizinkan. Harap unggah file JPG, JPEG, atau PNG.';
                } elseif ($file_size > $max_size) {
                    $_SESSION['error'] = 'Ukuran file terlalu besar. Maksimal ukuran file adalah 2MB.';
                } else {
                    $upload_dir = '../assets/uploads/bukti_pembayaran/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $new_filename = 'bukti_' . $booking_id . '_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                    $dest_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($file_tmp, $dest_path)) {
                        // Delete old file if exists
                        if (!empty($booking['bukti_pembayaran']) && file_exists($upload_dir . $booking['bukti_pembayaran'])) {
                            @unlink($upload_dir . $booking['bukti_pembayaran']);
                        }
                        
                        // Update booking payment info
                        $update_stmt = $pdo->prepare("
                            UPDATE booking 
                            SET bukti_pembayaran = ?, status_pembayaran = 'Menunggu Verifikasi' 
                            WHERE id = ?
                        ");
                        $update_stmt->execute([$new_filename, $booking_id]);
                        
                        $_SESSION['success'] = 'Bukti pembayaran berhasil diunggah! Menunggu verifikasi admin.';
                    } else {
                        $_SESSION['error'] = 'Gagal menyimpan berkas ke server.';
                    }
                }
            } else {
                $_SESSION['error'] = 'Gagal mengunggah berkas. Pastikan Anda memilih berkas yang valid.';
            }
        } else {
            $_SESSION['error'] = 'Pemesanan tidak ditemukan atau tidak memerlukan bukti pembayaran.';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Terjadi kesalahan database: ' . $e->getMessage();
    }
    
    header('Location: riwayat.php');
    exit;
}

// Handle cancel booking request
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    try {
        // Double check booking belongs to user and is still 'Menunggu'
        $stmt = $pdo->prepare("SELECT id FROM booking WHERE id = ? AND user_id = ? AND status = 'Menunggu'");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            // Update status to 'Dibatalkan'
            $cancel_stmt = $pdo->prepare("UPDATE booking SET status = 'Dibatalkan', status_pembayaran = 'Batal' WHERE id = ?");
            $cancel_stmt->execute([$booking_id]);
            $_SESSION['success'] = 'Booking berhasil dibatalkan.';
        } else {
            $_SESSION['error'] = 'Pemesanan tidak dapat dibatalkan atau sudah diproses oleh admin.';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Gagal membatalkan pemesanan: ' . $e->getMessage();
    }
    
    header('Location: riwayat.php');
    exit;
}

// Session messages check
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Fetch all bookings for logged-in user
try {
    $stmt = $pdo->prepare("
        SELECT b.*, l.nama_layanan, l.harga, l.durasi 
        FROM booking b 
        JOIN layanan l ON b.layanan_id = l.id 
        WHERE b.user_id = ? 
        ORDER BY b.tanggal_booking DESC, b.jam_booking DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
    $error = 'Gagal mengambil data riwayat: ' . $e->getMessage();
}

include '../templates/customer_header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card card-salon p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold font-outfit mb-1">Riwayat Booking Anda</h3>
                    <p class="text-muted small mb-0 font-outfit">Semua pemesanan jadwal perawatan salon Anda</p>
                </div>
                <a href="booking.php" class="btn btn-salon">
                    <i class="fa-solid fa-calendar-plus me-2"></i>Booking Baru
                </a>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success small py-2 px-3 mb-3" role="alert">
                    <i class="fa-solid fa-circle-check me-1"></i><?= $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small py-2 px-3 mb-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i><?= $error; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($bookings)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-calendar-times fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">Belum ada riwayat pemesanan layanan.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-salon align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Layanan</th>
                                <th>Jadwal Kedatangan</th>
                                <th>Biaya</th>
                                <th>Pembayaran</th>
                                <th>Status Booking</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($bookings as $b): ?>
                                <?php
                                $status_class = '';
                                if ($b['status'] === 'Menunggu') $status_class = 'badge bg-secondary-subtle text-secondary';
                                elseif ($b['status'] === 'Diproses') $status_class = 'badge bg-primary-subtle text-primary';
                                elseif ($b['status'] === 'Selesai') $status_class = 'badge bg-success-subtle text-success';
                                elseif ($b['status'] === 'Dibatalkan') $status_class = 'badge bg-danger-subtle text-danger';
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($b['nama_layanan']); ?></div>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= $b['durasi']; ?> Menit</small>
                                    </td>
                                    <td>
                                        <div class="font-outfit text-dark fw-semibold"><i class="fa-regular fa-calendar me-1 text-primary"></i><?= date('d-m-Y', strtotime($b['tanggal_booking'])); ?></div>
                                        <small class="text-muted font-outfit"><i class="fa-regular fa-clock me-1 text-primary"></i><?= date('H:i', strtotime($b['jam_booking'])); ?> WIB</small>
                                    </td>
                                    <td class="fw-semibold text-primary font-outfit">
                                        Rp <?= number_format($b['harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <div class="small font-outfit fw-bold text-dark mb-1"><i class="fa-solid fa-receipt me-1 text-secondary"></i><?= htmlspecialchars($b['metode_pembayaran']); ?></div>
                                        
                                        <?php
                                        $pay_status_class = '';
                                        if ($b['status_pembayaran'] === 'Belum Bayar') $pay_status_class = 'badge bg-danger-subtle text-danger border border-danger-subtle';
                                        elseif ($b['status_pembayaran'] === 'Menunggu Verifikasi') $pay_status_class = 'badge bg-warning-subtle text-warning border border-warning-subtle';
                                        elseif ($b['status_pembayaran'] === 'Lunas') $pay_status_class = 'badge bg-success-subtle text-success border border-success-subtle';
                                        elseif ($b['status_pembayaran'] === 'Batal') $pay_status_class = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle';
                                        ?>
                                        <span class="font-outfit small rounded-pill px-2.5 py-1 <?= $pay_status_class; ?>"><?= $b['status_pembayaran']; ?></span>
                                        
                                        <?php if (in_array($b['metode_pembayaran'], ['Transfer Bank', 'QRIS']) && in_array($b['status_pembayaran'], ['Belum Bayar', 'Batal']) && $b['status'] !== 'Dibatalkan'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-salon py-1 px-3 mt-2 font-outfit rounded-pill small d-block" 
                                                    style="font-size: 0.75rem; box-shadow: none;"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#paymentModal<?= $b['id']; ?>">
                                                <i class="fa-solid fa-credit-card me-1"></i>Bayar Sekarang
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= $status_class; ?>"><?= $b['status']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($b['status'] === 'Menunggu'): ?>
                                            <a href="riwayat.php?action=cancel&id=<?= $b['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger font-outfit px-3 py-1 rounded-pill small" 
                                               onclick="return confirm('Apakah Anda yakin ingin membatalkan booking ini?');">
                                                <i class="fa-solid fa-xmark me-1"></i>Batalkan
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small italic">-</span>
                                        <?php endif; ?>
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

<!-- Render Payment Modals -->
<?php foreach ($bookings as $b): ?>
    <?php if (in_array($b['metode_pembayaran'], ['Transfer Bank', 'QRIS']) && in_array($b['status_pembayaran'], ['Belum Bayar', 'Batal']) && $b['status'] !== 'Dibatalkan'): ?>
        <div class="modal fade" id="paymentModal<?= $b['id']; ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?= $b['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content card-salon border-0">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title font-outfit fw-bold text-dark" id="paymentModalLabel<?= $b['id']; ?>">
                            <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Selesaikan Pembayaran
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="riwayat.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-body font-outfit px-4">
                            <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
                            <input type="hidden" name="upload_bukti" value="1">
                            
                            <div class="alert alert-secondary py-2 px-3 small border-0 rounded-3 mb-3" style="background-color: rgba(214, 51, 132, 0.05); color: var(--dark-color);">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Layanan:</span>
                                    <strong class="text-dark"><?= htmlspecialchars($b['nama_layanan']); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Total Biaya:</span>
                                    <strong class="text-primary fs-6">Rp <?= number_format($b['harga'], 0, ',', '.'); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Metode Pembayaran:</span>
                                    <span class="badge bg-dark rounded-pill px-2.5"><?= htmlspecialchars($b['metode_pembayaran']); ?></span>
                                </div>
                            </div>

                            <?php if ($b['metode_pembayaran'] === 'Transfer Bank'): ?>
                                <div class="p-3 bg-light rounded-3 mb-3 border">
                                    <h6 class="fw-bold mb-2 small text-dark"><i class="fa-solid fa-building-columns text-primary me-1"></i>Instruksi Transfer Bank</h6>
                                    <p class="small text-muted mb-2">Silakan transfer sesuai nominal ke rekening berikut:</p>
                                    <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded border mb-2">
                                        <div>
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">BANK BCA</small>
                                            <strong class="text-dark font-outfit fs-6">123 4567 890</strong>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 text-xs font-outfit" onclick="navigator.clipboard.writeText('1234567890'); alert('Nomor rekening disalin!')">
                                            <i class="fa-solid fa-copy me-1"></i>Salin
                                        </button>
                                    </div>
                                    <div class="small text-muted">Atas Nama: <strong>Glowing Grace Salon</strong></div>
                                </div>
                            <?php elseif ($b['metode_pembayaran'] === 'QRIS'): ?>
                                <div class="text-center p-3 bg-light rounded-3 mb-3 border">
                                    <h6 class="fw-bold mb-2 small text-dark"><i class="fa-solid fa-qrcode text-primary me-1"></i>Scan QRIS</h6>
                                    <p class="small text-muted mb-3">Pindai kode QRIS di bawah ini melalui aplikasi e-wallet Anda (Gopay, OVO, Dana, LinkAja, atau Mobile Banking)</p>
                                    <div class="d-inline-block p-2 bg-white rounded border mb-2">
                                        <img src="../assets/img/qris_mockup.png" alt="QRIS Glowing Grace" class="img-fluid rounded" style="max-height: 250px; object-fit: contain;">
                                    </div>
                                    <div class="small fw-semibold text-primary font-outfit">Glowing Grace Salon</div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="bukti_file<?= $b['id']; ?>" class="form-label small fw-semibold text-dark">Unggah Bukti Pembayaran (Format Gambar)</label>
                                <input type="file" class="form-control" id="bukti_file<?= $b['id']; ?>" name="bukti_file" accept="image/*" required>
                                <div class="form-text text-muted small" style="font-size: 0.75rem;">Format diizinkan: JPG, JPEG, PNG. Maksimal 2MB.</div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0 px-4 pb-4">
                            <button type="button" class="btn btn-salon-outline btn-sm py-2 px-3 rounded-pill" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-salon btn-sm py-2 px-4 rounded-pill">Kirim Bukti</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php include '../templates/customer_footer.php'; ?>
