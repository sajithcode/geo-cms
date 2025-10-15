<?php
require_once '../../php/config.php';
header('Content-Type: application/json');

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Only allow admin
if ($user_role !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only administrators can delete reports.'
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
    
    // Get file path before deleting
    $stmt = $pdo->prepare("SELECT file_path FROM issue_reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    if (!$report) {
        throw new Exception('Report not found');
    }
    
    // Delete the report (cascading will handle related records)
    $stmt = $pdo->prepare("DELETE FROM issue_reports WHERE id = ?");
    $stmt->execute([$report_id]);
    
    // Delete uploaded file if exists
    if ($report['file_path'] && file_exists('../../' . $report['file_path'])) {
        unlink('../../' . $report['file_path']);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Report deleted successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Delete issue error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
