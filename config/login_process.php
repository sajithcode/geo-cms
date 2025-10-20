<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$login_id = sanitizeInput($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';
$role = sanitizeInput($_POST['role'] ?? '');
$remember_me = isset($_POST['remember_me']);

// Validate input
if (empty($login_id) || empty($password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    // Find user by email or user_id
    $stmt = $pdo->prepare("
        SELECT id, name, email, user_id, password, role, is_active 
        FROM users 
        WHERE (email = ? OR user_id = ?) AND role = ? AND is_active = TRUE
    ");
    $stmt->execute([$login_id, $login_id, $role]);
    $user = $stmt->fetch();

    if (!$user) {
        // Log failed login attempt
        logActivity(null, 'login_failed', "Failed login attempt for: $login_id as $role", $_SERVER['REMOTE_ADDR']);
        
        echo json_encode(['success' => false, 'message' => 'Invalid credentials or role mismatch']);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Log failed login attempt
        logActivity($user['id'], 'login_failed', "Incorrect password for user: {$user['user_id']}", $_SERVER['REMOTE_ADDR']);
        
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_identity'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    // Set remember me cookie if requested
    if ($remember_me) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
        
        // Store token in database - Note: remember_token column needs to be added to users table
        // For now, we'll just set the cookie without database storage
        // $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        // $stmt->execute([$token, $user['id']]);
    }
    
    // Create welcome notification
    createNotification(
        $user['id'], 
        'Welcome back!', 
        "You have successfully logged in to the Geo CMS system.", 
        'success'
    );
    
    // Log successful login
    logActivity($user['id'], 'login_success', "User logged in successfully", $_SERVER['REMOTE_ADDR']);
    
    // Determine redirect URL based on role
    $redirect_urls = [
        'admin' => 'dashboard.php',
        'lecturer' => 'dashboard.php',
        'staff' => 'dashboard.php',
        'student' => 'dashboard.php'
    ];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful',
        'redirect' => $redirect_urls[$user['role']] ?? 'dashboard.php',
        'user' => [
            'name' => $user['name'],
            'role' => $user['role'],
            'user_id' => $user['user_id']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>