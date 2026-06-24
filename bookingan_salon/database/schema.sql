-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `bookingan_salon`;
USE `bookingan_salon`;

-- Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'customer') DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `layanan`
CREATE TABLE IF NOT EXISTS `layanan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_layanan` VARCHAR(100) NOT NULL,
  `harga` DECIMAL(10,2) NOT NULL,
  `durasi` INT NOT NULL COMMENT 'duration in minutes',
  `deskripsi` TEXT,
  `gambar` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `booking`
CREATE TABLE IF NOT EXISTS `booking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `layanan_id` INT NOT NULL,
  `tanggal_booking` DATE NOT NULL,
  `jam_booking` TIME NOT NULL,
  `status` ENUM('Menunggu', 'Diproses', 'Selesai', 'Dibatalkan') DEFAULT 'Menunggu',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`layanan_id`) REFERENCES `layanan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data for table `users`
-- Admin: admin@salon.com / admin123
-- Customer: customer@salon.com / customer123
INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`) VALUES
(1, 'Administrator', 'admin@salon.com', '$2y$10$gg.s4vWoLqytOKm1VJT2V.7obot0L7CxT7HkwlnUPk0oNwn/3GE72', 'admin'),
(2, 'Jane Doe', 'customer@salon.com', '$2y$10$Ft9L0pMkjd.plEpm13IVge7bBl54Qz40qTC0gMymWOCWH9.WIeZ8.', 'customer')
ON DUPLICATE KEY UPDATE `email` = `email`;

-- Seed data for table `layanan`
INSERT INTO `layanan` (`id`, `nama_layanan`, `harga`, `durasi`, `deskripsi`, `gambar`) VALUES
(1, 'Gunting Rambut & Styling', 75000.00, 45, 'Potong rambut dengan stylist profesional termasuk cuci rambut, pijat kepala ringan, dan hair blow styling.', 'haircut.jpg'),
(2, 'Facial & Spa Treatment', 150000.00, 60, 'Perawatan wajah menyeluruh menggunakan bahan alami untuk membersihkan, mengeksfoliasi, dan mencerahkan kulit wajah Anda.', 'facial.jpg'),
(3, 'Manicure & Pedicure', 90000.00, 45, 'Perawatan kuku tangan dan kaki profesional lengkap dengan scrub, pembersihan kutikula, pijat refleksi ringan, dan pewarnaan kuku.', 'maniped.jpg'),
(4, 'Hair Coloring (Pewarnaan Rambut)', 250000.00, 120, 'Pewarnaan rambut trendi menggunakan produk berkualitas premium yang aman bagi kulit kepala dan menjaga kelembaban rambut.', 'haircolor.jpg')
ON DUPLICATE KEY UPDATE `nama_layanan` = `nama_layanan`;
