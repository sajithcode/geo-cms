<?php
// Migration script to add date range fields to borrow_requests table
require_once '../../php/config.php';

try {
    // Add new columns for date range
    $alterTable = "
        ALTER TABLE `borrow_requests` 
        ADD COLUMN `borrow_start_date` DATE DEFAULT NULL AFTER `expected_return_date`,
        ADD COLUMN `borrow_end_date` DATE DEFAULT NULL AFTER `borrow_start_date`
    ";
    
    $pdo->exec($alterTable);
    
    echo "Migration completed successfully. Added borrow_start_date and borrow_end_date columns.\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist. Migration skipped.\n";
    } else {
        echo "Migration failed: " . $e->getMessage() . "\n";
    }
}
?>