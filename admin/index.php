<?php
session_start();
require_once "../koneksi.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

function hitung($pdo, $sql) {
    return (int) $pdo->query($sql)->fetchColumn();
}

$totalPasien = hitung($pdo, "SELECT COUNT(*) FROM pasien");
$totalDokter = hitung($pdo, "SELECT COUNT(*) FROM dokter");
$totalAntrean = hitung(
    $pdo,
    "SELECT COUNT(*) FROM antrean
     WHERE tanggal = CURDATE()"
);
$totalObat = hitung($pdo, "SELECT COUNT(*) FROM obat");

$sql = "SELECT a.id, a.nomor_antrean, a.tanggal, a.status,
               p.nama AS nama_pasien,
               d.nama AS nama_dokter
        FROM antrean a
        JOIN pasien p ON a.pasien_id = p.id
        JOIN dokter d ON a.dokter_id = d.id
        WHERE a.tanggal = CURDATE()
        ORDER BY a.nomor_antrean ASC";

$antrean = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Puskesmas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">

<header class="admin-header">
    <div>
        <strong>Admin Puskesmas</strong>
        <div>Halo, <?= htmlspecialchars($_SESSION["nama"] ?? "Administrator") ?></div>
    </div>
    <a href="../logout.php">Logout</a>
</header>

<main class="admin-content">
    <h1>Dashboard</h1>
    <p>Ringkasan data pelayanan Puskesmas hari ini.</p>

    <section class="stats">
        <div class="stat">
            <p>Total Pasien</p>
            <h2><?= $totalPasien ?></h2>
        </div>
        <div class="stat">
            <p>Total Dokter</p>
            <h2><?= $totalDokter ?></h2>
        </div>
        <div class="stat">
            <p>Antrean Hari Ini</p>
            <h2><?= $totalAntrean ?></h2>
        </div>
        <div class="stat">
            <p>Jenis Obat</p>
            <h2><?= $totalObat ?></h2>
        </div>
    </section>

    <h2>Menu Pengelolaan</h2>
    <section class="admin-menu">
        <a href="data_pasien.php"><strong>Data Pasien</strong><p>Lihat data pasien terdaftar.</p></a>
        <a href="dokter.php"><strong>Data Dokter</strong><p>Kelola dokter dan jadwal praktik.</p></a>
        <a href="antrean.php"><strong>Antrean Pasien</strong><p>Pantau antrean dan status pelayanan.</p></a>
        <a href="pemeriksaan.php"><strong>Pemeriksaan</strong><p>Catat keluhan dan diagnosis.</p></a>
        <a href="obat.php"><strong>Data Obat</strong><p>Kelola obat dan stok.</p></a>
        <a href="transaksi_obat.php"><strong>Transaksi Obat</strong><p>Kelola kode pengambilan obat.</p></a>
        <a href="pengambilan_obat.php" class="menu-card"><strong>Pengambilan Obat</strong><p>Konfirmasi dan kelola pengambilan obat pasien.</p></a>

    </section>

    <h2>Antrean Hari Ini</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Pasien</th>
                    <th>Dokter</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$antrean): ?>
                    <tr>
                        <td colspan="4">Belum ada antrean hari ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($antrean as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a["nomor_antrean"]) ?></td>
                            <td><?= htmlspecialchars($a["nama_pasien"]) ?></td>
                            <td><?= htmlspecialchars($a["nama_dokter"]) ?></td>
                            <td><?= htmlspecialchars($a["status"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>