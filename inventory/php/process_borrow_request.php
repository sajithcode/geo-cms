<?php
require_once '../../php/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Log the request for debugging
error_log("Borrow request received: " . print_r($_POST, true));

// Check if user is logged in
if (!isLoggedIn()) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $item_id = $_POST['item_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);
    $expected_return_date = $_POST['expected_return_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    
    // Validation
    if (empty($item_id) || $quantity <= 0 || empty($expected_return_date) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (strlen($reason) < 10) {
        echo json_encode(['success' => false, 'message' => 'Reason must be at least 10 characters']);
        exit;
    }
    
    // Validate return date is in the future
    $return_date = new DateTime($expected_return_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($return_date <= $today) {
        echo json_encode(['success' => false, 'message' => 'Return date must be in the future']);
        exit;
    }
    
    // Check if item exists and has sufficient quantity
    error_log("Checking item: $item_id");
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as category_name 
        FROM inventory_items i 
        LEFT JOIN inventory_categories c ON i.category_id = c.id 
        WHERE i.id = ? AND i.status = 'active'
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    error_log("Item query result: " . print_r($item, true));
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
    
    if ($item['quantity_available'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Insufficient quantity available']);
        exit;
    }
    
    // Check if user has any pending requests for the same item
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM borrow_requests 
        WHERE user_id = ? AND item_id = ? AND status = 'pending'
    ");
    $stmt->execute([$user_id, $item_id]);
    $pending_count = $stmt->fetchColumn();
    
    if ($pending_count > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending request for this item']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert borrow request
        $stmt = $pdo->prepare("
            INSERT INTO borrow_requests (
                user_id, item_id, quantity, expected_return_date, 
                reason, status, request_date
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$user_id, $item_id, $quantity, $expected_return_date, $reason]);
        
        $request_id = $pdo->lastInsertId();
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (
                user_id, action, details, created_at
            ) VALUES (?, 'borrow_request', ?, NOW())
        ");
        $details = json_encode([
            'request_id' => $request_id,
            'item_name' => $item['name'],
            'quantity' => $quantity
        ]);
        $stmt->execute([$user_id, $details]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Request submitted successfully',
            'request_id' => $request_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in process_borrow_request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}
?>