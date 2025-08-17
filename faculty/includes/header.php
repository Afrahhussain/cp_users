<?php
// faculty/includes/header.php
session_start();
require_once __DIR__ . '/../../config.php';

// Faculty guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'faculty') {
    header("Location: ../index.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Faculty • College Portal</title>

  <!-- Styles -->
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>

  <!-- Scripts (note: script.js, not scripts.js) -->
  <script defer src="assets/script.js"></script>
</head>
<body>
  <header class="topbar">
    <div class="topbar-left">
      <button id="hamburger" class="hamburger" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
      <a class="brand" href="dashboard.php">College Portal</a>
    </div>
    <div class="topbar-right">
      <span class="role-pill">Faculty</span>
      <a class="logout" href="../logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </header>
