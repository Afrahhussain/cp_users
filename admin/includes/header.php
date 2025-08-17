<?php
// admin/includes/header.php
session_start();

// Adjust path to your config if needed
require_once __DIR__ . '/../../config.php';

// Guard - ensure admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin • College Portal</title>

  <!-- professional palette -->
  <link rel="stylesheet" href="assets/styles.css">
  <!-- font awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous"/>

  <script defer src="assets/scripts.js"></script>
</head>
<body>
  <header class="topbar">
    <div class="topbar-left">
      <button id="hamburger" class="hamburger" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
      <a class="brand" href="dashboard.php">College Portal</a>
    </div>
    <div class="topbar-right">
      <span class="role-pill">Admin</span>
      <a class="logout" href="../logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </header>
