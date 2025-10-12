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

$name = sanitizeInput($_POST['name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$user_id = sanitizeInput($_POST['user_id'] ?? '');
$role = sanitizeInput($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate input
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name must be at least 2 characters long';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address';
}

if (empty($user_id)) {
    $errors[] = 'User ID is required';
}

if (!in_array($role, ['student', 'lecturer', 'staff'])) {
    $errors[] = 'Invalid role selected. Admin accounts must be created by existing admins.';
}

if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
    $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

// Validate User ID format based on role
$user_id_patterns = [
    'student' => '/^STU\d{3,}$/',
    'lecturer' => '/^LEC\d{3,}$/',
    'staff' => '/^STAFF\d{3,}$/'
];

if (!preg_match($user_id_patterns[$role], $user_id)) {
    $role_formats = [
        'student' => 'STU001',
        'lecturer' => 'LEC001',
        'staff' => 'STAFF001'
    ];
    $errors[] = "User ID format for {$role} should be like: {$role_formats[$role]}";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email address is already registered']);
        exit;
    }
    
    // Check if user ID already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User ID is already taken']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, user_id, password, role, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$name, $email, $user_id, $hashed_password, $role]);
    $new_user_id = $pdo->lastInsertId();
    
    // Create welcome notification
    createNotification(
        $new_user_id, 
        'Welcome to Geo CMS!', 
        "Your account has been created successfully. You can now access the Faculty of Geomatics management system.", 
        'success'
    );
    
    // Log registration
    logActivity($new_user_id, 'user_registered', "New {$role} account created: {$user_id}", $_SERVER['REMOTE_ADDR']);
    
    // Send welcome email (you can implement this later)
    // sendWelcomeEmail($email, $name, $user_id, $role);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful! You can now log in with your credentials.',
        'user' => [
            'name' => $name,
            'email' => $email,
            'user_id' => $user_id,
            'role' => $role
        ]
    ]);

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    
    // Check for duplicate entry error
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Email or User ID already exists']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred during registration']);
    }
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred during registration']);
}
?>