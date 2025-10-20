<?php
require_once '../../php/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $item_id = $_GET['id'] ?? '';
    
    if (empty($item_id)) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        exit;
    }
    
    // Get item details
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            c.name as category_name,
            creator.name as created_by_name,
            updater.name as updated_by_name
        FROM inventory_items i
        LEFT JOIN inventory_categories c ON i.category_id = c.id
        LEFT JOIN users creator ON i.created_by = creator.id
        LEFT JOIN users updater ON i.updated_by = updater.id
        WHERE i.id = ?
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
    
    // Format dates
    if ($item['created_at']) {
        $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
    }
    if ($item['updated_at']) {
        $item['updated_at'] = date('Y-m-d H:i:s', strtotime($item['updated_at']));
    }
    
    echo json_encode(['success' => true, 'item' => $item]);
    
} catch (Exception $e) {
    error_log("Error in get_item_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while loading item details']);
}
?>