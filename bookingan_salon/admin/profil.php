<?php
require_once '../config/database.php';
include '../templates/admin_header.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current admin data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $admin = $stmt->fetch();
} catch (PDOException $e) {
    $admin = null;
    $error = 'Gagal memuat data profil: ' . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);

        if (empty($nama) || empty($email)) {
            $error = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            try {
                // Check email uniqueness (exclude current user)
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check->execute([$email, $user_id]);
                if ($check->fetch()) {
                    $error = 'Email sudah digunakan oleh akun lain.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
                    $stmt->execute([$nama, $email, $user_id]);
                    $_SESSION['nama'] = $nama;
                    $success = 'Profil berhasil diperbarui!';

                    // Refresh admin data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $admin = $stmt->fetch();
                }
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui profil: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Semua kolom password wajib diisi.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } elseif (!password_verify($current_password, $admin['password'])) {
            $error = 'Password saat ini salah.';
        } else {
            try {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user_id]);
                $success = 'Password berhasil diubah!';
            } catch (PDOException $e) {
                $error = 'Gagal mengubah password: ' . $e->getMessage();
            }
        }
    }
}
?>

<!-- Page Title -->
<div class="mb-4">
    <h3 class="fw-bold font-outfit mb-1"><i class="fa-solid fa-user-gear text-primary me-2"></i>Profil Administrator</h3>
    <p class="text-muted small mb-0 font-outfit">Kelola informasi akun dan ubah password Anda</p>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success small py-2 px-3 mb-3" role="alert">
        <i class="fa-solid fa-circle-check me-1"></i><?= htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger small py-2 px-3 mb-3" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Info Card -->
    <div class="col-lg-4">
        <div class="card card-salon p-4 text-center">
            <div class="mx-auto mb-3" style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-user-tie fa-3x text-white"></i>
            </div>
            <h4 class="fw-bold font-outfit mb-1"><?= htmlspecialchars($admin['nama']); ?></h4>
            <p class="text-muted small mb-2"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($admin['email']); ?></p>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 font-outfit mx-auto" style="width: fit-content;">
                <i class="fa-solid fa-shield-halved me-1"></i>Administrator
            </span>
            <hr class="my-3">
            <div class="text-start small text-muted font-outfit">
                <p class="mb-2"><i class="fa-solid fa-id-card text-primary me-2 fw-normal"></i><strong>ID Akun:</strong> #<?= $admin['id']; ?></p>
                <p class="mb-2"><i class="fa-solid fa-user-tag text-primary me-2"></i><strong>Role:</strong> <?= ucfirst($admin['role']); ?></p>
                <p class="mb-0"><i class="fa-regular fa-calendar text-primary me-2"></i><strong>Terdaftar:</strong> <?= date('d-m-Y H:i', strtotime($admin['created_at'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <div class="col-lg-8">
        <!-- Update Info Form -->
        <div class="card card-salon p-4 mb-4">
            <h5 class="font-outfit fw-bold mb-3"><i class="fa-regular fa-pen-to-square text-primary me-2"></i>Edit Informasi Profil</h5>
            <form action="profil.php" method="POST" class="font-outfit">
                <input type="hidden" name="action" value="update_profil">
                <div class="mb-3">
                    <label for="nama" class="form-label small fw-semibold">Nama Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-regular fa-user text-muted"></i></span>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($admin['nama']); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-regular fa-envelope text-muted"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($admin['email']); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-salon py-2 px-4 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="card card-salon p-4">
            <h5 class="font-outfit fw-bold mb-3"><i class="fa-solid fa-key text-primary me-2"></i>Ubah Password</h5>
            <form action="profil.php" method="POST" class="font-outfit">
                <input type="hidden" name="action" value="update_password">
                <div class="mb-3">
                    <label for="current_password" class="form-label small fw-semibold">Password Saat Ini</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-lock text-muted"></i></span>
                        <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Masukkan password saat ini" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label small fw-semibold">Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-lock-open text-muted"></i></span>
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="form-label small fw-semibold">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-shield-halved text-muted"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Ulangi password baru" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-salon-outline py-2 px-4 fw-bold">
                    <i class="fa-solid fa-key me-1"></i>Ubah Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../templates/admin_footer.php'; ?>
