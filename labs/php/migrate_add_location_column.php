<?php
require_once '../../php/config.php';

try {
    // Check if the location column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM labs LIKE 'location'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Add the location column if it doesn't exist
        $sql = "ALTER TABLE labs ADD COLUMN location VARCHAR(255) NULL AFTER capacity";
        $pdo->exec($sql);
        
        echo "Successfully added 'location' column to labs table\n";
    } else {
        echo "'location' column already exists in labs table\n";
    }
} catch (PDOException $e) {
    die("Error performing migration: " . $e->getMessage());
}
?>