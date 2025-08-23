<?php
// dashboard_redirect.php
session_start();

// If not logged in, back to login
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];

switch ($role) {
    case 'admin':
        header("Location: admin/dashboard.php");
        exit();
    case 'hod':
        header("Location: hod/dashboard.php");
        exit();
    case 'faculty':
        header("Location: faculty/dashboard.php");
        exit();
    case 'class_incharge':
        header("Location: class_incharge/dashboard.php");
        exit();
    case 'student':
        header("Location: student/dashboard.php");
        exit();
    default:
        // unknown role → logout
        session_destroy();
        header("Location: index.php");
        exit();
}
