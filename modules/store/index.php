<?php
require_once '../php/config.php';

// Require user to be logged in
requireLogin();

$user_role = $_SESSION['role'];

// Route users based on their role
switch ($user_role) {
    case 'student':
        header('Location: student-dashboard.php');
        break;
    case 'staff':
        header('Location: staff-dashboard.php');
        break;
    case 'admin':
        header('Location: admin-dashboard.php');
        break;
    case 'lecturer':
        // Lecturers can view and request items like students
        header('Location: student-dashboard.php');
        break;
    default:
        header('Location: ../dashboard.php');
        break;
}
exit;
?>
