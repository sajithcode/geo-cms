<?php
require_once '../../php/config.php';

echo "Migration: Adding created_by column to store_categories table\n";
echo "=================================================================\n\n";

try {
    // Check if the column already exists
    $checkColumnQuery = "SHOW COLUMNS FROM store_categories LIKE 'created_by'";
    $result = $pdo->query($checkColumnQuery);
    
    if ($result->rowCount() > 0) {
        echo "✓ Column 'created_by' already exists in store_categories table.\n";
    } else {
        echo "→ Adding 'created_by' column to store_categories table...\n";
        
        // Add the created_by column
        $alterQuery = "ALTER TABLE store_categories 
                      ADD COLUMN created_by INT(11) DEFAULT NULL AFTER created_at,
                      ADD KEY created_by (created_by)";
        
        $pdo->exec($alterQuery);
        echo "✓ Successfully added 'created_by' column.\n";
        
        // Add foreign key constraint if users table exists
        try {
            $addFkQuery = "ALTER TABLE store_categories 
                          ADD FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL";
            $pdo->exec($addFkQuery);
            echo "✓ Successfully added foreign key constraint to users table.\n";
        } catch (PDOException $e) {
            echo "⚠ Warning: Could not add foreign key constraint (users table may not exist): " . $e->getMessage() . "\n";
        }
    }
    
    // Show the updated table structure
    echo "\nCurrent table structure:\n";
    echo "------------------------\n";
    $describeQuery = "DESCRIBE store_categories";
    $columns = $pdo->query($describeQuery)->fetchAll();
    
    printf("%-15s %-20s %-8s %-8s %-15s %-10s\n", 
           "Field", "Type", "Null", "Key", "Default", "Extra");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        printf("%-15s %-20s %-8s %-8s %-15s %-10s\n",
               $column['Field'],
               $column['Type'],
               $column['Null'],
               $column['Key'],
               $column['Default'] ?? 'NULL',
               $column['Extra']
        );
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>
