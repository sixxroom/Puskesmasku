<?php
session_start();
require_once "koneksi.php";

// Pastikan yang membuka halaman adalah pasien.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "pasien"
) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user_id"];
$pesan = "";
$error = "";

// Proses penyimpanan perubahan profil.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama"] ?? "");
    $tanggal = $_POST["tanggal_lahir"] ?? "";
    $jk = $_POST["jenis_kelamin"] ?? "";
    $alamat = trim($_POST["alamat"] ?? "");
    $noHp = trim($_POST["no_hp"] ?? "");

    $tanggalValid = DateTime::createFromFormat("!Y-m-d", $tanggal);
    $tanggalValid =
        $tanggalValid && $tanggalValid->format("Y-m-d") === $tanggal;

    if (
        $nama === "" ||
        !$tanggalValid ||
        $tanggal > date("Y-m-d") ||
        !in_array($jk, ["Laki-laki", "Perempuan"], true) ||
        $alamat === "" ||
        $noHp === ""
    ) {
        $error = "Periksa kembali data yang kamu masukkan.";
    } else {
        try {
            $pdo->beginTransaction();

            // Perbarui data pasien.
            $sql = "UPDATE pasien
                    SET nama = ?, tanggal_lahir = ?,
                        jenis_kelamin = ?, alamat = ?, no_hp = ?
                    WHERE user_id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nama, $tanggal, $jk, $alamat, $noHp, $userId
            ]);

            // Perbarui nama akun agar dashboard ikut berubah.
            $sqlUser = "UPDATE users SET nama = ? WHERE id = ?";
            $stmtUser = $pdo->prepare($sqlUser);
            $stmtUser->execute([$nama, $userId]);

            $pdo->commit();

            $_SESSION["nama"] = $nama;

            // Hindari pengiriman ulang formulir saat halaman dimuat ulang.
            header("Location: profil.php?simpan=1");
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Profil gagal disimpan. Silakan coba lagi.";
        }
    }
}

// Ambil data terbaru dari database.
$sql = "SELECT nama, nik, tanggal_lahir, jenis_kelamin, alamat, no_hp
        FROM pasien
        WHERE user_id = ?
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$pasien = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pasien) {
    exit("Data pasien tidak ditemukan. Silakan hubungi administrator.");
}

if (isset($_GET["simpan"])) {
    $pesan = "Profil berhasil diperbarui!";
}

function e($nilai) {
    return htmlspecialchars((string) $nilai, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pasien</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="profil-page">
    <main class="profil-container">
        <div class="profil-card">
            <h1>Profil Saya</h1>
            <p>Perbarui data diri kamu di bawah ini.</p>

            <?php if ($pesan !== ""): ?>
                <p class="pesan-sukses"><?= e($pesan) ?></p>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <p class="pesan-error"><?= e($error) ?></p>
            <?php endif; ?>

            <form method="POST">
                <label>NIK</label>
                <div class="nik-info"><?= e($pasien["nik"]) ?></div>
                <small>NIK tidak dapat diubah dari halaman profil.</small>

                <label for="nama">Nama lengkap</label>
                <input
                    type="text"
                    id="nama"
                    name="nama"
                    maxlength="100"
                    value="<?= e($_POST["nama"] ?? $pasien["nama"]) ?>"
                    required
                >

                <label for="tanggal_lahir">Tanggal lahir</label>
                <input
                    type="date"
                    id="tanggal_lahir"
                    name="tanggal_lahir"
                    max="<?= date("Y-m-d") ?>"
                    value="<?= e($_POST["tanggal_lahir"] ?? $pasien["tanggal_lahir"]) ?>"
                    required
                >

                <label for="jenis_kelamin">Jenis kelamin</label>
                <select id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="">Pilih jenis kelamin</option>
                    <?php
                    $pilihanJK = ["Laki-laki", "Perempuan"];
                    $jkAktif = $_POST["jenis_kelamin"] ?? $pasien["jenis_kelamin"];
                    foreach ($pilihanJK as $pilihan):
                    ?>
                        <option
                            value="<?= e($pilihan) ?>"
                            <?= $jkAktif === $pilihan ? "selected" : "" ?>
                        >
                            <?= e($pilihan) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="alamat">Alamat</label>
                <textarea
                    id="alamat"
                    name="alamat"
                    maxlength="500"
                    required
                ><?= e($_POST["alamat"] ?? $pasien["alamat"]) ?></textarea>

                <label for="no_hp">Nomor HP</label>
                <input
                    type="tel"
                    id="no_hp"
                    name="no_hp"
                    maxlength="20"
                    value="<?= e($_POST["no_hp"] ?? $pasien["no_hp"]) ?>"
                    required
                >

                <div class="aksi">
                    <button type="submit" class="button">
                        Simpan Perubahan
                    </button>
                    <a href="index.php">Kembali ke Beranda</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>