<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo(BASE_URL . 'login.php');
}

// Log logout activity
logActivity($_SESSION['user_id'], 'logout', "User logged out", $_SERVER['REMOTE_ADDR']);

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    
    // Clear remember token from database
    try {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Set success message for login page
session_start();
$_SESSION['success'] = 'You have been logged out successfully.';

// Redirect to login page
redirectTo(BASE_URL . 'login.php');
?>