<?php
require_once "koneksi.php";
session_start();

if (empty($_SESSION["user_id"]) || $_SESSION["role"] != "pasien") {
    header("Location: login.php");
    exit;
}

$q = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
$q->execute([$_SESSION["user_id"]]);
$u = $q->fetch();

if (!$u) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bulan = [
    1=>'Januari','Februari','Maret','April','Mei','Juni',
    'Juli','Agustus','September','Oktober','November','Desember'
];

$tanggal = $hari[date('w')] . ', ' . date('j') . ' ' .
           $bulan[(int)date('n')] . ' ' . date('Y');

$menu = [
    ['Daftar Antrean','Daftarkan pemeriksaan','antrean.php'],
    ['Jadwal Dokter','Lihat jadwal praktik','jadwal_dokter.php'],
    ['Kode Pengambilan Obat','Cek status obat','cek_obat.php'],
    ['Riwayat Pemeriksaan','Lihat pemeriksaan sebelumnya','riwayat.php']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beranda Pasien</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="navbar">
    <div class="logo">Puskesmas Sehat</div>
    <div class="nav-user">
        <span class="nav-name"><?= htmlspecialchars($u['nama']) ?></span>
        <a href="profil.php">Profil</a>
        <a href="logout.php">Keluar</a>
    </div>
</header>

<main class="container">
    <section class="hero">
        <p class="hero-date"><?= htmlspecialchars($tanggal) ?></p>
        <h1>Selamat datang, <?= htmlspecialchars($u['nama']) ?>!</h1>
        <p>Silakan pilih layanan kesehatan yang Anda butuhkan.</p>
        <a href="profil.php" class="button">Lihat Profil Lengkap</a>
    </section>

    <h2 class="section-title">Layanan Pasien</h2>

    <div class="grid menu-grid">
        <?php foreach ($menu as $m): ?>
        <a class="card menu-card"
           href="pasien/<?= htmlspecialchars($m[2]) ?>">
            <h3><?= htmlspecialchars($m[0]) ?></h3>
            <p><?= htmlspecialchars($m[1]) ?></p>
            <span>Lihat layanan →</span>
        </a>
        <?php endforeach; ?>
    </div>
</main>

<footer class="footer">Puskesmas Sehat</footer>
</body>
</html>