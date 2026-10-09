<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }
require "../../config/db.php";
$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("DELETE FROM dokter WHERE id = ?");
$stmt->execute([$id]);
header("Location: index.php");
exit;
