<?php
// Database Configuration
$host = 'localhost';
$db_name = 'bookingan_salon';
$username = 'root';
$password = ''; // Default XAMPP password is empty

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Auto-migration for payments feature
    try {
        $stmt = $pdo->query("DESCRIBE booking");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('metode_pembayaran', $columns)) {
            $pdo->exec("ALTER TABLE booking ADD COLUMN metode_pembayaran VARCHAR(50) NOT NULL DEFAULT 'Bayar di Tempat'");
        }
        if (!in_array('bukti_pembayaran', $columns)) {
            $pdo->exec("ALTER TABLE booking ADD COLUMN bukti_pembayaran VARCHAR(255) DEFAULT NULL");
        }
        if (!in_array('status_pembayaran', $columns)) {
            $pdo->exec("ALTER TABLE booking ADD COLUMN status_pembayaran ENUM('Belum Bayar', 'Menunggu Verifikasi', 'Lunas', 'Batal') DEFAULT 'Belum Bayar'");
        }
    } catch (PDOException $mig_e) {
        // Silently pass or log error if table doesn't exist yet
    }
    
} catch (PDOException $e) {
    // Connection failed
    die("Koneksi database gagal: " . $e->getMessage());
}
?>
