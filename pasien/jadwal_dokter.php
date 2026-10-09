<?php
session_start();
require_once "../koneksi.php";

// Cek login pasien
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "pasien"
) {
    header("Location: ../login.php");
    exit;
}

// Ambil jadwal dokter dari database
$sql = "SELECT
            d.nama AS nama_dokter,
            d.spesialisasi,
            d.no_hp,
            j.hari,
            j.jam_mulai,
            j.jam_selesai
        FROM dokter d
        INNER JOIN jadwal_dokter j
            ON d.id = j.dokter_id
        ORDER BY
            FIELD(
                j.hari,
                'Senin', 'Selasa', 'Rabu', 'Kamis',
                'Jumat', 'Sabtu', 'Minggu'
            ),
            j.jam_mulai,
            d.nama";

$stmt = $pdo->query($sql);
$jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($nilai) {
    return htmlspecialchars(
        (string) $nilai,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Dokter - Puskesmas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="jadwal-page">
<div class="container">

    <h1>Jadwal Dokter</h1>

    <p class="deskripsi">
        Lihat hari dan jam praktik dokter sebelum mendaftar antrean.
    </p>

    <p>
        <a href="../index.php" class="tombol">
            &larr; Kembali ke Beranda
        </a>
    </p>

    <div class="panel">
        <?php if (count($jadwal) > 0): ?>

            <div class="tabel-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Dokter</th>
                            <th>Spesialisasi</th>
                            <th>Hari</th>
                            <th>Jam Praktik</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($jadwal as $i => $j): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>

                                <td>
                                    <?= e($j["nama_dokter"]) ?>
                                </td>

                                <td>
                                    <?= e($j["spesialisasi"]) ?>
                                </td>

                                <td>
                                    <?= e($j["hari"]) ?>
                                </td>

                                <td>
                                    <?= e(substr($j["jam_mulai"], 0, 5)) ?>
                                    -
                                    <?= e(substr($j["jam_selesai"], 0, 5)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <p class="kosong">
                Belum ada jadwal dokter yang tersedia.
            </p>

        <?php endif; ?>
    </div>

</div>
</body>
</html>