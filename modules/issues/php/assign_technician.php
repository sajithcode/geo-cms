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
    $technician_id = filter_input(INPUT_POST, 'technician_id', FILTER_VALIDATE_INT);
    
    if (!$report_id || !$technician_id) {
        throw new Exception('Invalid data provided');
    }
    
    // Verify technician exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ? AND role IN ('staff', 'admin')");
    $stmt->execute([$technician_id]);
    $technician = $stmt->fetch();
    
    if (!$technician) {
        throw new Exception('Invalid technician selected');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update issue report
    $stmt = $pdo->prepare("
        UPDATE issue_reports 
        SET assigned_to = ?, 
            status = CASE WHEN status = 'pending' THEN 'in_progress' ELSE status END,
            updated_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$technician_id, $report_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Report not found');
    }
    
    // Insert history record
    $stmt = $pdo->prepare("
        INSERT INTO issue_history (issue_id, action, description, performed_by)
        VALUES (?, 'assigned', ?, ?)
    ");
    $stmt->execute([
        $report_id,
        'Assigned to ' . $technician['name'],
        $user_id
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Technician assigned successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Assign technician error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
