<?php
/**
 * Password Hash Verifier and Generator
 * Use this to verify password hashes and generate new ones
 */

$test_password = 'password';
$database_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "<h1>Password Verification Tool</h1>";

echo "<h2>Test 1: Verify Database Hash</h2>";
echo "Test Password: <strong>$test_password</strong><br>";
echo "Database Hash: <code>$database_hash</code><br><br>";

if (password_verify($test_password, $database_hash)) {
    echo "✅ <strong style='color: green;'>SUCCESS!</strong> Password 'password' matches the database hash.<br>";
    echo "The default accounts should work with password: <strong>password</strong><br>";
} else {
    echo "❌ <strong style='color: red;'>FAILED!</strong> Password 'password' does NOT match the database hash.<br>";
    echo "This means the database might have different password hashes.<br>";
}

echo "<hr>";

echo "<h2>Test 2: Check Database Users</h2>";
require_once 'php/config.php';

try {
    $stmt = $pdo->query("SELECT user_id, name, password, role, is_active FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>User ID</th><th>Name</th><th>Role</th><th>Active</th><th>Password Test</th><th>Actions</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            $test_result = password_verify('password', $user['password']);
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($user['user_id']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . ($user['is_active'] ? '✅ Active' : '❌ Inactive') . "</td>";
            echo "<td>" . ($test_result ? '✅ password' : '❌ Different') . "</td>";
            echo "<td>";
            if (!$test_result) {
                echo "<a href='?reset=" . urlencode($user['user_id']) . "' style='color: blue;'>Reset to 'password'</a>";
            } else {
                echo "OK";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No users found in database!</p>";
        echo "<p>Please import database/database.sql</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Handle password reset
if (isset($_GET['reset'])) {
    $user_id = $_GET['reset'];
    $new_hash = password_hash('password', PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$new_hash, $user_id]);
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "✅ <strong>Success!</strong> Password for user '$user_id' has been reset to: <strong>password</strong><br>";
        echo "<a href='verify-password.php'>Refresh page</a> to verify.";
        echo "</div>";
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "❌ <strong>Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";

echo "<h2>Test 3: Generate New Password Hash</h2>";
echo "<form method='post'>";
echo "<label>Enter password to hash:</label><br>";
echo "<input type='text' name='new_password' placeholder='Enter password' style='padding: 8px; width: 300px;'><br><br>";
echo "<button type='submit' name='generate' style='padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;'>Generate Hash</button>";
echo "</form>";

if (isset($_POST['generate'])) {
    $new_password = $_POST['new_password'];
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    echo "<div style='background: #e7f3ff; padding: 15px; margin-top: 20px; border-radius: 5px;'>";
    echo "<strong>Password:</strong> $new_password<br>";
    echo "<strong>Hash:</strong> <code style='word-break: break-all;'>$new_hash</code><br><br>";
    echo "<strong>SQL to update user:</strong><br>";
    echo "<code style='display: block; background: #f4f4f4; padding: 10px; margin-top: 10px;'>";
    echo "UPDATE users SET password = '$new_hash' WHERE user_id = 'ADMIN001';";
    echo "</code>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Quick Fixes</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<strong>If login still fails:</strong><br><br>";
echo "1. <strong>Check Role Match:</strong> Make sure the role dropdown matches the user's role in database<br>";
echo "2. <strong>Check User is Active:</strong> is_active must be TRUE/1<br>";
echo "3. <strong>Clear Browser Cache:</strong> Press Ctrl+Shift+Delete<br>";
echo "4. <strong>Test with simple-login-test.php:</strong> <a href='simple-login-test.php'>Click here</a><br>";
echo "</div>";

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
h2 { color: #34495e; margin-top: 30px; }
table { background: white; margin-top: 20px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";
?>
