<?php
require_once '../../php/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
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
    $user_role = $_SESSION['role'] ?? '';
    $request_id = $_POST['request_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($request_id) || empty($action)) {
        echo json_encode(['success' => false, 'message' => 'Request ID and action are required']);
        exit;
    }
    
    // Check valid actions
    $valid_actions = ['approve', 'reject', 'cancel', 'return'];
    if (!in_array($action, $valid_actions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    // Get request details
    $stmt = $pdo->prepare("
        SELECT br.*,
               GROUP_CONCAT(
                   CONCAT(i.name, ' (x', bri.quantity, ')')
                   ORDER BY i.name SEPARATOR ', '
               ) as item_names,
               GROUP_CONCAT(bri.quantity) as quantities,
               GROUP_CONCAT(bri.item_id) as item_ids,
               u.name as requester_name
        FROM borrow_requests br
        JOIN borrow_request_items bri ON br.id = bri.borrow_request_id
        JOIN store_items i ON bri.item_id = i.id
        JOIN users u ON br.user_id = u.id
        WHERE br.id = ?
        GROUP BY br.id
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }
    
    // Authorization checks
    if ($action === 'cancel') {
        // Only the requester can cancel their own request
        if ($request['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'You can only cancel your own requests']);
            exit;
        }
        if ($request['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled']);
            exit;
        }
    } elseif (in_array($action, ['approve', 'reject'])) {
        // Only staff and admin can approve/reject
        if (!in_array($user_role, ['staff', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized to perform this action']);
            exit;
        }
        if ($request['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Only pending requests can be approved or rejected']);
            exit;
        }
        
        // Check quantity availability for approval (multiple items)
        if ($action === 'approve') {
            $item_ids = explode(',', $request['item_ids']);
            $quantities = explode(',', $request['quantities']);

            for ($i = 0; $i < count($item_ids); $i++) {
                $stmt = $pdo->prepare("SELECT quantity_available FROM store_items WHERE id = ?");
                $stmt->execute([$item_ids[$i]]);
                $item = $stmt->fetch();

                if (!$item || $item['quantity_available'] < $quantities[$i]) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient quantity available for one or more items']);
                    exit;
                }
            }
        }
    } elseif ($action === 'return') {
        // Only staff and admin can mark as returned
        if (!in_array($user_role, ['staff', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized to perform this action']);
            exit;
        }
        if ($request['status'] !== 'approved') {
            echo json_encode(['success' => false, 'message' => 'Only approved requests can be marked as returned']);
            exit;
        }
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        $new_status = '';
        $approved_by = null;
        $approved_date = null;
        
        if ($action === 'approve') {
            $new_status = 'approved';
            $approved_by = $user_id;
            $approved_date = date('Y-m-d H:i:s');

            // Update item quantities for all items
            $item_ids = explode(',', $request['item_ids']);
            $quantities = explode(',', $request['quantities']);

            for ($i = 0; $i < count($item_ids); $i++) {
                $stmt = $pdo->prepare("
                    UPDATE store_items
                    SET quantity_available = quantity_available - ?,
                        quantity_borrowed = quantity_borrowed + ?
                    WHERE id = ?
                ");
                $stmt->execute([$quantities[$i], $quantities[$i], $item_ids[$i]]);
            }
            
        } elseif ($action === 'reject') {
            $new_status = 'rejected';
            $approved_by = $user_id;
            $approved_date = date('Y-m-d H:i:s');
            
        } elseif ($action === 'cancel') {
            $new_status = 'cancelled';
            
        } elseif ($action === 'return') {
            $new_status = 'returned';

            // Update item quantities for all items
            $item_ids = explode(',', $request['item_ids']);
            $quantities = explode(',', $request['quantities']);

            for ($i = 0; $i < count($item_ids); $i++) {
                $stmt = $pdo->prepare("
                    UPDATE store_items
                    SET quantity_available = quantity_available + ?,
                        quantity_borrowed = quantity_borrowed - ?
                    WHERE id = ?
                ");
                $stmt->execute([$quantities[$i], $quantities[$i], $item_ids[$i]]);

                // Check if item condition affects maintenance count
                $condition = $_POST['condition'] ?? 'Good';
                if (in_array($condition, ['Damaged', 'Needs Repair'])) {
                    $stmt = $pdo->prepare("
                        UPDATE store_items
                        SET quantity_available = quantity_available - ?,
                            quantity_maintenance = quantity_maintenance + ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$quantities[$i], $quantities[$i], $item_ids[$i]]);
                }
            }
        }
        
        // Update request status
        $stmt = $pdo->prepare("
            UPDATE borrow_requests 
            SET status = ?, notes = ?, approved_by = ?, approved_date = ?
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $notes, $approved_by, $approved_date, $request_id]);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (
                user_id, action, details, created_at
            ) VALUES (?, ?, ?, NOW())
        ");
        $details = json_encode([
            'request_id' => $request_id,
            'action' => $action,
            'items' => $request['item_names'],
            'requester' => $request['requester_name'],
            'total_quantity' => array_sum(explode(',', $request['quantities']))
        ]);
        $stmt->execute([$user_id, "request_$action", $details]);
        
        $pdo->commit();
        
        $message = '';
        switch ($action) {
            case 'approve':
                $message = 'Request approved successfully';
                break;
            case 'reject':
                $message = 'Request rejected successfully';
                break;
            case 'cancel':
                $message = 'Request cancelled successfully';
                break;
            case 'return':
                $message = 'Item marked as returned successfully';
                break;
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in update_request_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the request']);
}
?>
