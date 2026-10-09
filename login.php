<?php
session_start();
require_once "koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identitas = trim($_POST["identitas"] ?? "");
    $password = $_POST["password"] ?? "";

    $sql = "SELECT id, nama, username, password, role
            FROM users
            WHERE username = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identitas]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["nama"] = $user["nama"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "admin") {
            header("Location: admin/index.php");
        } elseif ($user["role"] === "pasien") {
            header("Location: index.php");
        } else {
            session_unset();
            session_destroy();
            header("Location: login.php");
        }
        exit;
    }

    $error = "NIK/username atau password salah.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Puskesmas</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="container container-sm">
        <div class="card auth-card">
            <h1>Login</h1>
            <p class="auth-desc">Masuk ke layanan Puskesmas.</p>

            <?php if ($error !== ""): ?>
                <p class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <label>NIK pasien / Nama pasien</label>
                <input type="text" name="identitas" required
                    autocomplete="username"
                    value="<?= htmlspecialchars($_POST["identitas"] ?? "") ?>">

                <label>Password</label>
                <input type="password" name="password" required
                    autocomplete="current-password">

                <button type="submit" class="button button-block">Login</button>
            </form>

            <p class="auth-footer">Belum punya akun pasien? <a href="daftar.php">Daftar di sini</a></p>
        </div>
    </main>
</body>
</html>