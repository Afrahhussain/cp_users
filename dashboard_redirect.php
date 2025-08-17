<?php
session_start();

if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];

switch ($role) {
    case 'admin':
        header("Location: admin/dashboard.php");
        break;
    case 'hod':
        header("Location: hod/dashboard.php");
        break;
    case 'faculty':
        header("Location: faculty/dashboard.php");
        break;
    case 'class_incharge':
        header("Location: class_incharge/dashboard.php");
        break;
    case 'student':
        header("Location: student/dashboard.php");
        break;
    default:
        header("Location: index.php");
}
exit();
?>
