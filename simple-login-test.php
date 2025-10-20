<?php
/**
 * Simple Login Test - No AJAX, Direct Form Submission
 * Use this to test if login works without JavaScript
 */

session_start();
require_once 'php/config.php';

// If already logged in, show success
if (isLoggedIn()) {
    echo "<h1>✅ Already Logged In!</h1>";
    echo "<p>User: " . htmlspecialchars($_SESSION['user_name']) . "</p>";
    echo "<p>Role: " . htmlspecialchars($_SESSION['role']) . "</p>";
    echo "<p><a href='php/logout.php'>Logout</a> | <a href='dashboard.php'>Dashboard</a></p>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;'>";
    echo "<h3>Debug Information:</h3>";
    echo "Login ID: " . htmlspecialchars($login_id) . "<br>";
    echo "Role: " . htmlspecialchars($role) . "<br>";
    echo "Password Length: " . strlen($password) . "<br><br>";
    
    try {
        // Find user
        $stmt = $pdo->prepare("
            SELECT id, name, email, user_id, password, role, is_active 
            FROM users 
            WHERE (email = ? OR user_id = ?) AND role = ?
        ");
        $stmt->execute([$login_id, $login_id, $role]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo "❌ <strong>Error:</strong> User not found with ID '$login_id' and role '$role'<br><br>";
            echo "<strong>Tip:</strong> Check if this user exists in database and role matches.<br>";
        } else {
            echo "✅ User found: " . htmlspecialchars($user['name']) . "<br>";
            echo "Email: " . htmlspecialchars($user['email']) . "<br>";
            echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br><br>";
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                echo "✅ <strong>Password correct!</strong><br><br>";
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_identity'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                echo "✅ <strong>Login successful!</strong><br><br>";
                echo "<a href='dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a>";
                exit;
            } else {
                echo "❌ <strong>Error:</strong> Incorrect password<br><br>";
                echo "<strong>Tip:</strong> Default password for test accounts is: <code>password</code><br>";
            }
        }
    } catch (PDOException $e) {
        echo "❌ <strong>Database Error:</strong> " . $e->getMessage() . "<br>";
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .hint {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #007bff;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>🔐 Simple Login Test</h1>
        <p>This is a simplified login form to test without JavaScript/AJAX.</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="login_id">User ID or Email:</label>
                <input type="text" id="login_id" name="login_id" required 
                       placeholder="e.g., ADMIN001" value="ADMIN001">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter password" value="password">
            </div>
            
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="">Select role</option>
                    <option value="admin" selected>Admin</option>
                    <option value="lecturer">Lecturer</option>
                    <option value="staff">Staff</option>
                    <option value="student">Student</option>
                </select>
            </div>
            
            <button type="submit">🚀 Test Login</button>
        </form>
        
        <div class="hint">
            <strong>💡 Default Test Accounts:</strong><br>
            • User ID: <code>ADMIN001</code>, Password: <code>password</code>, Role: Admin<br>
            • User ID: <code>LEC001</code>, Password: <code>password</code>, Role: Lecturer<br>
            • User ID: <code>STAFF001</code>, Password: <code>password</code>, Role: Staff<br>
            • User ID: <code>STU001</code>, Password: <code>password</code>, Role: Student
        </div>
        
        <p style="margin-top: 20px; text-align: center;">
            <a href="login.php">← Back to Main Login</a> | 
            <a href="test-connection.php">Database Test</a>
        </p>
    </div>
</body>
</html>
