<?php
/**
 * Database Connection Test Script
 * Use this to verify your database setup is correct
 */

// Include config
require_once 'php/config.php';

echo "<h1>Geo CMS - Database Connection Test</h1>";

// Test 1: Check if database connection works
echo "<h2>Test 1: Database Connection</h2>";
try {
    $pdo->query("SELECT 1");
    echo "✅ <strong>Success:</strong> Database connection is working!<br>";
    echo "Connected to database: <strong>" . DB_NAME . "</strong><br><br>";
} catch (PDOException $e) {
    echo "❌ <strong>Error:</strong> Database connection failed!<br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
    exit;
}

// Test 2: Check if users table exists
echo "<h2>Test 2: Users Table</h2>";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ <strong>Success:</strong> Users table exists!<br>";
    echo "Columns: " . implode(", ", $columns) . "<br><br>";
} catch (PDOException $e) {
    echo "❌ <strong>Error:</strong> Users table not found!<br>";
    echo "Please run database/database.sql to create the table.<br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
}

// Test 3: Check if default users exist
echo "<h2>Test 3: Default Users</h2>";
try {
    $stmt = $pdo->query("SELECT user_id, name, email, role FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "✅ <strong>Success:</strong> Found " . count($users) . " user(s) in database:<br>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br><br>";
        echo "<strong>Note:</strong> Default password for all test accounts is: <code>password</code><br><br>";
    } else {
        echo "⚠️ <strong>Warning:</strong> No users found in database!<br>";
        echo "Please run database/database.sql to create default users.<br><br>";
    }
} catch (PDOException $e) {
    echo "❌ <strong>Error:</strong> Could not query users!<br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
}

// Test 4: Check other required tables
echo "<h2>Test 4: Other Required Tables</h2>";
$required_tables = [
    'labs',
    'lab_reservations',
    'lab_timetables',
    'store_categories',
    'store_items',
    'borrow_requests',
    'issue_reports',
    'notifications',
    'system_logs'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "✅ Table '<strong>$table</strong>' exists<br>";
    } catch (PDOException $e) {
        echo "❌ Table '<strong>$table</strong>' is missing<br>";
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    echo "<br>✅ <strong>Success:</strong> All required tables exist!<br><br>";
} else {
    echo "<br>⚠️ <strong>Warning:</strong> Missing tables: " . implode(", ", $missing_tables) . "<br>";
    echo "Please run database/database.sql to create missing tables.<br><br>";
}

// Test 5: Check file permissions
echo "<h2>Test 5: File Permissions</h2>";
$upload_dirs = [
    'uploads/',
    'uploads/store/',
    'uploads/issues/'
];

foreach ($upload_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Directory '<strong>$dir</strong>' is writable<br>";
        } else {
            echo "⚠️ Directory '<strong>$dir</strong>' exists but is not writable<br>";
        }
    } else {
        echo "❌ Directory '<strong>$dir</strong>' does not exist<br>";
    }
}

// Test 6: Check PHP configuration
echo "<br><h2>Test 6: PHP Configuration</h2>";
echo "PHP Version: <strong>" . phpversion() . "</strong><br>";
echo "Session Status: <strong>" . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "</strong><br>";
echo "PDO MySQL Driver: <strong>" . (extension_loaded('pdo_mysql') ? 'Installed' : 'Not Installed') . "</strong><br>";
echo "JSON Extension: <strong>" . (extension_loaded('json') ? 'Installed' : 'Not Installed') . "</strong><br>";
echo "GD Library: <strong>" . (extension_loaded('gd') ? 'Installed' : 'Not Installed') . "</strong><br>";

echo "<br><h2>Summary</h2>";
echo "If all tests passed, your system is ready to use!<br>";
echo "You can now test the login at: <a href='login.php'><strong>login.php</strong></a><br><br>";
echo "Default test account: <br>";
echo "User ID: <strong>ADMIN001</strong><br>";
echo "Password: <strong>password</strong><br>";
echo "Role: <strong>Admin</strong><br>";

// Style the page
echo "<style>
body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
h2 { color: #34495e; margin-top: 30px; border-left: 4px solid #3498db; padding-left: 10px; }
table { background: white; width: 100%; }
th { background: #3498db; color: white; padding: 10px; }
td { padding: 8px; }
code { background: #ecf0f1; padding: 2px 6px; border-radius: 3px; }
</style>";
?>
