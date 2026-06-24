<?php
require_once '../config/database.php';
include '../templates/admin_header.php';

$error = '';
$success = '';
$edit_mode = false;
$user_to_edit = null;

// 1. DELETE ACTION (GET)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($id === intval($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Akses ditolak. Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'User berhasil dihapus.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Gagal menghapus user: ' . $e->getMessage();
        }
    }
    echo "<script>window.location.href='users.php';</script>";
    exit;
}

// 2. RETRIEVE EDIT RECORD (GET)
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $user_to_edit = $stmt->fetch();
    if ($user_to_edit) {
        $edit_mode = true;
    }
}

// 3. CREATE OR UPDATE ACTION (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $allowed_roles = ['admin', 'customer'];

    if (empty($nama) || empty($email) || !in_array($role, $allowed_roles)) {
        $error = 'Nama, email, dan role wajib diisi dengan benar.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($id === 0 && empty($password)) {
        $error = 'Password wajib diisi untuk user baru.';
    } elseif ($id === intval($_SESSION['user_id']) && $role !== 'admin') {
        $error = 'Anda tidak dapat mengubah role Anda sendiri menjadi Customer.';
    } else {
        try {
            // Check for email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar oleh pengguna lain.';
            } else {
                if ($id > 0) {
                    // Update mode
                    if (!empty($password)) {
                        // If password is changed
                        if (strlen($password) < 6) {
                            $error = 'Password minimal 6 karakter.';
                        } else {
                            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                            $update_stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, password = ?, role = ? WHERE id = ?");
                            $update_stmt->execute([$nama, $email, $hashed_pass, $role, $id]);
                            $_SESSION['success'] = 'User berhasil diperbarui.';
                        }
                    } else {
                        // If password is not changed
                        $update_stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
                        $update_stmt->execute([$nama, $email, $role, $id]);
                        $_SESSION['success'] = 'User berhasil diperbarui.';
                    }
                    
                    // Update session name if editing self
                    if ($id === intval($_SESSION['user_id'])) {
                        $_SESSION['nama'] = $nama;
                    }
                } else {
                    // Insert mode
                    if (strlen($password) < 6) {
                        $error = 'Password minimal 6 karakter.';
                    } else {
                        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                        $insert_stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                        $insert_stmt->execute([$nama, $email, $hashed_pass, $role]);
                        $_SESSION['success'] = 'User baru berhasil ditambahkan.';
                    }
                }

                if (empty($error)) {
                    echo "<script>window.location.href='users.php';</script>";
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan database: ' . $e->getMessage();
        }
    }
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

// Fetch all users for listing, including total booking count
try {
    $stmt = $pdo->query("
        SELECT u.id, u.nama, u.email, u.role, u.created_at, COUNT(b.id) AS total_booking 
        FROM users u 
        LEFT JOIN booking b ON u.id = b.user_id 
        GROUP BY u.id 
        ORDER BY u.role ASC, u.created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $error = 'Gagal mengambil data user: ' . $e->getMessage();
}
?>

<div class="row">
    <!-- Form Card (Add/Edit) -->
    <div class="col-lg-4 mb-4 font-outfit">
        <div class="card card-salon p-4">
            <h4 class="mb-3 font-outfit text-dark">
                <?= $edit_mode ? '<i class="fa-regular fa-pen-to-square text-primary me-2"></i>Edit User' : '<i class="fa-solid fa-plus text-primary me-2"></i>Tambah User'; ?>
            </h4>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small py-2 px-3 mb-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="users.php" method="POST">
                <input type="hidden" name="id" value="<?= $edit_mode ? $user_to_edit['id'] : 0; ?>">

                <div class="mb-3">
                    <label for="nama" class="form-label small fw-semibold">Nama Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-regular fa-user text-muted"></i></span>
                        <input type="text" class="form-control" id="nama" name="nama" 
                               value="<?= $edit_mode ? htmlspecialchars($user_to_edit['nama']) : ''; ?>" 
                               placeholder="Nama Lengkap" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-regular fa-envelope text-muted"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= $edit_mode ? htmlspecialchars($user_to_edit['email']) : ''; ?>" 
                               placeholder="nama@email.com" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-lock text-muted"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="<?= $edit_mode ? 'Biarkan kosong jika tidak diubah' : 'Minimal 6 karakter'; ?>" 
                               <?= $edit_mode ? '' : 'required'; ?>>
                    </div>
                    <?php if ($edit_mode): ?>
                        <div class="form-text text-muted small" style="font-size: 0.75rem;">Biarkan kolom password kosong jika tidak ingin mengubah password user.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="role" class="form-label small fw-semibold">Role Pengguna</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-user-tag text-muted"></i></span>
                        <select class="form-select" id="role" name="role" required>
                            <option value="customer" <?= $edit_mode && $user_to_edit['role'] === 'customer' ? 'selected' : ''; ?>>Customer (Pelanggan)</option>
                            <option value="admin" <?= $edit_mode && $user_to_edit['role'] === 'admin' ? 'selected' : ''; ?>>Admin (Pengelola)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <?php if ($edit_mode): ?>
                        <a href="users.php" class="btn btn-salon-outline w-50 py-2 fw-semibold">Batal</a>
                        <button type="submit" class="btn btn-salon w-50 py-2 fw-bold">Update</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-salon w-100 py-2 fw-bold">Simpan User</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Listing Table Card -->
    <div class="col-lg-8 mb-4">
        <div class="card card-salon p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 font-outfit text-dark"><i class="fa-solid fa-users text-primary me-2"></i>Daftar Pengguna Website</h4>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success small py-2 px-3 mb-3" role="alert">
                    <i class="fa-solid fa-circle-check me-1"></i><?= $success; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($users)): ?>
                <div class="text-center py-5 text-muted">
                    <p class="mb-0">Belum ada user terdaftar.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-salon align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Total Booking</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($users as $u): ?>
                                <?php
                                $role_badge = '';
                                if ($u['role'] === 'admin') {
                                    $role_badge = 'badge bg-primary-subtle text-primary border border-primary-subtle';
                                } else {
                                    $role_badge = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle';
                                }
                                ?>
                                <tr class="small">
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($u['nama']); ?></div>
                                        <small class="text-muted font-outfit">Terdaftar: <?= date('d-m-Y', strtotime($u['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="font-outfit"><i class="fa-regular fa-envelope me-1 text-primary"></i><?= htmlspecialchars($u['email']); ?></span>
                                    </td>
                                    <td>
                                        <span class="font-outfit <?= $role_badge; ?>"><?= ucfirst($u['role']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'customer'): ?>
                                            <span class="badge bg-light text-dark font-outfit border">
                                                <?= $u['total_booking']; ?> Kali
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted italic small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="users.php?edit=<?= $u['id']; ?>" class="btn btn-sm btn-outline-primary px-2.5 py-1 rounded-pill small">
                                                <i class="fa-regular fa-pen-to-square"></i> Edit
                                            </a>
                                            <?php if ($u['id'] !== intval($_SESSION['user_id'])): ?>
                                                <a href="users.php?action=delete&id=<?= $u['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger px-2.5 py-1 rounded-pill small" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus user ini? Semua booking terkait user ini juga akan ikut dihapus.');">
                                                    <i class="fa-solid fa-trash-can"></i> Hapus
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary px-2.5 py-1 rounded-pill small" disabled title="Anda tidak bisa menghapus akun sendiri">
                                                    <i class="fa-solid fa-ban"></i> Hapus
                                                </button>
                                            <?php endif; ?>
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

<?php include '../templates/admin_footer.php'; ?>
