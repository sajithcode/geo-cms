<?php
require_once '../../php/config.php';

try {
    // Get current table structure
    $stmt = $pdo->query("DESCRIBE labs");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current columns in labs table:\n";
    foreach ($existing_columns as $column) {
        echo "- $column\n";
    }
    echo "\n";

    // Define all expected columns
    $expected_columns = [
        'equipment_list' => "ALTER TABLE labs ADD COLUMN equipment_list TEXT NULL",
        'safety_guidelines' => "ALTER TABLE labs ADD COLUMN safety_guidelines TEXT NULL",
        'created_at' => "ALTER TABLE labs ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE labs ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];

    // Add missing columns
    foreach ($expected_columns as $column_name => $sql) {
        if (!in_array($column_name, $existing_columns)) {
            $pdo->exec($sql);
            echo "Successfully added '$column_name' column to labs table\n";
        } else {
            echo "'$column_name' column already exists in labs table\n";
        }
    }

    echo "\nAll required columns have been added to the labs table!\n";

} catch (PDOException $e) {
    die("Error performing migration: " . $e->getMessage());
}
?>