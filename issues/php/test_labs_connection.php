<?php
/**
 * Quick Test Script to Verify Labs Can Be Fetched
 * Access this file to test if labs are loading correctly
 */

// Basic connection test (no authentication required for testing)
$db_host = 'localhost';
$db_name = 'geo_cms';
$db_user = 'root';
$db_pass = '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labs Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; }
        .info { color: #004085; background: #cce5ff; padding: 10px; border-radius: 4px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        h1 { color: #333; }
        h2 { color: #666; }
    </style>
</head>
<body>
    <h1>🧪 Labs Database Connection Test</h1>
    
    <div class="box">
        <h2>Step 1: Database Connection</h2>
        <?php
        try {
            $pdo = new PDO(
                "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                $db_user,
                $db_pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            echo '<div class="success">✅ Successfully connected to database: ' . $db_name . '</div>';
        } catch (PDOException $e) {
            echo '<div class="error">❌ Database connection failed: ' . $e->getMessage() . '</div>';
            echo '<div class="info"><strong>Fix:</strong> Check XAMPP MySQL is running and database credentials are correct.</div>';
            exit;
        }
        ?>
    </div>
    
    <div class="box">
        <h2>Step 2: Check Labs Table Exists</h2>
        <?php
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'labs'");
            if ($stmt->rowCount() > 0) {
                echo '<div class="success">✅ Labs table exists</div>';
            } else {
                echo '<div class="error">❌ Labs table does not exist!</div>';
                echo '<div class="info"><strong>Fix:</strong> Import labs_system_setup.sql file</div>';
                echo '<pre>mysql -u root -p geo_cms < labs_system_setup.sql</pre>';
                exit;
            }
        } catch (PDOException $e) {
            echo '<div class="error">❌ Error checking table: ' . $e->getMessage() . '</div>';
            exit;
        }
        ?>
    </div>
    
    <div class="box">
        <h2>Step 3: Fetch Labs Data</h2>
        <?php
        try {
            $stmt = $pdo->query("SELECT id, name FROM labs ORDER BY name ASC");
            $labs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($labs)) {
                echo '<div class="error">❌ Labs table is empty!</div>';
                echo '<div class="info"><strong>Fix:</strong> Insert sample labs data:</div>';
                echo '<pre>INSERT INTO labs (name, description, capacity, status) VALUES
("Lab 01", "Computer Science Lab", 30, "available"),
("Lab 02", "Network Lab", 25, "available"),
("Lab 03", "Database Lab", 30, "available"),
("Lab 04", "Multimedia Lab", 20, "available");</pre>';
            } else {
                echo '<div class="success">✅ Successfully fetched ' . count($labs) . ' lab(s)</div>';
                echo '<h3>Labs in Database:</h3>';
                echo '<table style="width:100%; border-collapse: collapse;">';
                echo '<tr style="background: #f8f9fa; font-weight: bold;">';
                echo '<td style="padding: 10px; border: 1px solid #ddd;">ID</td>';
                echo '<td style="padding: 10px; border: 1px solid #ddd;">Name</td>';
                echo '</tr>';
                foreach ($labs as $lab) {
                    echo '<tr>';
                    echo '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($lab['id']) . '</td>';
                    echo '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($lab['name']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">❌ Error fetching labs: ' . $e->getMessage() . '</div>';
            exit;
        }
        ?>
    </div>
    
    <div class="box">
        <h2>Step 4: Test Dropdown Generation</h2>
        <?php
        if (!empty($labs)) {
            echo '<div class="success">✅ Generating HTML dropdown:</div>';
            echo '<select style="width: 100%; padding: 10px; margin-top: 10px; font-size: 16px;">';
            echo '<option value="">Select Lab</option>';
            foreach ($labs as $lab) {
                echo '<option value="' . $lab['id'] . '">' . htmlspecialchars($lab['name']) . '</option>';
            }
            echo '</select>';
            
            echo '<h3>Generated HTML Code:</h3>';
            echo '<pre>';
            echo htmlspecialchars('<select name="lab_id" required>
    <option value="">Select Lab</option>');
            foreach ($labs as $lab) {
                echo "\n" . htmlspecialchars('    <option value="' . $lab['id'] . '">' . $lab['name'] . '</option>');
            }
            echo "\n" . htmlspecialchars('</select>');
            echo '</pre>';
        }
        ?>
    </div>
    
    <div class="box">
        <h2>✅ Test Results</h2>
        <?php
        if (!empty($labs)) {
            echo '<div class="success">';
            echo '<h3>🎉 All Tests Passed!</h3>';
            echo '<p>Labs can be fetched successfully. The issue reporting system should work correctly.</p>';
            echo '<p><strong>Next Steps:</strong></p>';
            echo '<ul>';
            echo '<li>Go to <a href="../issues/">Issue Reporting Dashboard</a></li>';
            echo '<li>Try creating a new issue report</li>';
            echo '<li>The lab dropdown should now be populated</li>';
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<h3>❌ Issues Found</h3>';
            echo '<p>Please follow the fix instructions above to resolve the issues.</p>';
            echo '</div>';
        }
        ?>
    </div>
    
    <div class="box">
        <h2>🔧 Useful Commands</h2>
        <h3>Check MySQL is Running:</h3>
        <pre>Open XAMPP Control Panel → MySQL should be green/running</pre>
        
        <h3>Import Labs Data:</h3>
        <pre>mysql -u root -p geo_cms < c:\xampp\htdocs\geo-cms\labs_system_setup.sql</pre>
        
        <h3>Or in phpMyAdmin:</h3>
        <pre>1. Go to http://localhost/phpmyadmin
2. Select 'geo_cms' database
3. Click 'Import' tab
4. Choose 'labs_system_setup.sql'
5. Click 'Go'</pre>
        
        <h3>Verify Labs:</h3>
        <pre>mysql -u root -p geo_cms -e "SELECT * FROM labs;"</pre>
    </div>
    
    <p style="text-align: center; margin-top: 30px;">
        <a href="../issues/" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">
            ← Back to Issue Reporting
        </a>
        <a href="diagnostic.php" style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin-left: 10px;">
            Run Full Diagnostic
        </a>
    </p>
</body>
</html>
