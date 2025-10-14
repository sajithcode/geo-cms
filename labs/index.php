<?php
define('BASE_PATH', dirname(__DIR__));
require_once '../php/config.php';

// Require user to be logged in
requireLogin();

$user_role = $_SESSION['role'];

// Route to appropriate dashboard based on role
switch ($user_role) {
    case 'admin':
        include 'admin-dashboard.php';
        break;
    case 'staff':
        include 'staff-dashboard.php';
        break;
    case 'lecturer':
        include 'lecturer-dashboard.php';
        break;
    case 'student':
        include 'student-dashboard.php';
        break;
    default:
        // Fallback - should never reach here
        redirectTo('../dashboard.php');
}
?>
