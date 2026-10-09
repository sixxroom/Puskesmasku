<?php
require "../config/db.php";

// Data pasien
$username_pasien = "pasien1";
$password_pasien = "123456";
$fullname_pasien = "Nama Pasien";
$role_pasien = "pasien";

// Hash password pasien
$hash_pasien = password_hash($password_pasien, PASSWORD_DEFAULT);

// Masukkan ke database
$sql_pasien = "INSERT INTO users (username, password, role, fullname)
               VALUES (?, ?, ?, ?)";
$stmt_pasien = $conn->prepare($sql_pasien);

try {
    $stmt_pasien->execute([$username_pasien, $hash_pasien, $role_pasien, $fullname_pasien]);
    echo "Pasien berhasil dibuat!<br>";
    echo "Username: $username_pasien<br>Password: $password_pasien<br>";
} catch (PDOException $e) {
    echo "Error pasien: " . $e->getMessage() . "<br>";
}

// Data admin
$username_admin = "admin";
$password_admin = "password123";
$fullname_admin = "Administrator";
$role_admin = "admin";

// Hash password admin
$hash_admin = password_hash($password_admin, PASSWORD_DEFAULT);

$sql_admin = "INSERT INTO users (username, password, role, fullname)
              VALUES (?, ?, ?, ?)";
$stmt_admin = $conn->prepare($sql_admin);

try {
    $stmt_admin->execute([$username_admin, $hash_admin, $role_admin, $fullname_admin]);
    echo "Admin berhasil dibuat!<br>";
    echo "Username: $username_admin<br>Password: $password_admin<br>";
} catch (PDOException $e) {
    echo "Error admin: " . $e->getMessage() . "<br>";
}
 