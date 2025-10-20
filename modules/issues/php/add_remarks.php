<?php
require_once '../../php/config.php';
header('Content-Type: application/json');

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Only allow staff and admin
if (!in_array($user_role, ['staff', 'admin'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied'
    ]);
    exit;
}

try {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token');
    }
    
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
    $remarks = trim($_POST['remarks'] ?? '');
    
    if (!$report_id || empty($remarks)) {
        throw new Exception('Please provide all required information');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update issue report remarks
    $stmt = $pdo->prepare("
        UPDATE issue_reports 
        SET remarks = CONCAT(COALESCE(remarks, ''), '\n\n[' , NOW(), '] ', ?),
            updated_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$remarks, $report_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Report not found');
    }
    
    // Insert history record
    $stmt = $pdo->prepare("
        INSERT INTO issue_history (issue_id, action, description, performed_by)
        VALUES (?, 'remark_added', ?, ?)
    ");
    $stmt->execute([$report_id, $remarks, $user_id]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Remarks added successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Add remarks error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
