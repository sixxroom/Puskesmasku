<?php
session_start();

// Jika sudah login dan tidak ada ?force=1
if (isset($_SESSION["user_id"]) && !isset($_GET['force'])) {

    if ($_SESSION["role"] === "admin") {
        header("Location: ../admin/dashboard.php");
        exit();
    }

    if ($_SESSION["role"] === "pasien") {
        header("Location: ../pasien/beranda.php");
        exit();
    }
}

require "../config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["role"] = $user["role"];
        $_SESSION["fullname"] = $user["fullname"];

        if ($user["role"] === "admin") {
            header("Location: ../admin/dashboard.php");
            exit();
        }

        if ($user["role"] === "pasien") {
            header("Location: ../pasien/beranda.php");
            exit();
        }
    }

    $message = "Username atau password salah!";
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Puskesmas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            display: flex;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-left h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .login-left p {
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        .medical-icon {
            font-size: 80px;
            margin-bottom: 30px;
        }

        .login-right {
            flex: 1;
            padding: 60px 40px;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-header h2 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.2rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
            font-size: 0.9rem;
            animation: shake 0.5s;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-10px);
            }

            75% {
                transform: translateX(10px);
            }
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: #999;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-left {
                padding: 40px 30px;
            }

            .login-left h1 {
                font-size: 2rem;
            }

            .login-right {
                padding: 40px 30px;
            }

            .medical-icon {
                font-size: 60px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-left">
            <div class="medical-icon">🏥</div>
            <h1>Informatika Kesehhatan Puskesmas</h1>
            <p>Selamat datang di portal login sistem informasi manajemen Puskesmas. Silakan masukkan kredensial Anda untuk mengakses sistem.</p>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Masuk ke Akun</h2>
                <p>Masukkan username dan password Anda</p>
            </div>

            <?php if ($message != ""): ?>
                <div class="error-message">
                    ⚠️ <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text"
                            id="username"
                            name="username"
                            placeholder="Masukkan username..."
                            required
                            autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password..."
                            required
                            autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-login">Masuk</button>
            </form>

            <p class="footer-text">
                © 2025 Informatika Kesehatan - Puskesmas. Prosama.</p>
            </p>
        </div>
    </div>
</body>

</html>