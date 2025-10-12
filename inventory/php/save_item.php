<?php
require_once '../../php/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $item_id = $_POST['item_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $quantity_total = (int)($_POST['quantity_total'] ?? 0);
    $quantity_available = (int)($_POST['quantity_available'] ?? 0);
    $quantity_borrowed = (int)($_POST['quantity_borrowed'] ?? 0);
    $quantity_maintenance = (int)($_POST['quantity_maintenance'] ?? 0);
    
    // Validation
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required']);
        exit;
    }
    
    if ($quantity_total <= 0) {
        echo json_encode(['success' => false, 'message' => 'Total quantity must be greater than 0']);
        exit;
    }
    
    // Validate quantity consistency
    if (($quantity_available + $quantity_borrowed + $quantity_maintenance) !== $quantity_total) {
        echo json_encode(['success' => false, 'message' => 'Quantity breakdown must equal total quantity']);
        exit;
    }
    
    // If category_id is provided, validate it exists
    if (!empty($category_id)) {
        $stmt = $pdo->prepare("SELECT id FROM inventory_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid category selected']);
            exit;
        }
    } else {
        $category_id = null;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        if (!empty($item_id)) {
            // Update existing item
            $stmt = $pdo->prepare("
                UPDATE inventory_items 
                SET name = ?, description = ?, category_id = ?, 
                    quantity_total = ?, quantity_available = ?, 
                    quantity_borrowed = ?, quantity_maintenance = ?,
                    updated_at = NOW(), updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $description, $category_id, 
                $quantity_total, $quantity_available, 
                $quantity_borrowed, $quantity_maintenance,
                $user_id, $item_id
            ]);
            
            $action = 'item_updated';
            $message = 'Item updated successfully';
            $result_id = $item_id;
            
        } else {
            // Create new item
            $stmt = $pdo->prepare("
                INSERT INTO inventory_items (
                    name, description, category_id, 
                    quantity_total, quantity_available, 
                    quantity_borrowed, quantity_maintenance,
                    status, created_at, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), ?)
            ");
            $stmt->execute([
                $name, $description, $category_id, 
                $quantity_total, $quantity_available, 
                $quantity_borrowed, $quantity_maintenance,
                $user_id
            ]);
            
            $result_id = $pdo->lastInsertId();
            $action = 'item_created';
            $message = 'Item created successfully';
        }
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (
                user_id, action, details, created_at
            ) VALUES (?, ?, ?, NOW())
        ");
        $details = json_encode([
            'item_id' => $result_id,
            'item_name' => $name,
            'quantity_total' => $quantity_total
        ]);
        $stmt->execute([$user_id, $action, $details]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'item_id' => $result_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in save_item.php: " . $e->getMessage());
    
    // Check for duplicate name error
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['success' => false, 'message' => 'An item with this name already exists']);
    } else {
        echo json_encode(['success' => false, 'message' => 'An error occurred while saving the item']);
    }
}
?>