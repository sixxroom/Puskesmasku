<?php
require_once "../koneksi.php";
session_start();

if (empty($_SESSION["user_id"]) || $_SESSION["role"] != "pasien") {
    header("Location: ../login.php");
    exit;
}

$q = $pdo->prepare("
    SELECT a.tanggal, d.nama AS dokter, p.keluhan,
           p.diagnosis, p.catatan
    FROM antrean a
    JOIN pasien ps ON ps.id = a.pasien_id
    JOIN dokter d ON d.id = a.dokter_id
    JOIN pemeriksaan p ON p.antrean_id = a.id
    WHERE ps.user_id = ?
    ORDER BY p.tanggal_pemeriksaan DESC
");
$q->execute([$_SESSION["user_id"]]);
$data = $q->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Pemeriksaan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="riwayat-page">
<main class="container">
    <h1>Riwayat Pemeriksaan</h1>

    <?php if (!$data): ?>
        <div class="card kosong">
            Belum ada riwayat pemeriksaan.
        </div>
    <?php else: ?>
        <?php foreach ($data as $r): ?>
        <div class="card">
            <p><b>Tanggal:</b>
                <?= htmlspecialchars($r["tanggal"]) ?></p>
            <p><b>Dokter:</b>
                <?= htmlspecialchars($r["dokter"]) ?></p>
            <p><b>Keluhan:</b>
                <?= htmlspecialchars($r["keluhan"] ?? "-") ?></p>
            <p><b>Diagnosis:</b>
                <?= htmlspecialchars($r["diagnosis"] ?? "-") ?></p>
            <p><b>Catatan:</b>
                <?= htmlspecialchars($r["catatan"] ?? "-") ?></p>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="../index.php">Kembali ke beranda</a>
</main>
</body>
</html>