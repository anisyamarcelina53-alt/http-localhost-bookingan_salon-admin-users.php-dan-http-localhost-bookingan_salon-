<?php
require_once '../config/database.php';
include '../templates/customer_header.php';

$user_id = $_SESSION['user_id'];
$pre_selected_layanan_id = isset($_GET['layanan_id']) ? intval($_GET['layanan_id']) : 0;

$error = '';
$success = '';

// Fetch all services for the dropdown selection
try {
    $stmt = $pdo->query("SELECT id, nama_layanan, harga, durasi FROM layanan ORDER BY nama_layanan ASC");
    $layanan_options = $stmt->fetchAll();
} catch (PDOException $e) {
    $layanan_options = [];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $layanan_id = intval($_POST['layanan_id']);
    $tanggal_booking = $_POST['tanggal_booking'];
    $jam_booking = $_POST['jam_booking'];
    $metode_pembayaran = isset($_POST['metode_pembayaran']) ? $_POST['metode_pembayaran'] : '';

    // Validations
    $today = date('Y-m-d');
    $allowed_methods = ['Bayar di Tempat', 'Transfer Bank', 'QRIS'];
    
    if (empty($layanan_id) || empty($tanggal_booking) || empty($jam_booking) || empty($metode_pembayaran)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif ($tanggal_booking < $today) {
        $error = 'Tanggal booking tidak boleh hari yang lalu.';
    } elseif (!in_array($metode_pembayaran, $allowed_methods)) {
        $error = 'Metode pembayaran tidak valid.';
    } else {
        try {
            // Check if service exists
            $check_service = $pdo->prepare("SELECT id FROM layanan WHERE id = ?");
            $check_service->execute([$layanan_id]);
            
            if (!$check_service->fetch()) {
                $error = 'Layanan yang dipilih tidak valid.';
            } else {
                // Insert booking request
                $insert_stmt = $pdo->prepare("
                    INSERT INTO booking (user_id, layanan_id, tanggal_booking, jam_booking, metode_pembayaran, status_pembayaran, status) 
                    VALUES (?, ?, ?, ?, ?, 'Belum Bayar', 'Menunggu')
                ");
                $insert_stmt->execute([$user_id, $layanan_id, $tanggal_booking, $jam_booking, $metode_pembayaran]);
                
                $_SESSION['success'] = 'Booking berhasil diajukan! Silakan selesaikan pembayaran di halaman Riwayat Booking jika memilih Transfer Bank atau QRIS.';
                echo "<script>window.location.href='riwayat.php';</script>";
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan booking: ' . $e->getMessage();
        }
    }
}

// Available operation hours for appointment slots
$hours = [
    '09:00' => '09:00 WIB',
    '10:00' => '10:00 WIB',
    '11:00' => '11:00 WIB',
    '12:00' => '12:00 WIB',
    '13:00' => '13:00 WIB',
    '14:00' => '14:00 WIB',
    '15:00' => '15:00 WIB',
    '16:00' => '16:00 WIB',
    '17:00' => '17:00 WIB',
    '18:00' => '18:00 WIB',
    '19:00' => '19:00 WIB',
];
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card card-salon p-4">
            <div class="text-center mb-4">
                <i class="fa-regular fa-calendar-check fa-2x text-primary mb-2"></i>
                <h3 class="fw-bold font-outfit">Booking Layanan</h3>
                <p class="text-muted small">Pilih layanan, tanggal, dan waktu kedatangan Anda</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small py-2 px-3 mb-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="booking.php" method="POST" class="font-outfit">
                <!-- Select Service -->
                <div class="mb-3">
                    <label for="layanan_id" class="form-label small fw-semibold">Pilih Layanan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-scissors text-muted"></i></span>
                        <select class="form-select" id="layanan_id" name="layanan_id" required>
                            <option value="">-- Silakan Pilih Layanan --</option>
                            <?php foreach ($layanan_options as $lay): ?>
                                <?php 
                                $selected = '';
                                if ($pre_selected_layanan_id == $lay['id'] || (isset($_POST['layanan_id']) && $_POST['layanan_id'] == $lay['id'])) {
                                    $selected = 'selected';
                                }
                                ?>
                                <option value="<?= $lay['id']; ?>" <?= $selected; ?>>
                                    <?= htmlspecialchars($lay['nama_layanan']); ?> (Rp <?= number_format($lay['harga'], 0, ',', '.'); ?> - <?= $lay['durasi']; ?> Mnt)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Date Picker -->
                <div class="mb-3">
                    <label for="tanggal_booking" class="form-label small fw-semibold">Tanggal Kedatangan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-regular fa-calendar text-muted"></i></span>
                        <input type="date" class="form-control" id="tanggal_booking" name="tanggal_booking" 
                               min="<?= date('Y-m-d'); ?>" 
                               value="<?= isset($_POST['tanggal_booking']) ? htmlspecialchars($_POST['tanggal_booking']) : ''; ?>" 
                               required>
                    </div>
                    <small class="text-muted small">Jam operasional: Setiap hari</small>
                </div>

                <!-- Time Picker -->
                <div class="mb-3">
                    <label for="jam_booking" class="form-label small fw-semibold">Jam Kedatangan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-regular fa-clock text-muted"></i></span>
                        <select class="form-select" id="jam_booking" name="jam_booking" required>
                            <option value="">-- Pilih Jam Layanan --</option>
                            <?php foreach ($hours as $val => $label): ?>
                                <?php
                                $selected = '';
                                if (isset($_POST['jam_booking']) && $_POST['jam_booking'] === $val) {
                                    $selected = 'selected';
                                }
                                ?>
                                <option value="<?= $val; ?>" <?= $selected; ?>><?= $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="mb-4">
                    <label for="metode_pembayaran" class="form-label small fw-semibold">Metode Pembayaran</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-wallet text-muted"></i></span>
                        <select class="form-select" id="metode_pembayaran" name="metode_pembayaran" required>
                            <option value="">-- Pilih Metode Pembayaran --</option>
                            <option value="Bayar di Tempat" <?= (isset($_POST['metode_pembayaran']) && $_POST['metode_pembayaran'] === 'Bayar di Tempat') ? 'selected' : ''; ?>>Bayar di Tempat (Cash)</option>
                            <option value="Transfer Bank" <?= (isset($_POST['metode_pembayaran']) && $_POST['metode_pembayaran'] === 'Transfer Bank') ? 'selected' : ''; ?>>Transfer Bank (BCA 12345678 a.n Glowing Grace)</option>
                            <option value="QRIS" <?= (isset($_POST['metode_pembayaran']) && $_POST['metode_pembayaran'] === 'QRIS') ? 'selected' : ''; ?>>QRIS (Scan Barcode)</option>
                        </select>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex gap-2">
                    <a href="dashboard.php" class="btn btn-salon-outline w-50 py-2 fw-semibold">Kembali</a>
                    <button type="submit" class="btn btn-salon w-50 py-2 fw-bold">Buat Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../templates/customer_footer.php'; ?>
