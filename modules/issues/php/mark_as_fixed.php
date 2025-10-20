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
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verify CSRF token
    if (!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
        throw new Exception('Invalid security token');
    }
    
    $report_id = filter_var($data['report_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$report_id) {
        throw new Exception('Invalid report ID');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update issue report
    $stmt = $pdo->prepare("
        UPDATE issue_reports 
        SET status = 'resolved',
            resolved_by = ?,
            resolved_date = NOW(),
            updated_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$user_id, $report_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Report not found');
    }
    
    // Insert history record
    $stmt = $pdo->prepare("
        INSERT INTO issue_history (issue_id, action, description, performed_by)
        VALUES (?, 'resolved', 'Issue marked as resolved', ?)
    ");
    $stmt->execute([$report_id, $user_id]);
    
    // Get reporter information for notification
    $stmt = $pdo->prepare("
        SELECT ir.report_id, ir.reported_by, u.name as reporter_name, u.email as reporter_email
        FROM issue_reports ir
        LEFT JOIN users u ON ir.reported_by = u.id
        WHERE ir.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    // TODO: Send notification to reporter
    // This can be implemented later with email or in-app notifications
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Issue marked as resolved successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Mark as fixed error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
