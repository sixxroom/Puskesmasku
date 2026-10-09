<?php
session_start();
require_once "../koneksi.php";

// Pastikan pasien sudah login
if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "pasien"
) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Ambil transaksi obat milik pasien yang sedang login
$sql = "SELECT
            t.id,
            t.kode_pengambilan,
            t.tanggal,
            t.status,
            pm.diagnosis,
            a.nomor_antrean,
            d.nama AS nama_dokter
        FROM transaksi_obat t
        JOIN pemeriksaan pm ON t.pemeriksaan_id = pm.id
        JOIN antrean a ON pm.antrean_id = a.id
        JOIN pasien p ON a.pasien_id = p.id
        JOIN dokter d ON a.dokter_id = d.id
        WHERE p.user_id = ?
        ORDER BY t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION["user_id"]]);
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Pengambilan Obat</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="obat-page">

<header>
    <h2>Pengambilan Obat</h2>
</header>

<main>
    <a class="back" href="../index.php">&larr; Kembali ke Beranda</a>

    <h2>Cek Kode Pengambilan Obat</h2>
    <p>Periksa kode dan status obat sebelum mengambilnya di puskesmas.</p>

    <?php if (empty($transaksi)): ?>

        <div class="empty">
            <h3>Belum Ada Kode Obat</h3>
            <p>
                Kode pengambilan obat akan muncul setelah pemeriksaan
                dan transaksi obat dibuat oleh petugas.
            </p>
        </div>

    <?php else: ?>

        <?php foreach ($transaksi as $item): ?>

            <div class="card">
                <h3>Informasi Pengambilan</h3>

                <div class="code">
                    <small>KODE PENGAMBILAN OBAT</small>
                    <strong><?= e($item["kode_pengambilan"]) ?></strong>
                    <br>

                    <button
                        type="button"
                        onclick="salinKode('<?= e($item["kode_pengambilan"]) ?>')">
                        Salin Kode
                    </button>
                </div>

                <p>
                    Status:
                    <span class="status <?= $item["status"] === "Sudah Diambil" ? "selesai" : "" ?>">
                        <?= e($item["status"]) ?>
                    </span>
                </p>

                <div class="info">
                    <div>
                        Nomor antrean:
                        <?= e($item["nomor_antrean"]) ?>
                    </div>

                    <div>
                        Dokter:
                        <?= e($item["nama_dokter"]) ?>
                    </div>

                    <div>
                        Diagnosis:
                        <?= e($item["diagnosis"] ?: "Belum tersedia") ?>
                    </div>

                    <div>
                        Tanggal:
                        <?= e($item["tanggal"]) ?>
                    </div>
                </div>

                <hr>

                <h4>Daftar Obat</h4>

                <?php
                $stmtObat = $pdo->prepare(
                    "SELECT
                        o.nama_obat,
                        o.satuan,
                        det.jumlah,
                        det.harga_satuan,
                        det.aturan_pakai
                    FROM detail_obat det
                    JOIN obat o ON det.obat_id = o.id
                    WHERE det.transaksi_id = ?"
                );

                $stmtObat->execute([$item["id"]]);
                $daftarObat = $stmtObat->fetchAll(PDO::FETCH_ASSOC);

                $totalHarga = 0;
                ?>

                <?php if (empty($daftarObat)): ?>

                    <p>Belum ada detail obat.</p>

                <?php else: ?>

                    <?php foreach ($daftarObat as $obat): ?>

                        <?php
                        $subtotal = (float) $obat["harga_satuan"]
                                  * (int) $obat["jumlah"];

                        $totalHarga += $subtotal;
                        ?>

                        <div class="medicine">
                            <strong><?= e($obat["nama_obat"]) ?></strong>

                            <div>
                                Jumlah:
                                <?= e($obat["jumlah"]) ?>
                                <?= e($obat["satuan"]) ?>
                            </div>

                            <div>
                                Aturan pakai:
                                <?= e(
                                    $obat["aturan_pakai"]
                                    ?: "Silakan tanyakan kepada petugas"
                                ) ?>
                            </div>

                            <div>
                                Harga satuan:
                                Rp <?= number_format(
                                    (float) $obat["harga_satuan"],
                                    0,
                                    ",",
                                    "."
                                ) ?>
                            </div>

                            <div>
                                Subtotal:
                                <strong>
                                    Rp <?= number_format(
                                        $subtotal,
                                        0,
                                        ",",
                                        "."
                                    ) ?>
                                </strong>
                            </div>
                        </div>

                    <?php endforeach; ?>

                    <div class="medicine">
                        <strong>
                            Total Harga Semua Obat:
                            Rp <?= number_format(
                                $totalHarga,
                                0,
                                ",",
                                "."
                            ) ?>
                        </strong>
                    </div>

                <?php endif; ?>

                <?php if ($item["status"] === "Belum Diambil"): ?>

                    <p>
                        <small>
                            Tunjukkan kode ini kepada petugas saat
                            mengambil obat.
                        </small>
                    </p>

                <?php else: ?>

                    <p>
                        <small>Obat sudah ditandai telah diambil.</small>
                    </p>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>
</main>

    <script>
    function salinKode(kode) {
        navigator.clipboard.writeText(kode)
            .then(() => {
                alert('Kode berhasil disalin.');
            })
            .catch(() => {
                alert('Gagal menyalin kode. Silakan salin manual.');
            });
    }
    </script>

</body>
</html>