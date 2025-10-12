<?php
require_once '../../php/config.php';

try {
    // Add image column to inventory_items table
    $stmt = $pdo->prepare("
        ALTER TABLE inventory_items 
        ADD COLUMN image_path VARCHAR(255) NULL AFTER description
    ");
    $stmt->execute();
    
    echo "✅ Successfully added image_path column to inventory_items table\n";
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = '../../uploads/inventory';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
        echo "✅ Created uploads/inventory directory\n";
    } else {
        echo "ℹ️ uploads/inventory directory already exists\n";
    }
    
    // Create .htaccess for uploads security
    $htaccessContent = "Options -Indexes\n";
    $htaccessContent .= "<Files ~ \"\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n";
    
    file_put_contents($uploadsDir . '/.htaccess', $htaccessContent);
    echo "✅ Created security .htaccess file in uploads directory\n";
    
    echo "\n🎉 Image functionality migration completed successfully!\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️ image_path column already exists\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>