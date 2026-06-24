<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Check current columns on booking table
    $stmt = $pdo->query("DESCRIBE booking");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Memeriksa tabel booking...\n";

    // 1. Add metode_pembayaran if not exists
    if (!in_array('metode_pembayaran', $columns)) {
        $pdo->exec("ALTER TABLE booking ADD COLUMN metode_pembayaran VARCHAR(50) NOT NULL DEFAULT 'Bayar di Tempat'");
        echo "Kolom 'metode_pembayaran' berhasil ditambahkan.\n";
    } else {
        echo "Kolom 'metode_pembayaran' sudah ada.\n";
    }

    // 2. Add bukti_pembayaran if not exists
    if (!in_array('bukti_pembayaran', $columns)) {
        $pdo->exec("ALTER TABLE booking ADD COLUMN bukti_pembayaran VARCHAR(255) DEFAULT NULL");
        echo "Kolom 'bukti_pembayaran' berhasil ditambahkan.\n";
    } else {
        echo "Kolom 'bukti_pembayaran' sudah ada.\n";
    }

    // 3. Add status_pembayaran if not exists
    if (!in_array('status_pembayaran', $columns)) {
        $pdo->exec("ALTER TABLE booking ADD COLUMN status_pembayaran ENUM('Belum Bayar', 'Menunggu Verifikasi', 'Lunas', 'Batal') DEFAULT 'Belum Bayar'");
        echo "Kolom 'status_pembayaran' berhasil ditambahkan.\n";
    } else {
        echo "Kolom 'status_pembayaran' sudah ada.\n";
    }

    echo "Pembaruan skema database selesai dengan sukses!\n";
} catch (PDOException $e) {
    die("Gagal memperbarui skema database: " . $e->getMessage() . "\n");
}
?>
