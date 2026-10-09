<?php
session_start();
require_once "../koneksi.php";

// Pastikan pasien sudah login.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "pasien"
) {
    header("Location: ../login.php");
    exit;
}

$pesan = "";
$jenisPesan = "";

function e($nilai) {
    return htmlspecialchars((string) $nilai, ENT_QUOTES, "UTF-8");
}

// Ambil data pasien berdasarkan akun yang sedang login.
$stmt = $pdo->prepare(
    "SELECT id FROM pasien WHERE user_id = ? LIMIT 1"
);
$stmt->execute([$_SESSION["user_id"]]);
$pasien = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pasien) {
    exit("Data pasien tidak ditemukan. Silakan periksa data akun pasien.");
}

// Ambil daftar dokter.
$dokter = $pdo->query(
    "SELECT id, nama, spesialisasi FROM dokter ORDER BY nama"
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dokterId = filter_input(INPUT_POST, "dokter_id", FILTER_VALIDATE_INT);
    $tanggal = $_POST["tanggal"] ?? "";

    $tanggalObj = DateTime::createFromFormat("!Y-m-d", $tanggal);
    $tanggalValid = $tanggalObj &&
        $tanggalObj->format("Y-m-d") === $tanggal &&
        $tanggal >= date("Y-m-d");

    if (!$dokterId || !$tanggalValid) {
        $pesan = "Pilih dokter dan tanggal pemeriksaan yang valid.";
        $jenisPesan = "error";
    } else {
        // Pastikan dokter benar-benar tersedia.
        $stmt = $pdo->prepare(
            "SELECT id FROM dokter WHERE id = ?"
        );
        $stmt->execute([$dokterId]);
        $dokterAda = $stmt->fetchColumn();

        if (!$dokterAda) {
            $pesan = "Dokter yang dipilih tidak ditemukan.";
            $jenisPesan = "error";
        } else {
            // Cocokkan tanggal dengan jadwal praktik dokter.
            $hariIndonesia = [
                "Minggu", "Senin", "Selasa", "Rabu",
                "Kamis", "Jumat", "Sabtu"
            ];
            $hari = $hariIndonesia[(int) $tanggalObj->format("w")];

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM jadwal_dokter
                 WHERE dokter_id = ? AND hari = ?"
            );
            $stmt->execute([$dokterId, $hari]);

            if (!$stmt->fetchColumn()) {
                $pesan = "Dokter tidak memiliki jadwal praktik pada hari tersebut.";
                $jenisPesan = "error";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Hitung nomor antrean untuk dokter dan tanggal tersebut.
                    $stmt = $pdo->prepare(
                        "SELECT COALESCE(MAX(nomor_antrean), 0) + 1
                         FROM antrean
                         WHERE dokter_id = ? AND tanggal = ?"
                    );
                    $stmt->execute([$dokterId, $tanggal]);
                    $nomor = $stmt->fetchColumn();

                    // Status ditulis secara eksplisit agar sesuai dengan
                    // kolom status yang wajib diisi pada database.
                    $stmt = $pdo->prepare(
                        "INSERT INTO antrean
                         (pasien_id, dokter_id, tanggal, nomor_antrean, status)
                         VALUES (?, ?, ?, ?, 'Menunggu')"
                    );

                    $stmt->execute([
                        $pasien["id"],
                        $dokterId,
                        $tanggal,
                        $nomor
                    ]);

                    $pdo->commit();

                    $pesan = "Pendaftaran berhasil! Nomor antrean kamu: " .
                        $nomor . ". Tanggal: " . $tanggal .
                        ". Status: Menunggu.";
                    $jenisPesan = "success";
                } catch (PDOException $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    error_log("Gagal membuat antrean: " . $ex->getMessage());
                    $pesan = "Antrean gagal disimpan. Periksa struktur tabel antrean dan log error PHP.";
                    $jenisPesan = "error";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Antrean</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="antrean-page">
    <main class="antrean-container">
        <h1>Daftar Antrean</h1>
        <p>Pilih dokter dan tanggal pemeriksaan.</p>

        <?php if ($pesan !== ""): ?>
            <p class="<?= e($jenisPesan) ?>"><?= e($pesan) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="dokter_id">Pilih dokter</label>
            <select id="dokter_id" name="dokter_id" required>
                <option value="">Pilih dokter</option>
                <?php foreach ($dokter as $d): ?>
                    <option
                        value="<?= e($d["id"]) ?>"
                        <?= (string) ($_POST["dokter_id"] ?? "") === (string) $d["id"] ? "selected" : "" ?>
                    >
                        <?= e($d["nama"]) ?> -
                        <?= e($d["spesialisasi"] ?? "") ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="tanggal">Tanggal pemeriksaan</label>
            <input
                type="date"
                id="tanggal"
                name="tanggal"
                min="<?= date("Y-m-d") ?>"
                value="<?= e($_POST["tanggal"] ?? "") ?>"
                required
            >

            <button type="submit" class="button">Ambil Antrean</button>
        </form>

        <a class="kembali" href="../index.php">Kembali ke beranda</a>
    </main>
</body>
</html>