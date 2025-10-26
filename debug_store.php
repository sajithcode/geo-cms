<?php
require_once '../php/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get available store items for borrowing
try {
    $stmt = $pdo->prepare("
        SELECT ii.*, ic.name as category_name
        FROM store_items ii
        LEFT JOIN store_categories ic ON ii.category_id = ic.id
        WHERE ii.quantity_available > 0
        ORDER BY ii.name ASC
    ");
    $stmt->execute();
    $available_items = $stmt->fetchAll();

    echo "<h1>DEBUG: Available Items</h1>";
    echo "<p>Found " . count($available_items) . " items</p>";

    if (count($available_items) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Available</th></tr>";
        foreach ($available_items as $item) {
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            echo "<td>" . htmlspecialchars($item['name']) . "</td>";
            echo "<td>" . htmlspecialchars($item['category_name'] ?? 'No category') . "</td>";
            echo "<td>" . $item['quantity_available'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No available items found!</p>";
    }

} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}
</content>
<parameter name="filePath">c:\xampp\htdocs\geo-cms\debug_store.php