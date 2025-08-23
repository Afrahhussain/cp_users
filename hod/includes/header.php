<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "../config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: ../index.php");
    exit();
}

$hod_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, department FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $hod_id);
$stmt->execute();
$res = $stmt->get_result();
$hod = $res->fetch_assoc();
$hod_name = $hod['full_name'] ?? "HOD";
$dept = $hod['department'] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>HOD Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .sidebar {
      width: 240px;
      background: linear-gradient(to bottom, #1e3a8a, #0f766e);
      color: #fff;
      min-height: 100vh;
      transition: width 0.3s ease;
      overflow: hidden;
      position: fixed;
      top: 0; left: 0;
    }
    .sidebar.collapsed { width: 60px; }
    .sidebar.collapsed .label,
    .sidebar.collapsed .logo-text { display: none; }
    .sidebar a {
      display: flex; align-items: center;
      padding: 10px 16px; color: #fff; text-decoration: none;
      transition: background 0.2s;
    }
    .sidebar a:hover { background: rgba(255,255,255,0.1); }
    .sidebar-toggle { font-size: 22px; cursor: pointer; background: none; border: none; color: #1e3a8a; }
    .main { margin-left: 240px; padding: 20px; transition: margin-left 0.3s ease; }
    .sidebar.collapsed ~ .main { margin-left: 60px; }
  </style>
</head>
<body class="bg-gray-100">
