<?php
require_once 'config/config.php';

echo "=== TESTING DATABASE QUERIES ===\n\n";

try {
    // Test store items query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM store_items");
    $count = $stmt->fetch()['count'];
    echo "Store items count: $count\n";

    // Test available items query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM store_items WHERE quantity_available > 0");
    $available_count = $stmt->fetch()['count'];
    echo "Available items count: $available_count\n";

    // Test categories query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM store_categories");
    $cat_count = $stmt->fetch()['count'];
    echo "Categories count: $cat_count\n";

    // Test labs query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM labs");
    $lab_count = $stmt->fetch()['count'];
    echo "Labs count: $lab_count\n";

    echo "\n✓ All queries working!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
</content>
<parameter name="filePath">c:\xampp\htdocs\geo-cms\test_queries.php