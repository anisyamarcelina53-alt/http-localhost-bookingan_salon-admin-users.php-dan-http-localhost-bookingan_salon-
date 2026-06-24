<?php
require_once '../config/database.php';
include '../templates/admin_header.php';

$error = '';
$success = '';
$edit_mode = false;
$service_to_edit = null;

// Ensure upload directory exists
$upload_dir = '../assets/images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 1. DELETE ACTION (GET)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        // Find existing image to delete from disk
        $stmt = $pdo->prepare("SELECT gambar FROM layanan WHERE id = ?");
        $stmt->execute([$id]);
        $lay = $stmt->fetch();
        if ($lay && !empty($lay['gambar']) && file_exists($upload_dir . $lay['gambar'])) {
            unlink($upload_dir . $lay['gambar']);
        }

        // Delete from DB
        $del_stmt = $pdo->prepare("DELETE FROM layanan WHERE id = ?");
        $del_stmt->execute([$id]);
        $_SESSION['success'] = 'Layanan berhasil dihapus.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Gagal menghapus layanan: ' . $e->getMessage();
    }
    echo "<script>window.location.href='layanan.php';</script>";
    exit;
}

// 2. RETRIEVE EDIT RECORD (GET)
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM layanan WHERE id = ?");
    $stmt->execute([$edit_id]);
    $service_to_edit = $stmt->fetch();
    if ($service_to_edit) {
        $edit_mode = true;
    }
}

// 3. CREATE OR UPDATE ACTION (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_layanan = trim($_POST['nama_layanan']);
    $harga = floatval($_POST['harga']);
    $durasi = intval($_POST['durasi']);
    $deskripsi = trim($_POST['deskripsi']);
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if (empty($nama_layanan) || $harga <= 0 || $durasi <= 0) {
        $error = 'Nama layanan, harga, dan durasi wajib diisi dengan benar.';
    } else {
        try {
            // Handle file upload
            $gambar_name = '';
            if ($id > 0) {
                // Keep original image if not replaced
                $stmt = $pdo->prepare("SELECT gambar FROM layanan WHERE id = ?");
                $stmt->execute([$id]);
                $original = $stmt->fetch();
                $gambar_name = $original ? $original['gambar'] : '';
            }

            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['gambar']['tmp_name'];
                $file_name = $_FILES['gambar']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                if (in_array($file_ext, $allowed)) {
                    // Delete old file if updating
                    if ($id > 0 && !empty($gambar_name) && file_exists($upload_dir . $gambar_name)) {
                        unlink($upload_dir . $gambar_name);
                    }
                    // Save new file
                    $new_file_name = md5(time() . $file_name) . '.' . $file_ext;
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                        $gambar_name = $new_file_name;
                    }
                } else {
                    $error = 'Format gambar tidak didukung (gunakan JPG, JPEG, PNG, WEBP, atau GIF).';
                }
            }

            if (empty($error)) {
                if ($id > 0) {
                    // Update Mode
                    $stmt = $pdo->prepare("
                        UPDATE layanan 
                        SET nama_layanan = ?, harga = ?, durasi = ?, deskripsi = ?, gambar = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$nama_layanan, $harga, $durasi, $deskripsi, $gambar_name, $id]);
                    $_SESSION['success'] = 'Layanan berhasil diperbarui.';
                } else {
                    // Insert Mode
                    $stmt = $pdo->prepare("
                        INSERT INTO layanan (nama_layanan, harga, durasi, deskripsi, gambar) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$nama_layanan, $harga, $durasi, $deskripsi, $gambar_name]);
                    $_SESSION['success'] = 'Layanan baru berhasil ditambahkan.';
                }
                echo "<script>window.location.href='layanan.php';</script>";
                exit;
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

// Fetch all services for listing
try {
    $stmt = $pdo->query("SELECT * FROM layanan ORDER BY id DESC");
    $services = $stmt->fetchAll();
} catch (PDOException $e) {
    $services = [];
    $error = 'Gagal mengambil katalog layanan: ' . $e->getMessage();
}
?>

