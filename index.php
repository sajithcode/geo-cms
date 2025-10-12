<?php
require_once 'php/config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectTo('dashboard.php');
} else {
    // Redirect to login page
    redirectTo('login.php');
}
?>