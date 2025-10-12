<?php
require_once '../../php/config.php';

echo "Testing Category Creation\n";
echo "========================\n\n";

// Simulate a logged-in admin user for testing
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists
$_SESSION['role'] = 'admin';

// Test data
$testCategories = [
    [
        'name' => 'Test Electronics',
        'description' => 'Test category for electronic devices'
    ],
    [
        'name' => 'Test Laboratory',
        'description' => 'Test category for lab equipment'
    ]
];

foreach ($testCategories as $index => $categoryData) {
    echo "Test " . ($index + 1) . ": Creating category '{$categoryData['name']}'...\n";
    
    try {
        // Check if category already exists
        $checkStmt = $pdo->prepare("SELECT id FROM inventory_categories WHERE name = ?");
        $checkStmt->execute([$categoryData['name']]);
        
        if ($checkStmt->fetch()) {
            echo "  → Category already exists, skipping...\n";
            continue;
        }
        
        // Insert new category
        $insertStmt = $pdo->prepare("
            INSERT INTO inventory_categories (name, description, created_by) 
            VALUES (?, ?, ?)
        ");
        
        $createdBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $success = $insertStmt->execute([
            $categoryData['name'], 
            $categoryData['description'], 
            $createdBy
        ]);
        
        if ($success) {
            $categoryId = $pdo->lastInsertId();
            echo "  ✓ Successfully created category with ID: $categoryId\n";
            
            // Fetch and display the created category
            $fetchStmt = $pdo->prepare("
                SELECT c.*, u.name as created_by_name, u.user_id as created_by_user_id
                FROM inventory_categories c
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?
            ");
            $fetchStmt->execute([$categoryId]);
            $category = $fetchStmt->fetch();
            
            if ($category) {
                echo "    Name: {$category['name']}\n";
                echo "    Description: {$category['description']}\n";
                echo "    Created: {$category['created_at']}\n";
                echo "    Created By: " . ($category['created_by_name'] ?? 'Unknown') . "\n";
            }
        } else {
            echo "  ✗ Failed to create category\n";
        }
        
    } catch (PDOException $e) {
        echo "  ✗ Database error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Display all categories
echo "All Categories:\n";
echo "---------------\n";
try {
    $allCategoriesStmt = $pdo->query("
        SELECT c.*, u.name as created_by_name, u.user_id as created_by_user_id
        FROM inventory_categories c
        LEFT JOIN users u ON c.created_by = u.id
        ORDER BY c.created_at DESC
    ");
    
    $categories = $allCategoriesStmt->fetchAll();
    
    if (empty($categories)) {
        echo "No categories found.\n";
    } else {
        printf("%-5s %-20s %-30s %-20s %-15s\n", 
               "ID", "Name", "Description", "Created", "Created By");
        echo str_repeat("-", 90) . "\n";
        
        foreach ($categories as $category) {
            printf("%-5s %-20s %-30s %-20s %-15s\n",
                   $category['id'],
                   substr($category['name'], 0, 19),
                   substr($category['description'] ?? 'No description', 0, 29),
                   substr($category['created_at'], 0, 19),
                   substr($category['created_by_name'] ?? 'Unknown', 0, 14)
            );
        }
    }
    
} catch (PDOException $e) {
    echo "Error fetching categories: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n";
?>