<?php
require_once '../../php/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $request_id = $_GET['id'] ?? '';
    
    if (empty($request_id)) {
        echo json_encode(['success' => false, 'message' => 'Request ID is required']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? '';
    
    // Get request details with related information
    $stmt = $pdo->prepare("
        SELECT
            br.*,
            u.name as requester_name,
            u.user_id as requester_id,
            u.email as requester_email,
            approver.name as approved_by_name
        FROM borrow_requests br
        JOIN users u ON br.user_id = u.id
        LEFT JOIN users approver ON br.approved_by = approver.id
        WHERE br.id = ?
    ");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    // Get request items
    $stmt = $pdo->prepare("
        SELECT
            bri.quantity,
            i.name as item_name,
            i.description as item_description,
            i.image_path,
            c.name as category_name
        FROM borrow_request_items bri
        JOIN store_items i ON bri.item_id = i.id
        LEFT JOIN store_categories c ON i.category_id = c.id
        WHERE bri.borrow_request_id = ?
        ORDER BY i.name
    ");
    $stmt->execute([$request_id]);
    $request_items = $stmt->fetchAll();

    // Add items to request data
    $request['items'] = $request_items;

    // For backward compatibility, set single item fields if there's only one item
    if (count($request_items) === 1) {
        $item = $request_items[0];
        $request['item_name'] = $item['item_name'];
        $request['item_description'] = $item['item_description'];
        $request['quantity'] = $item['quantity'];
        $request['category_name'] = $item['category_name'];
    }
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }
    
    // Authorization check - users can only see their own requests, staff/admin can see all
    if ($user_role === 'student' && $request['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Not authorized to view this request']);
        exit;
    }
    
    // Format dates
    if ($request['request_date']) {
        $request['request_date'] = date('Y-m-d H:i:s', strtotime($request['request_date']));
    }
    if ($request['approved_date']) {
        $request['approved_date'] = date('Y-m-d H:i:s', strtotime($request['approved_date']));
    }
    if ($request['expected_return_date']) {
        $request['expected_return_date'] = date('Y-m-d', strtotime($request['expected_return_date']));
    }
    if ($request['borrow_start_date']) {
        $request['borrow_start_date'] = date('Y-m-d', strtotime($request['borrow_start_date']));
    }
    if ($request['borrow_end_date']) {
        $request['borrow_end_date'] = date('Y-m-d', strtotime($request['borrow_end_date']));
    }
    
    echo json_encode(['success' => true, 'request' => $request]);
    
} catch (Exception $e) {
    error_log("Error in get_request_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while loading request details']);
}
?>