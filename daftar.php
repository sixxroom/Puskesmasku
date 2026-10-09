<?php
session_start();
require_once "koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama"] ?? "");
    $nik = trim($_POST["nik"] ?? "");
    $tanggal = $_POST["tanggal_lahir"] ?? "";
    $jk = $_POST["jenis_kelamin"] ?? "";
    $alamat = trim($_POST["alamat"] ?? "");
    $no_hp = trim($_POST["no_hp"] ?? "");
    $password = $_POST["password"] ?? "";

    if (
        $nama === "" || !preg_match('/^[0-9]{16}$/', $nik) ||
        $tanggal === "" || !in_array($jk, ["Laki-laki", "Perempuan"], true) ||
        $alamat === "" || $no_hp === "" || strlen($password) < 8
    ) {
        $error = "Lengkapi semua data. NIK harus 16 digit dan password minimal 8 karakter.";
    } else {
        $cek = $pdo->prepare(
            "SELECT id FROM pasien WHERE nik = ?"
        );
        $cek->execute([$nik]);

        if ($cek->fetch()) {
            $error = "NIK sudah terdaftar. Silakan login.";
        } else {
            try {
                $pdo->beginTransaction();

                // NIK digunakan sebagai identitas login pasien.
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $sqlUser = "INSERT INTO users (nama, username, password, role)
                            VALUES (?, ?, ?, 'pasien')";
                $stmtUser = $pdo->prepare($sqlUser);
                $stmtUser->execute([$nama, $nik, $hash]);

                $userId = $pdo->lastInsertId();

                $sqlPasien = "INSERT INTO pasien
                    (nik, nama, tanggal_lahir, jenis_kelamin, alamat, no_hp, user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

                $stmtPasien = $pdo->prepare($sqlPasien);
                $stmtPasien->execute([
                    $nik, $nama, $tanggal, $jk,
                    $alamat, $no_hp, $userId
                ]);

                $pdo->commit();

                $_SESSION["user_id"] = $userId;
                $_SESSION["nama"] = $nama;
                $_SESSION["role"] = "pasien";

                header("Location: index.php");
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Pendaftaran gagal. Periksa kembali data atau database.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pasien</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="container">
        <div class="card">
            <h1>Daftar Pasien</h1>
            <p>Isi data diri untuk membuat akun Puskesmas.</p>

            <?php if ($error !== ""): ?>
                <p style="color:red;">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <label>Nama lengkap</label>
                <input type="text" name="nama" required
                    value="<?= htmlspecialchars($_POST["nama"] ?? "") ?>">

                <label>NIK (16 digit)</label>
                <input type="text" name="nik" inputmode="numeric"
                    pattern="[0-9]{16}" maxlength="16" required
                    value="<?= htmlspecialchars($_POST["nik"] ?? "") ?>">

                <label>Tanggal lahir</label>
                <input type="date" name="tanggal_lahir" required
                    value="<?= htmlspecialchars($_POST["tanggal_lahir"] ?? "") ?>">

                <label>Jenis kelamin</label>
                <select name="jenis_kelamin" required>
                    <option value="">Pilih jenis kelamin</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>

                <label>Alamat</label>
                <textarea name="alamat" required><?= htmlspecialchars($_POST["alamat"] ?? "") ?></textarea>

                <label>Nomor HP</label>
                <input type="tel" name="no_hp" required
                    value="<?= htmlspecialchars($_POST["no_hp"] ?? "") ?>">

                <label>Password (minimal 8 karakter)</label>
                <input type="password" name="password" minlength="8" required>

                <button type="submit" class="button">Daftar</button>
            </form>

            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </div>
    </main>
</body>
</html>