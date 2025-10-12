<?php
// Simple test file to debug the API endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing API endpoint...\n";

// Test if config.php can be included
try {
    require_once '../../php/config.php';
    echo "✓ Config.php included successfully\n";
} catch (Exception $e) {
    echo "✗ Error including config.php: " . $e->getMessage() . "\n";
    exit;
}

// Test if user is logged in
if (isLoggedIn()) {
    echo "✓ User is logged in\n";
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "User Role: " . $_SESSION['role'] . "\n";
} else {
    echo "✗ User is not logged in\n";
}

// Test database connection
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE status = 'active'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "✓ Database connection working\n";
    echo "Active items count: " . $count . "\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
?>