<?php
require_once '../../php/config.php';

try {
    // Check if the code column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM labs LIKE 'code'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Add the code column if it doesn't exist
        $sql = "ALTER TABLE labs ADD COLUMN code VARCHAR(20) NOT NULL AFTER name";
        $pdo->exec($sql);
        
        echo "Successfully added 'code' column to labs table\n";
    } else {
        echo "'code' column already exists in labs table\n";
    }
} catch (PDOException $e) {
    die("Error performing migration: " . $e->getMessage());
}
?>