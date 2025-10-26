<?php
require_once '../../php/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Log the request for debugging
error_log("Bulk borrow request received");

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
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $items = $data['items'] ?? [];
    $start_date = $data['borrow_start_date'] ?? '';
    $end_date = $data['borrow_end_date'] ?? '';
    $reason = trim($data['reason'] ?? '');
    $csrf_token = $data['csrf_token'] ?? '';
    
    // CSRF validation
    if (!verifyCSRFToken($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
    
    // Validation
    if (empty($items) || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'No items provided']);
        exit;
    }
    
    if (empty($start_date) || empty($end_date) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (strlen($reason) < 10) {
        echo json_encode(['success' => false, 'message' => 'Reason must be at least 10 characters']);
        exit;
    }
    
    // Validate date range
    $borrow_start = new DateTime($start_date);
    $borrow_end = new DateTime($end_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($borrow_start < $today) {
        echo json_encode(['success' => false, 'message' => 'Start date cannot be in the past']);
        exit;
    }
    
    if ($borrow_end <= $borrow_start) {
        echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
        exit;
    }
    
    // Check maximum borrow period (30 days)
    $daysDifference = $borrow_end->diff($borrow_start)->days;
    if ($daysDifference > 30) {
        echo json_encode(['success' => false, 'message' => 'Borrow period cannot exceed 30 days']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    $success_count = 0;
    $failed_items = [];
    
    try {
        foreach ($items as $item) {
            $item_id = $item['item_id'] ?? '';
            $quantity = (int)($item['quantity'] ?? 0);
            
            if (empty($item_id) || $quantity <= 0) {
                $failed_items[] = ['id' => $item_id, 'reason' => 'Invalid item data'];
                continue;
            }
            
            // Check if item exists and has sufficient quantity
            $stmt = $pdo->prepare("
                SELECT i.*, c.name as category_name 
                FROM store_items i 
                LEFT JOIN store_categories c ON i.category_id = c.id 
                WHERE i.id = ? AND i.status = 'active'
            ");
            $stmt->execute([$item_id]);
            $item_data = $stmt->fetch();
            
            if (!$item_data) {
                $failed_items[] = ['id' => $item_id, 'reason' => 'Item not found'];
                continue;
            }
            
            if ($item_data['quantity_available'] < $quantity) {
                $failed_items[] = [
                    'id' => $item_id, 
                    'name' => $item_data['name'],
                    'reason' => 'Insufficient quantity available'
                ];
                continue;
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
                $failed_items[] = [
                    'id' => $item_id,
                    'name' => $item_data['name'],
                    'reason' => 'Already have pending request for this item'
                ];
                continue;
            }
            
            // Insert borrow request
            $stmt = $pdo->prepare("
                INSERT INTO borrow_requests (
                    user_id, item_id, quantity, borrow_start_date, borrow_end_date,
                    reason, status, request_date
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$user_id, $item_id, $quantity, $start_date, $end_date, $reason]);
            
            $request_id = $pdo->lastInsertId();
            
            // Log activity
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (
                    user_id, action, details, created_at
                ) VALUES (?, 'bulk_borrow_request', ?, NOW())
            ");
            $details = json_encode([
                'request_id' => $request_id,
                'item_name' => $item_data['name'],
                'quantity' => $quantity,
                'bulk_request' => true
            ]);
            $stmt->execute([$user_id, $details]);
            
            $success_count++;
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Prepare response
        $response = [
            'success' => true,
            'count' => $success_count,
            'total_items' => count($items)
        ];
        
        if (!empty($failed_items)) {
            $response['partial'] = true;
            $response['failed_items'] = $failed_items;
            $response['message'] = "$success_count request(s) submitted successfully. " . count($failed_items) . " item(s) failed.";
        } else {
            $response['message'] = "All $success_count request(s) submitted successfully!";
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in process_bulk_borrow_request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your requests']);
}
?>
