<?php
/**
 * Database Diagnostic Script for Issue Reporting System
 * This script checks if all required tables and data exist
 */

require_once '../php/config.php';

// Only allow admin access
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    die('Access denied. Admin only.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Reporting - Database Diagnostic</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 0; }
        .status { 
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
        }
        .status.ok { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.warning { background: #fff3cd; color: #856404; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
        }
        th, td { 
            padding: 10px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        th { background: #f8f9fa; font-weight: 600; }
        code { 
            background: #f4f4f4; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover { background: #0056b3; }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Issue Reporting System - Database Diagnostic</h1>
    
    <?php
    $issues = [];
    $warnings = [];
    
    // Check 1: Labs table
    echo '<div class="section">';
    echo '<h2>1. Labs Table Check</h2>';
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'labs'");
        if ($stmt->rowCount() > 0) {
            echo '<p><span class="status ok">✓ OK</span> Labs table exists</p>';
            
            // Check for data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM labs");
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                echo '<p><span class="status ok">✓ OK</span> Found ' . $count . ' lab(s)</p>';
                
                // Show labs
                $stmt = $pdo->query("SELECT * FROM labs ORDER BY name");
                $labs = $stmt->fetchAll();
                
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Description</th><th>Capacity</th><th>Status</th></tr>';
                foreach ($labs as $lab) {
                    echo '<tr>';
                    echo '<td>' . $lab['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($lab['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($lab['description'] ?? 'N/A') . '</td>';
                    echo '<td>' . ($lab['capacity'] ?? 'N/A') . '</td>';
                    echo '<td>' . ($lab['status'] ?? 'N/A') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p><span class="status error">✗ ERROR</span> Labs table exists but is empty!</p>';
                $issues[] = 'No labs in database';
                echo '<p><strong>Solution:</strong> Run the labs_system_setup.sql script to insert sample labs.</p>';
                echo '<pre>mysql -u root -p geo_cms < labs_system_setup.sql</pre>';
            }
        } else {
            echo '<p><span class="status error">✗ ERROR</span> Labs table does not exist!</p>';
            $issues[] = 'Labs table missing';
            echo '<p><strong>Solution:</strong> Import the labs_system_setup.sql file.</p>';
        }
    } catch (PDOException $e) {
        echo '<p><span class="status error">✗ ERROR</span> ' . $e->getMessage() . '</p>';
        $issues[] = 'Labs table check failed';
    }
    echo '</div>';
    
    // Check 2: Issue Reports table
    echo '<div class="section">';
    echo '<h2>2. Issue Reports Table Check</h2>';
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'issue_reports'");
        if ($stmt->rowCount() > 0) {
            echo '<p><span class="status ok">✓ OK</span> Issue_reports table exists</p>';
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM issue_reports");
            $count = $stmt->fetch()['count'];
            echo '<p>Current reports: ' . $count . '</p>';
        } else {
            echo '<p><span class="status error">✗ ERROR</span> Issue_reports table does not exist!</p>';
            $issues[] = 'Issue_reports table missing';
            echo '<p><strong>Solution:</strong> Import the issues_schema.sql file.</p>';
            echo '<pre>mysql -u root -p geo_cms < issues_schema.sql</pre>';
        }
    } catch (PDOException $e) {
        echo '<p><span class="status error">✗ ERROR</span> ' . $e->getMessage() . '</p>';
        $issues[] = 'Issue_reports table check failed';
    }
    echo '</div>';
    
    // Check 3: Computers table
    echo '<div class="section">';
    echo '<h2>3. Computers Table Check</h2>';
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'computers'");
        if ($stmt->rowCount() > 0) {
            echo '<p><span class="status ok">✓ OK</span> Computers table exists</p>';
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM computers");
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                echo '<p><span class="status ok">✓ OK</span> Found ' . $count . ' computer(s)</p>';
                
                // Show sample
                $stmt = $pdo->query("SELECT c.serial_no, l.name as lab_name FROM computers c LEFT JOIN labs l ON c.lab_id = l.id LIMIT 5");
                $computers = $stmt->fetchAll();
                
                echo '<p><strong>Sample computers:</strong></p>';
                echo '<ul>';
                foreach ($computers as $comp) {
                    echo '<li>' . htmlspecialchars($comp['serial_no']) . ' - ' . htmlspecialchars($comp['lab_name'] ?? 'No Lab') . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p><span class="status warning">⚠ WARNING</span> Computers table is empty</p>';
                $warnings[] = 'No computers in database';
                echo '<p><strong>Solution:</strong> The issues_schema.sql includes sample computer data.</p>';
            }
        } else {
            echo '<p><span class="status error">✗ ERROR</span> Computers table does not exist!</p>';
            $issues[] = 'Computers table missing';
            echo '<p><strong>Solution:</strong> Import the issues_schema.sql file.</p>';
        }
    } catch (PDOException $e) {
        echo '<p><span class="status error">✗ ERROR</span> ' . $e->getMessage() . '</p>';
        $issues[] = 'Computers table check failed';
    }
    echo '</div>';
    
    // Check 4: Database Connection
    echo '<div class="section">';
    echo '<h2>4. Database Connection Check</h2>';
    try {
        $stmt = $pdo->query("SELECT DATABASE() as db, VERSION() as version");
        $info = $stmt->fetch();
        echo '<p><span class="status ok">✓ OK</span> Connected to database: <code>' . $info['db'] . '</code></p>';
        echo '<p>MySQL Version: <code>' . $info['version'] . '</code></p>';
    } catch (PDOException $e) {
        echo '<p><span class="status error">✗ ERROR</span> ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // Check 5: File Permissions
    echo '<div class="section">';
    echo '<h2>5. File Upload Directory Check</h2>';
    $upload_dir = '../../uploads/issues/';
    if (is_dir($upload_dir)) {
        echo '<p><span class="status ok">✓ OK</span> Upload directory exists</p>';
        if (is_writable($upload_dir)) {
            echo '<p><span class="status ok">✓ OK</span> Upload directory is writable</p>';
        } else {
            echo '<p><span class="status error">✗ ERROR</span> Upload directory is not writable!</p>';
            $issues[] = 'Upload directory not writable';
            echo '<p><strong>Solution (Windows):</strong> Right-click folder → Properties → Security → Add write permissions</p>';
            echo '<p><strong>Solution (Linux):</strong> <code>chmod 755 uploads/issues/</code></p>';
        }
    } else {
        echo '<p><span class="status warning">⚠ WARNING</span> Upload directory does not exist (will be created automatically)</p>';
        $warnings[] = 'Upload directory will be auto-created';
    }
    echo '</div>';
    
    // Summary
    echo '<div class="section">';
    echo '<h2>📊 Summary</h2>';
    
    if (empty($issues)) {
        echo '<p style="font-size: 18px;"><span class="status ok">✓ ALL CHECKS PASSED</span></p>';
        echo '<p>The Issue Reporting System is properly configured and ready to use!</p>';
    } else {
        echo '<p style="font-size: 18px;"><span class="status error">✗ ' . count($issues) . ' ISSUE(S) FOUND</span></p>';
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li>' . $issue . '</li>';
        }
        echo '</ul>';
        echo '<p><strong>Required Actions:</strong></p>';
        echo '<ol>';
        echo '<li>Import <code>labs_system_setup.sql</code> if labs table is missing or empty</li>';
        echo '<li>Import <code>issues_schema.sql</code> if issue tables are missing</li>';
        echo '<li>Verify database connection settings in <code>php/config.php</code></li>';
        echo '</ol>';
    }
    
    if (!empty($warnings)) {
        echo '<p style="margin-top: 20px;"><span class="status warning">⚠ ' . count($warnings) . ' WARNING(S)</span></p>';
        echo '<ul>';
        foreach ($warnings as $warning) {
            echo '<li>' . $warning . '</li>';
        }
        echo '</ul>';
    }
    
    echo '<p style="margin-top: 20px;"><a href="../" class="btn">← Back to Issues Dashboard</a></p>';
    echo '</div>';
    ?>
    
</body>
</html>
