
<?php
$host = "localhost";
$dbname = "puskesmas_db";
$username = "root";
$password = ""; // Default XAMPP, jika belum diubah

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit("Koneksi database gagal. Periksa konfigurasi MySQL.");
}
?>