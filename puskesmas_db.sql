-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Okt 2026 pada 12.12
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `puskesmas_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `antrean`
--

CREATE TABLE `antrean` (
  `id` int(11) NOT NULL,
  `pasien_id` int(11) NOT NULL,
  `dokter_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `nomor_antrean` int(11) NOT NULL,
  `status` enum('Menunggu','Dipanggil','Selesai','Batal') DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `antrean`
--

INSERT INTO `antrean` (`id`, `pasien_id`, `dokter_id`, `tanggal`, `nomor_antrean`, `status`, `created_at`) VALUES
(1, 2, 1, '2026-10-12', 1, 'Selesai', '2026-10-09 07:44:34'),
(2, 2, 3, '2026-10-09', 1, 'Selesai', '2026-10-09 08:02:03'),
(3, 2, 4, '2026-10-09', 1, 'Selesai', '2026-10-09 08:13:35'),
(4, 2, 3, '2026-10-09', 2, 'Selesai', '2026-10-09 08:46:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_obat`
--

CREATE TABLE `detail_obat` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `obat_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `aturan_pakai` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `detail_obat`
--

INSERT INTO `detail_obat` (`id`, `transaksi_id`, `obat_id`, `jumlah`, `harga_satuan`, `aturan_pakai`) VALUES
(1, 1, 1, 24, 500.00, NULL),
(2, 2, 2, 33, 1000.00, NULL),
(3, 3, 2, 22, 1000.00, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `dokter`
--

CREATE TABLE `dokter` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `spesialisasi` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `dokter`
--

INSERT INTO `dokter` (`id`, `nama`, `spesialisasi`, `no_hp`) VALUES
(1, 'Dr. Andi', 'Dokter Umum', '1123141131414123'),
(2, 'Dr. Siti', 'Kesehatan Ibu dan Anak', '1231231w4112413'),
(3, 'riad', 'Kesehatan Ibu dan Anak', '082261445556'),
(4, 'riad', 'Kesehatan Ibu dan Anak', '082261445556'),
(5, 'riad', 'Kesehatan Ibu dan Anak', '082261445556');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_dokter`
--

CREATE TABLE `jadwal_dokter` (
  `id` int(11) NOT NULL,
  `dokter_id` int(11) NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `jadwal_dokter`
--

INSERT INTO `jadwal_dokter` (`id`, `dokter_id`, `hari`, `jam_mulai`, `jam_selesai`) VALUES
(1, 1, 'Senin', '08:00:00', '12:00:00'),
(2, 1, 'Rabu', '08:00:00', '12:00:00'),
(3, 2, 'Selasa', '09:00:00', '13:00:00'),
(4, 2, 'Kamis', '09:00:00', '13:00:00'),
(5, 3, 'Jumat', '12:00:00', '18:00:00'),
(6, 4, 'Jumat', '12:00:00', '18:00:00'),
(7, 5, 'Jumat', '12:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `obat`
--

CREATE TABLE `obat` (
  `id` int(11) NOT NULL,
  `nama_obat` varchar(100) NOT NULL,
  `satuan` varchar(30) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `harga` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `obat`
--

INSERT INTO `obat` (`id`, `nama_obat`, `satuan`, `stok`, `harga`) VALUES
(1, 'Paracetamol', 'Tablet', 76, 15000.00),
(2, 'Vitamin C', 'Tablet', 45, 1000.00),
(3, 'batukk', 'botol', 55, 20000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pasien`
--

CREATE TABLE `pasien` (
  `id` int(11) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pasien`
--

INSERT INTO `pasien` (`id`, `nik`, `nama`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `no_hp`, `created_at`, `user_id`) VALUES
(1, '123', 'r', '2026-10-09', 'L', 'bekasi', '', '2026-10-09 07:06:44', 1),
(2, '1234', 'riad', '2026-10-09', 'P', 'jabar', '0123456789', '2026-10-09 07:11:09', 2),
(3, '3275041501060001', 'sixx', '2006-01-15', '', 'Bekasi', '082261445556', '2026-10-09 07:28:53', 4);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pemeriksaan`
--

CREATE TABLE `pemeriksaan` (
  `id` int(11) NOT NULL,
  `antrean_id` int(11) NOT NULL,
  `keluhan` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `tanggal_pemeriksaan` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pemeriksaan`
--

INSERT INTO `pemeriksaan` (`id`, `antrean_id`, `keluhan`, `diagnosis`, `catatan`, `tanggal_pemeriksaan`) VALUES
(1, 1, 'tolong kaki saya sakit', 'tulangnya geser njir', 'rawat jalan', '2026-10-09 15:12:16'),
(2, 3, 'asdaw', 'asdaw', 'asda', '2026-10-09 15:16:26'),
(3, 4, 'ahhh', 'ihhhh', 'uhhhh', '2026-10-09 15:47:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_obat`
--

CREATE TABLE `transaksi_obat` (
  `id` int(11) NOT NULL,
  `pemeriksaan_id` int(11) NOT NULL,
  `kode_pengambilan` varchar(20) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Belum Diambil','Sudah Diambil') DEFAULT 'Belum Diambil'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `transaksi_obat`
--

INSERT INTO `transaksi_obat` (`id`, `pemeriksaan_id`, `kode_pengambilan`, `tanggal`, `status`) VALUES
(1, 2, 'OBT-ES7J3S', '2026-10-09', 'Sudah Diambil'),
(2, 3, 'OBT-R8FE2U', '2026-10-09', 'Sudah Diambil'),
(3, 1, 'OBT-XUFVUV', '2026-10-09', 'Sudah Diambil');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas','pasien') DEFAULT 'pasien',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'r', 'as', '$2y$10$GKPpjiH6iSh349QdnqKRGOxYdU1OQsyORglZdHNqTNWZLuMlbZ9Nm', 'pasien', '2026-10-09 07:06:44'),
(2, 'riad', 'sixx', '$2y$10$8ItHXVmq3OdLkJic3ha9C.lLJd1IIlHzhawLnCmX.wdFFeSY9v1nu', 'pasien', '2026-10-09 07:11:09'),
(3, 'Administrator Puskesmas', 'admin', '$2y$10$Sbf9At88CkWnE5uZkSZ9y.LqVowgYCl58IfqjHWxQq3pYg69y41yq', 'admin', '2026-10-09 07:23:26'),
(4, 'sixx', '3275041501060001', '$2y$10$crJtEi3/OhdW7k1wsqvP2.jrAEAhPrGHlDAtT0f5kxOPYFu3mjTCa', 'pasien', '2026-10-09 07:28:53');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `antrean`
--
ALTER TABLE `antrean`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dokter_id` (`dokter_id`,`tanggal`,`nomor_antrean`),
  ADD KEY `pasien_id` (`pasien_id`);

--
-- Indeks untuk tabel `detail_obat`
--
ALTER TABLE `detail_obat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `obat_id` (`obat_id`);

--
-- Indeks untuk tabel `dokter`
--
ALTER TABLE `dokter`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jadwal_dokter`
--
ALTER TABLE `jadwal_dokter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dokter_id` (`dokter_id`);

--
-- Indeks untuk tabel `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pasien`
--
ALTER TABLE `pasien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `pemeriksaan`
--
ALTER TABLE `pemeriksaan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `antrean_id` (`antrean_id`);

--
-- Indeks untuk tabel `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pengambilan` (`kode_pengambilan`),
  ADD KEY `pemeriksaan_id` (`pemeriksaan_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `antrean`
--
ALTER TABLE `antrean`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `detail_obat`
--
ALTER TABLE `detail_obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `dokter`
--
ALTER TABLE `dokter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `jadwal_dokter`
--
ALTER TABLE `jadwal_dokter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `obat`
--
ALTER TABLE `obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pasien`
--
ALTER TABLE `pasien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pemeriksaan`
--
ALTER TABLE `pemeriksaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `antrean`
--
ALTER TABLE `antrean`
  ADD CONSTRAINT `antrean_ibfk_1` FOREIGN KEY (`pasien_id`) REFERENCES `pasien` (`id`),
  ADD CONSTRAINT `antrean_ibfk_2` FOREIGN KEY (`dokter_id`) REFERENCES `dokter` (`id`);

--
-- Ketidakleluasaan untuk tabel `detail_obat`
--
ALTER TABLE `detail_obat`
  ADD CONSTRAINT `detail_obat_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi_obat` (`id`),
  ADD CONSTRAINT `detail_obat_ibfk_2` FOREIGN KEY (`obat_id`) REFERENCES `obat` (`id`);

--
-- Ketidakleluasaan untuk tabel `jadwal_dokter`
--
ALTER TABLE `jadwal_dokter`
  ADD CONSTRAINT `jadwal_dokter_ibfk_1` FOREIGN KEY (`dokter_id`) REFERENCES `dokter` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pasien`
--
ALTER TABLE `pasien`
  ADD CONSTRAINT `fk_pasien_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pemeriksaan`
--
ALTER TABLE `pemeriksaan`
  ADD CONSTRAINT `pemeriksaan_ibfk_1` FOREIGN KEY (`antrean_id`) REFERENCES `antrean` (`id`);

--
-- Ketidakleluasaan untuk tabel `transaksi_obat`
--
ALTER TABLE `transaksi_obat`
  ADD CONSTRAINT `transaksi_obat_ibfk_1` FOREIGN KEY (`pemeriksaan_id`) REFERENCES `pemeriksaan` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
