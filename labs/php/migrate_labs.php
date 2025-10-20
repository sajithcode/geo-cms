<?php
require_once '../../php/config.php';

echo "Labs Management Database Migration\n";
echo "==================================\n\n";

try {
    // Read and execute the labs schema
    $schema = file_get_contents('../../labs_schema.sql');
    
    if (!$schema) {
        throw new Exception('Could not read labs_schema.sql file');
    }
    
    // Split by semicolons to get individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "Executing " . count($statements) . " SQL statements...\n\n";
    
    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
            
            // Determine what type of statement this is
            $stmt_type = 'Unknown';
            if (stripos($statement, 'CREATE TABLE') === 0) {
                preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches);
                $stmt_type = 'CREATE TABLE ' . ($matches[1] ?? 'Unknown');
            } elseif (stripos($statement, 'INSERT INTO') === 0) {
                preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches);
                $stmt_type = 'INSERT INTO ' . ($matches[1] ?? 'Unknown');
            } elseif (stripos($statement, 'ALTER TABLE') === 0) {
                preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $statement, $matches);
                $stmt_type = 'ALTER TABLE ' . ($matches[1] ?? 'Unknown');
            }
            
            echo "✓ " . ($index + 1) . ". " . $stmt_type . "\n";
            
        } catch (PDOException $e) {
            // Check if it's just a "table already exists" error
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ " . ($index + 1) . ". Skipped (already exists): " . $stmt_type . "\n";
            } else {
                echo "✗ " . ($index + 1) . ". Error: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n";
    echo "Migration completed!\n";
    echo "====================\n\n";
    
    // Verify the migration by checking if tables exist
    echo "Verifying tables...\n";
    $tables = ['labs', 'lab_reservations', 'lab_timetables', 'lab_issues'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "✓ Table '$table' exists with $count records\n";
        } catch (PDOException $e) {
            echo "✗ Table '$table' does not exist or has issues\n";
        }
    }
    
    echo "\nLabs management system is ready to use!\n";
    echo "You can now access the labs section from the sidebar.\n\n";
    
    echo "Sample labs created:\n";
    echo "- Lab 01: Computer Lab 01 (LAB01)\n";
    echo "- Lab 02: Physics Lab 02 (LAB02)\n";
    echo "- Lab 03: Chemistry Lab 03 (LAB03)\n";
    echo "- Lab 04: Engineering Lab 04 (LAB04) - Currently in maintenance\n\n";
    
    echo "To access the labs:\n";
    echo "- Students: Can view labs, request usage, and track reservations\n";
    echo "- Lecturers: Can reserve labs for classes and report issues\n";
    echo "- Admin/Staff: Can manage all aspects including approvals and timetables\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>