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

// Check for session-passed error/success messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            // Find user in database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: http://localhost/bookingan_salon/admin/dashboard.php');
                } else {
                    header('Location: http://localhost/bookingan_salon/customer/dashboard.php');
                }
                exit;
            } else {
                $error = 'Email atau password salah.';
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
                    <h3 class="fw-bold font-outfit">Masuk Akun</h3>
                    <p class="text-muted small">Kelola pesanan kecantikan Anda dengan mudah</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger small py-2 px-3 mb-3" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success small py-2 px-3 mb-3" role="alert">
                        <i class="fa-solid fa-circle-check me-1"></i><?= htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="font-outfit">
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="nama@email.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Masukkan password Anda" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-salon w-100 py-2.5 fw-bold mb-3">Login</button>
                </form>

                <div class="text-center mt-2 small font-outfit">
                    <span class="text-muted">Belum punya akun? </span>
                    <a href="register.php" class="text-primary fw-bold text-decoration-none">Daftar di sini</a>
                </div>

                <div class="mt-4 p-3 bg-light rounded text-center small text-muted font-outfit">
                    <div class="fw-bold mb-1">Akun Uji Coba:</div>
                    <div>Admin: <strong>admin@salon.com</strong> / <strong>admin123</strong></div>
                    <div>Customer: <strong>customer@salon.com</strong> / <strong>customer123</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
