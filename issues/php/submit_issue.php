<?php
require_once '../../php/config.php';
header('Content-Type: application/json');

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

try {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid security token');
    }
    
    // Get form data
    $lab_id = filter_input(INPUT_POST, 'lab_id', FILTER_VALIDATE_INT);
    $issue_category = filter_input(INPUT_POST, 'issue_category', FILTER_SANITIZE_STRING);
    $description = trim($_POST['description'] ?? '');
    $computer_serial_no = trim($_POST['computer_serial_no'] ?? '');
    $affected_computers = $_POST['affected_computers'] ?? [];
    
    // Validate required fields
    if (!$lab_id || !$issue_category || empty($description)) {
        throw new Exception('Please fill in all required fields');
    }
    
    // Validate issue category
    $valid_categories = ['hardware', 'software', 'network', 'projector', 'other'];
    if (!in_array($issue_category, $valid_categories)) {
        throw new Exception('Invalid issue category');
    }
    
    // For students, computer serial number is required
    if ($user_role === 'student' && empty($computer_serial_no)) {
        throw new Exception('Computer serial number is required');
    }
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/issues/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = pathinfo($_FILES['file_upload']['name']);
        $file_ext = strtolower($file_info['extension']);
        
        // Validate file type
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (!in_array($file_ext, $allowed_extensions)) {
            throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF, PDF');
        }
        
        // Validate file size (5MB max)
        if ($_FILES['file_upload']['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size must be less than 5MB');
        }
        
        $file_name = uniqid('issue_') . '_' . time() . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        if (!move_uploaded_file($_FILES['file_upload']['tmp_name'], $file_path)) {
            throw new Exception('Failed to upload file');
        }
        
        // Store relative path
        $file_path = 'uploads/issues/' . $file_name;
    }
    
    // Generate unique report ID
    $report_id = 'ISS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Check if report ID exists, regenerate if needed
    $stmt = $pdo->prepare("SELECT id FROM issue_reports WHERE report_id = ?");
    $stmt->execute([$report_id]);
    while ($stmt->fetch()) {
        $report_id = 'ISS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt->execute([$report_id]);
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert issue report
    $stmt = $pdo->prepare("
        INSERT INTO issue_reports (
            report_id, computer_serial_no, lab_id, issue_category, 
            description, file_path, status, reported_by
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
    ");
    
    $stmt->execute([
        $report_id,
        $computer_serial_no ?: null,
        $lab_id,
        $issue_category,
        $description,
        $file_path,
        $user_id
    ]);
    
    $issue_id = $pdo->lastInsertId();
    
    // For lecturers, insert affected computers
    if ($user_role === 'lecturer' && !empty($affected_computers) && is_array($affected_computers)) {
        $stmt = $pdo->prepare("INSERT INTO issue_affected_computers (issue_id, computer_serial_no) VALUES (?, ?)");
        
        foreach ($affected_computers as $serial_no) {
            $serial_no = trim($serial_no);
            if (!empty($serial_no)) {
                $stmt->execute([$issue_id, $serial_no]);
            }
        }
    }
    
    // Insert history record
    $stmt = $pdo->prepare("
        INSERT INTO issue_history (issue_id, action, description, performed_by)
        VALUES (?, 'created', 'Issue report created', ?)
    ");
    $stmt->execute([$issue_id, $user_id]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Issue report submitted successfully',
        'report_id' => $report_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Delete uploaded file if exists
    if (isset($file_path) && file_exists('../../' . $file_path)) {
        unlink('../../' . $file_path);
    }
    
    error_log("Submit issue error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
