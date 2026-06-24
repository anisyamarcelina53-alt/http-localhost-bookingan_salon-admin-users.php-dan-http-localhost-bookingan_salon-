-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 24 Jun 2026 pada 15.28
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bookingan_salon`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `booking`
--

CREATE TABLE `booking` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `layanan_id` int(11) NOT NULL,
  `tanggal_booking` date NOT NULL,
  `jam_booking` time NOT NULL,
  `status` enum('Menunggu','Diproses','Selesai','Dibatalkan') DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `metode_pembayaran` varchar(50) NOT NULL DEFAULT 'Bayar di Tempat',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `status_pembayaran` enum('Belum Bayar','Menunggu Verifikasi','Lunas','Batal') DEFAULT 'Belum Bayar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `booking`
--

INSERT INTO `booking` (`id`, `user_id`, `layanan_id`, `tanggal_booking`, `jam_booking`, `status`, `created_at`, `metode_pembayaran`, `bukti_pembayaran`, `status_pembayaran`) VALUES
(1, 2, 1, '2026-06-18', '14:00:00', 'Selesai', '2026-06-17 12:40:43', 'Bayar di Tempat', NULL, 'Belum Bayar'),
(2, 3, 2, '2026-06-17', '11:00:00', 'Dibatalkan', '2026-06-17 12:55:02', 'Bayar di Tempat', NULL, 'Belum Bayar'),
(3, 3, 3, '2026-06-22', '12:00:00', 'Diproses', '2026-06-22 04:36:20', 'Transfer Bank', NULL, 'Belum Bayar');

-- --------------------------------------------------------

--
-- Struktur dari tabel `layanan`
--

CREATE TABLE `layanan` (
  `id` int(11) NOT NULL,
  `nama_layanan` varchar(100) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `durasi` int(11) NOT NULL COMMENT 'duration in minutes',
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `layanan`
--

INSERT INTO `layanan` (`id`, `nama_layanan`, `harga`, `durasi`, `deskripsi`, `gambar`, `created_at`) VALUES
(1, 'Gunting Rambut & Styling', 75000.00, 45, 'Potong rambut dengan stylist profesional termasuk cuci rambut, pijat kepala ringan, dan hair blow styling.', 'haircut.jpg', '2026-06-17 12:40:26'),
(2, 'Facial & Spa Treatment', 150000.00, 60, 'Perawatan wajah menyeluruh menggunakan bahan alami untuk membersihkan, mengeksfoliasi, dan mencerahkan kulit wajah Anda.', 'facial.jpg', '2026-06-17 12:40:26'),
(3, 'Manicure & Pedicure', 90000.00, 45, 'Perawatan kuku tangan dan kaki profesional lengkap dengan scrub, pembersihan kutikula, pijat refleksi ringan, dan pewarnaan kuku.', 'maniped.jpg', '2026-06-17 12:40:26'),
(4, 'Hair Coloring (Pewarnaan Rambut)', 250000.00, 120, 'Pewarnaan rambut trendi menggunakan produk berkualitas premium yang aman bagi kulit kepala dan menjaga kelembaban rambut.', 'haircolor.jpg', '2026-06-17 12:40:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `metode_pembayaran` enum('QRIS','Transfer Bank','Bayar di Tempat') NOT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `status_pembayaran` enum('Menunggu Verifikasi','Diterima','Ditolak','Bayar di Tempat','Lunas') DEFAULT 'Menunggu Verifikasi',
  `tanggal_bayar` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `qris`
--

CREATE TABLE `qris` (
  `id` int(11) NOT NULL,
  `gambar_qris` varchar(255) NOT NULL,
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `qris`
--

INSERT INTO `qris` (`id`, `gambar_qris`, `status`) VALUES
(1, 'qris_mockup.png', 'Aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rekening_bank`
--

CREATE TABLE `rekening_bank` (
  `id` int(11) NOT NULL,
  `nama_bank` varchar(100) NOT NULL,
  `nomor_rekening` varchar(50) NOT NULL,
  `atas_nama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rekening_bank`
--

INSERT INTO `rekening_bank` (`id`, `nama_bank`, `nomor_rekening`, `atas_nama`) VALUES
(1, 'Bank BCA', '1234567890', 'Glowing Grace Salon'),
(2, 'Bank Mandiri', '9876543210', 'Glowing Grace Salon');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@salon.com', '$2y$10$gg.s4vWoLqytOKm1VJT2V.7obot0L7CxT7HkwlnUPk0oNwn/3GE72', 'admin', '2026-06-17 12:40:26'),
(2, 'Jane Doe', 'customer@salon.com', '$2y$10$Ft9L0pMkjd.plEpm13IVge7bBl54Qz40qTC0gMymWOCWH9.WIeZ8.', 'customer', '2026-06-17 12:40:26'),
(3, 'Anisya Marcelina', 'anisyamarcelina53@gmail.com', '$2y$10$rzQxpiqAi.5S.ghkxgSxEOlVnWEACdwUmIBRjivxNyB3DbzhWy34u', 'customer', '2026-06-17 12:54:25');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `layanan_id` (`layanan_id`);

--
-- Indeks untuk tabel `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indeks untuk tabel `qris`
--
ALTER TABLE `qris`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rekening_bank`
--
ALTER TABLE `rekening_bank`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `qris`
--
ALTER TABLE `qris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rekening_bank`
--
ALTER TABLE `rekening_bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`layanan_id`) REFERENCES `layanan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
