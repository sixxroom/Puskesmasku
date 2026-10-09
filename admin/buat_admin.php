<?php
require_once "../koneksi.php";

// Ganti username dan password sebelum dijalankan.
$nama = "Administrator Puskesmas";
$username = "admin";
$password = "Admin12345";

$cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$cek->execute([$username]);

if ($cek->fetch()) {
    exit("Username admin sudah tersedia. Hapus file ini jika sudah selesai.");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (nama, username, password, role)
        VALUES (?, ?, ?, 'admin')";

$stmt = $pdo->prepare($sql);
$stmt->execute([$nama, $username, $hash]);

echo "Akun admin berhasil dibuat. Silakan login.";
?>