<div class="row">
    <!-- Form Card (Add/Edit) -->
    <div class="col-lg-4 mb-4">
        <div class="card card-salon p-4">
            <h4 class="mb-3 font-outfit text-dark">
                <?= $edit_mode ? '<i class="fa-regular fa-pen-to-square text-primary me-2"></i>Edit Layanan' : '<i class="fa-solid fa-plus text-primary me-2"></i>Tambah Layanan'; ?>
            </h4>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small py-2 px-3 mb-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i><?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="layanan.php" method="POST" enctype="multipart/form-data" class="font-outfit">
                <input type="hidden" name="id" value="<?= $edit_mode ? $service_to_edit['id'] : 0; ?>">

                <div class="mb-3">
                    <label for="nama_layanan" class="form-label small fw-semibold">Nama Layanan</label>
                    <input type="text" class="form-control" id="nama_layanan" name="nama_layanan" 
                           value="<?= $edit_mode ? htmlspecialchars($service_to_edit['nama_layanan']) : ''; ?>" 
                           placeholder="Contoh: Spa Wajah Premium" required>
                </div>

                <div class="mb-3">
                    <label for="harga" class="form-label small fw-semibold">Harga (Rp)</label>
                    <input type="number" class="form-control" id="harga" name="harga" 
                           value="<?= $edit_mode ? intval($service_to_edit['harga']) : ''; ?>" 
                           placeholder="Contoh: 120000" min="0" required>
                </div>

                <div class="mb-3">
                    <label for="durasi" class="form-label small fw-semibold">Durasi (Menit)</label>
                    <input type="number" class="form-control" id="durasi" name="durasi" 
                           value="<?= $edit_mode ? intval($service_to_edit['durasi']) : ''; ?>" 
                           placeholder="Contoh: 60" min="1" required>
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label small fw-semibold">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" 
                              placeholder="Deskripsi singkat mengenai detail layanan..."><?= $edit_mode ? htmlspecialchars($service_to_edit['deskripsi']) : ''; ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="gambar" class="form-label small fw-semibold">Gambar Layanan</label>
                    <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                    <?php if ($edit_mode && !empty($service_to_edit['gambar'])): ?>
                        <div class="mt-2">
                            <span class="small text-muted d-block mb-1">Gambar saat ini:</span>
                            <img src="<?= $upload_dir . $service_to_edit['gambar']; ?>" class="rounded shadow-sm" style="max-height: 80px; max-width: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <?php if ($edit_mode): ?>
                        <a href="layanan.php" class="btn btn-salon-outline w-50 py-2 fw-semibold">Batal</a>
                        <button type="submit" class="btn btn-salon w-50 py-2 fw-bold">Update</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-salon w-100 py-2 fw-bold">Simpan Layanan</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Listing Table Card -->
    <div class="col-lg-8 mb-4">
        <div class="card card-salon p-4">
            <h4 class="mb-3 font-outfit text-dark"><i class="fa-solid fa-list text-primary me-2"></i>Daftar Layanan Salon</h4>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success small py-2 px-3 mb-3" role="alert">
                    <i class="fa-solid fa-circle-check me-1"></i><?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($services)): ?>
                <div class="text-center py-5 text-muted">
                    <p class="mb-0">Belum ada layanan salon yang terdaftar.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-salon align-middle">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Layanan</th>
                                <th>Harga</th>
                                <th>Durasi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $s): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($s['gambar']) && file_exists($upload_dir . $s['gambar'])): ?>
                                            <img src="<?= $upload_dir . $s['gambar']; ?>" class="rounded shadow-sm" style="width: 60px; height: 45px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary-subtle text-secondary rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 45px;">
                                                <i class="fa-solid fa-image small text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['nama_layanan']); ?></div>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;">
                                            <?= htmlspecialchars($s['deskripsi']); ?>
                                        </small>
                                    </td>
                                    <td class="fw-semibold text-primary font-outfit">
                                        Rp <?= number_format($s['harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="font-outfit text-muted">
                                        <?= $s['durasi']; ?> Menit
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="layanan.php?edit=<?= $s['id']; ?>" class="btn btn-sm btn-outline-primary px-2.5 py-1 rounded-pill small">
                                                <i class="fa-regular fa-pen-to-square"></i> Edit
                                            </a>
                                            <a href="layanan.php?action=delete&id=<?= $s['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger px-2.5 py-1 rounded-pill small" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');">
                                                <i class="fa-solid fa-trash-can"></i> Hapus
                                            </a>
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
