<?php
require 'config/config.php';

echo "=== DATABASE VERIFICATION ===\n\n";

// Count store items
$stmt = $pdo->query('SELECT COUNT(*) as total FROM store_items');
$items_count = $stmt->fetch()['total'];
echo "Total Store Items: $items_count\n";

// Count labs
$stmt = $pdo->query('SELECT COUNT(*) as total FROM labs');
$labs_count = $stmt->fetch()['total'];
echo "Total Labs: $labs_count\n\n";

// List all store items
echo "=== STORE ITEMS ===\n";
$stmt = $pdo->query('SELECT id, name, category_id, quantity_total, quantity_available FROM store_items ORDER BY id');
while ($item = $stmt->fetch()) {
    echo "ID: {$item['id']} | {$item['name']} | Category: {$item['category_id']} | Total: {$item['quantity_total']} | Available: {$item['quantity_available']}\n";
}

echo "\n=== LABS ===\n";
$stmt = $pdo->query('SELECT id, name, capacity, status FROM labs ORDER BY id');
while ($lab = $stmt->fetch()) {
    echo "ID: {$lab['id']} | {$lab['name']} | Capacity: {$lab['capacity']} | Status: {$lab['status']}\n";
}

echo "\n✓ Database is ready for display!\n";
