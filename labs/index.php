<?php
require_once '../php/config.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$page_title = 'Labs Management';

// Redirect based on user role
switch ($user_role) {
    case 'admin':
    case 'staff':
        header('Location: admin-dashboard.php');
        exit;
    case 'lecturer':
        header('Location: lecturer-dashboard.php');
        exit;
    case 'student':
        header('Location: student-dashboard.php');
        exit;
    default:
        redirectTo('../index.php');
}
?>