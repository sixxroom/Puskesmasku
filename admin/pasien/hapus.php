<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require "../../config/db.php";
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int) $_GET['id'];

try {
    $stmt = $conn->prepare("DELETE FROM pasien WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['flash_success'] = "Data pasien berhasil dihapus.";
    header("Location: index.php");
    exit();
} catch (PDOException $e) {
    $_SESSION['flash_error'] = "Error: " . $e->getMessage();
    header("Location: index.php");
    exit();
}
