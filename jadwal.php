
<?php
require_once "../koneksi.php";

$sql = "
    SELECT d.nama, d.spesialisasi,
           j.hari, j.jam_mulai, j.jam_selesai
    FROM dokter d
    JOIN jadwal_dokter j ON d.id = j.dokter_id
    ORDER BY j.id
";

$jadwal = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Dokter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="logo">Puskesmas Sehat</div>
    <a href="../index.php">Kembali ke Beranda</a>
</nav>

<div class="container">
    <h1>Jadwal Praktik Dokter</h1>

    <table>
        <tr>
            <th>Nama Dokter</th>
            <th>Spesialisasi</th>
            <th>Hari</th>
            <th>Jam Praktik</th>
        </tr>

        <?php foreach ($jadwal as $j): ?>
        <tr>
            <td><?= htmlspecialchars($j['nama']) ?></td>
            <td><?= htmlspecialchars($j['spesialisasi'] ?? '') ?></td>
            <td><?= htmlspecialchars($j['hari']) ?></td>
            <td>
                <?= htmlspecialchars(substr($j['jam_mulai'], 0, 5)) ?>
                -
                <?= htmlspecialchars(substr($j['jam_selesai'], 0, 5)) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>