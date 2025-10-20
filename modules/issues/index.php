<?php
require_once '../php/config.php';

// Require user to be logged in
requireLogin();

$user_role = $_SESSION['role'];

// Redirect to appropriate dashboard based on role
switch ($user_role) {
    case 'student':
        redirectTo('student-dashboard.php');
        break;
    case 'lecturer':
        redirectTo('lecturer-dashboard.php');
        break;
    case 'staff':
    case 'admin':
        redirectTo('staff-dashboard.php');
        break;
    default:
        redirectTo('../dashboard.php');
        break;
}
?>
