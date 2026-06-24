<?php
require_once 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: http://localhost/bookingan_salon/admin/dashboard.php');
    } else {
        header('Location: http://localhost/bookingan_salon/customer/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validations
    if (empty($nama) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal terdiri dari 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user as customer
                $insert_stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'customer')");
                $insert_stmt->execute([$nama, $email, $hashed_password]);
                
                $_SESSION['success'] = 'Registrasi berhasil! Silakan login menggunakan email dan password Anda.';
                header('Location: http://localhost/bookingan_salon/login.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

include 'templates/header.php';
?>

<div class="container py-5 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card card-salon p-4">
                <div class="text-center mb-4">
                    <i class="fa-solid fa-gem fa-2x text-primary mb-2"></i>
                    <h3 class="fw-bold font-outfit">Daftar Akun</h3>
                    <p class="text-muted small">Buat akun untuk memesan layanan kecantikan Anda</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger small py-2 px-3 mb-3" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" class="font-outfit">
                    <div class="mb-3">
                        <label for="nama" class="form-label small fw-semibold">Nama Lengkap</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" id="nama" name="nama" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" placeholder="Masukkan nama lengkap" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="nama@email.com" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Minimal 6 karakter" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label small fw-semibold">Konfirmasi Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-shield-halved text-muted"></i></span>
                            <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-salon w-100 py-2.5 fw-bold mb-3">Daftar Sekarang</button>
                </form>

                <div class="text-center mt-2 small font-outfit">
                    <span class="text-muted">Sudah punya akun? </span>
                    <a href="login.php" class="text-primary fw-bold text-decoration-none">Masuk di sini</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
