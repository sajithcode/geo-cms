<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debugger - Geo CMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .test-section h2 {
            color: #34495e;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        .result {
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .form-test {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 5px 0 15px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        button {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .icon { margin-right: 8px; }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .steps {
            list-style: none;
            counter-reset: step-counter;
        }
        .steps li {
            counter-increment: step-counter;
            padding: 10px 10px 10px 40px;
            position: relative;
            margin-bottom: 10px;
        }
        .steps li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Login Debugger</h1>
        <p class="subtitle">Diagnose exactly why login isn't working</p>

        <?php
        require_once 'php/config.php';
        
        // Test 1: Database Connection
        echo '<div class="test-section">';
        echo '<h2>Test 1: Database Connection</h2>';
        try {
            $pdo->query("SELECT 1");
            echo '<div class="result success"><span class="icon">✅</span>Database connection successful</div>';
        } catch (PDOException $e) {
            echo '<div class="result error"><span class="icon">❌</span>Database connection failed: ' . $e->getMessage() . '</div>';
            echo '<div class="result warning">Fix: Start MySQL in XAMPP and verify credentials in php/config.php</div>';
            exit;
        }
        echo '</div>';
        
        // Test 2: Check Users Table
        echo '<div class="test-section">';
        echo '<h2>Test 2: Users in Database</h2>';
        try {
            $stmt = $pdo->query("SELECT user_id, name, role, is_active, password FROM users ORDER BY role, user_id");
            $users = $stmt->fetchAll();
            
            if (count($users) > 0) {
                echo '<div class="result success"><span class="icon">✅</span>Found ' . count($users) . ' user(s) in database</div>';
                echo '<table style="width: 100%; border-collapse: collapse; margin-top: 15px;">';
                echo '<tr style="background: #667eea; color: white;">';
                echo '<th style="padding: 10px; text-align: left;">User ID</th>';
                echo '<th style="padding: 10px; text-align: left;">Name</th>';
                echo '<th style="padding: 10px; text-align: left;">Role</th>';
                echo '<th style="padding: 10px; text-align: center;">Active</th>';
                echo '<th style="padding: 10px; text-align: center;">Password OK</th>';
                echo '</tr>';
                
                foreach ($users as $user) {
                    $pwd_ok = password_verify('password', $user['password']);
                    echo '<tr style="border-bottom: 1px solid #dee2e6;">';
                    echo '<td style="padding: 10px;"><code>' . htmlspecialchars($user['user_id']) . '</code></td>';
                    echo '<td style="padding: 10px;">' . htmlspecialchars($user['name']) . '</td>';
                    echo '<td style="padding: 10px;">' . htmlspecialchars($user['role']) . '</td>';
                    echo '<td style="padding: 10px; text-align: center;">' . ($user['is_active'] ? '✅' : '❌') . '</td>';
                    echo '<td style="padding: 10px; text-align: center;">' . ($pwd_ok ? '✅' : '❌') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                // Check for issues
                $inactive = array_filter($users, fn($u) => !$u['is_active']);
                $bad_pwd = array_filter($users, fn($u) => !password_verify('password', $u['password']));
                
                if (count($inactive) > 0) {
                    echo '<div class="result warning" style="margin-top: 15px;">';
                    echo '<span class="icon">⚠️</span><strong>Warning:</strong> ' . count($inactive) . ' user(s) are inactive.<br>';
                    echo 'Fix: Run SQL: <code>UPDATE users SET is_active = TRUE WHERE user_id IN (';
                    echo implode(', ', array_map(fn($u) => "'" . $u['user_id'] . "'", $inactive));
                    echo ');</code>';
                    echo '</div>';
                }
                
                if (count($bad_pwd) > 0) {
                    echo '<div class="result warning" style="margin-top: 15px;">';
                    echo '<span class="icon">⚠️</span><strong>Warning:</strong> ' . count($bad_pwd) . ' user(s) have different passwords.<br>';
                    echo 'Fix: Visit <a href="verify-password.php">verify-password.php</a> to reset passwords';
                    echo '</div>';
                }
                
            } else {
                echo '<div class="result error"><span class="icon">❌</span>No users found in database</div>';
                echo '<div class="result warning">Fix: Import database/database.sql in phpMyAdmin</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="result error"><span class="icon">❌</span>Error: ' . $e->getMessage() . '</div>';
        }
        echo '</div>';
        
        // Test 3: Live Login Test
        echo '<div class="test-section">';
        echo '<h2>Test 3: Live Login Test</h2>';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
            $login_id = trim($_POST['login_id'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            
            echo '<div class="result info">';
            echo '<strong>Testing with:</strong><br>';
            echo 'User ID: <code>' . htmlspecialchars($login_id) . '</code><br>';
            echo 'Role: <code>' . htmlspecialchars($role) . '</code><br>';
            echo 'Password Length: ' . strlen($password) . ' characters<br>';
            echo '</div>';
            
            // Step 1: Find user
            echo '<ol class="steps" style="margin-top: 15px;">';
            echo '<li><strong>Searching for user...</strong><br>';
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, user_id, password, role, is_active FROM users WHERE (email = ? OR user_id = ?) AND role = ?");
                $stmt->execute([$login_id, $login_id, $role]);
                $user = $stmt->fetch();
                
                if ($user) {
                    echo '<div class="result success" style="margin-top: 10px;">✅ User found: ' . htmlspecialchars($user['name']) . '</div>';
                } else {
                    echo '<div class="result error" style="margin-top: 10px;">❌ No user found with ID "' . htmlspecialchars($login_id) . '" and role "' . htmlspecialchars($role) . '"</div>';
                    echo '<div class="result warning">Possible issues:<br>';
                    echo '• User ID might be wrong<br>';
                    echo '• Role selection doesn\'t match user\'s actual role<br>';
                    echo '• User doesn\'t exist in database</div>';
                    echo '</li></ol>';
                    echo '</div>';
                    goto end_test;
                }
            } catch (PDOException $e) {
                echo '<div class="result error" style="margin-top: 10px;">❌ Database error: ' . $e->getMessage() . '</div>';
                echo '</li></ol>';
                echo '</div>';
                goto end_test;
            }
            echo '</li>';
            
            // Step 2: Check if active
            echo '<li><strong>Checking if account is active...</strong><br>';
            if ($user['is_active']) {
                echo '<div class="result success" style="margin-top: 10px;">✅ Account is active</div>';
            } else {
                echo '<div class="result error" style="margin-top: 10px;">❌ Account is INACTIVE</div>';
                echo '<div class="result warning">Fix: Run SQL: <code>UPDATE users SET is_active = TRUE WHERE user_id = \'' . $user['user_id'] . '\';</code></div>';
                echo '</li></ol>';
                echo '</div>';
                goto end_test;
            }
            echo '</li>';
            
            // Step 3: Verify password
            echo '<li><strong>Verifying password...</strong><br>';
            if (password_verify($password, $user['password'])) {
                echo '<div class="result success" style="margin-top: 10px;">✅ Password is correct!</div>';
            } else {
                echo '<div class="result error" style="margin-top: 10px;">❌ Password is INCORRECT</div>';
                echo '<div class="result warning">The password you entered doesn\'t match the database.<br>';
                echo 'If you\'re using "password", visit <a href="verify-password.php">verify-password.php</a> to reset it.</div>';
                echo '</li></ol>';
                echo '</div>';
                goto end_test;
            }
            echo '</li>';
            
            // Step 4: Success
            echo '<li><strong>Creating session...</strong><br>';
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_identity'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            echo '<div class="result success" style="margin-top: 10px;">✅ Session created successfully!</div>';
            echo '<div class="result success" style="margin-top: 10px;">';
            echo '<strong>🎉 LOGIN SUCCESSFUL!</strong><br><br>';
            echo 'Logged in as: <strong>' . htmlspecialchars($user['name']) . '</strong><br>';
            echo 'Role: <strong>' . htmlspecialchars($user['role']) . '</strong><br><br>';
            echo '<a href="dashboard.php" style="display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: 600;">Go to Dashboard →</a>';
            echo '</div>';
            echo '</li>';
            echo '</ol>';
            echo '</div>';
            goto end_test;
        }
        
        // Show form
        echo '<div class="form-test">';
        echo '<form method="POST">';
        echo '<label><strong>User ID or Email:</strong></label>';
        echo '<input type="text" name="login_id" value="ADMIN001" required>';
        echo '<label><strong>Password:</strong></label>';
        echo '<input type="password" name="password" value="password" required>';
        echo '<label><strong>Role:</strong></label>';
        echo '<select name="role" required>';
        echo '<option value="admin" selected>Admin</option>';
        echo '<option value="lecturer">Lecturer</option>';
        echo '<option value="staff">Staff</option>';
        echo '<option value="student">Student</option>';
        echo '</select>';
        echo '<button type="submit" name="test_login">🚀 Test Login Now</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
        
        end_test:
        
        // Test 4: Recommendations
        echo '<div class="test-section">';
        echo '<h2>Recommendations</h2>';
        echo '<div class="result info">';
        echo '<strong>If login still fails on main page:</strong><br><br>';
        echo '1. Clear browser cache and cookies (Ctrl + Shift + Delete)<br>';
        echo '2. Check browser console for JavaScript errors (F12)<br>';
        echo '3. Make sure CSRF token is generated (check login.php source)<br>';
        echo '4. Verify form action points to: <code>php/login_process.php</code><br>';
        echo '5. Check that js/auth.js and js/script.js are loading<br><br>';
        echo '<strong>Tools available:</strong><br>';
        echo '• <a href="test-connection.php">test-connection.php</a> - Database setup test<br>';
        echo '• <a href="verify-password.php">verify-password.php</a> - Password verification tool<br>';
        echo '• <a href="simple-login-test.php">simple-login-test.php</a> - Simple login test<br>';
        echo '• <a href="login.php">login.php</a> - Main login page<br>';
        echo '</div>';
        echo '</div>';
        ?>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6;">
            <p style="color: #7f8c8d;">Geo CMS Debug Tool • Faculty of Geomatics</p>
        </div>
    </div>
</body>
</html>
