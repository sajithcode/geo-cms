<?php
require_once '../../php/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    error_log("Labs API: User not logged in. Session data: " . print_r($_SESSION, true));
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'submit_reservation':
            handleSubmitReservation();
            break;
        case 'cancel_reservation':
            handleCancelReservation();
            break;
        case 'get_timetable':
            handleGetTimetable();
            break;
        case 'get_reservation_details':
            handleGetReservationDetails();
            break;
        case 'refresh_lab_status':
            handleRefreshLabStatus();
            break;
        case 'report_issue':
            handleReportIssue();
            break;
        case 'approve_reservation':
            handleApproveReservation();
            break;
        case 'reject_reservation':
            handleRejectReservation();
            break;
        case 'manage_lab':
            handleManageLab();
            break;
        case 'update_lab_status':
            handleUpdateLabStatus();
            break;
        case 'assign_issue':
            handleAssignIssue();
            break;
        case 'update_issue_status':
            handleUpdateIssueStatus();
            break;
        case 'upload_timetable':
            handleUploadTimetable();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    error_log("Labs API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleSubmitReservation() {
    global $pdo, $user_id;
    
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    $lab_id = (int)$_POST['lab_id'];
    $reservation_date = $_POST['reservation_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = trim($_POST['purpose']);
    
    // Validation
    if (empty($lab_id) || empty($reservation_date) || empty($start_time) || 
        empty($end_time) || empty($purpose)) {
        throw new Exception('All required fields must be filled');
    }
    
    // Check if lab exists and is available
    $stmt = $pdo->prepare("SELECT * FROM labs WHERE id = ? AND status = 'available'");
    $stmt->execute([$lab_id]);
    $lab = $stmt->fetch();
    
    if (!$lab) {
        throw new Exception('Lab not found or not available');
    }
    
    // Check for time conflicts
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM lab_reservations 
        WHERE lab_id = ? 
        AND reservation_date = ? 
        AND status IN ('approved', 'pending')
        AND (
            (start_time < ? AND end_time > ?) OR
            (start_time < ? AND end_time > ?) OR
            (start_time >= ? AND end_time <= ?)
        )
    ");
    $stmt->execute([
        $lab_id, $reservation_date,
        $end_time, $start_time,    // New reservation ends after existing starts and new starts before existing ends
        $start_time, $end_time,    // New reservation starts before existing ends and new ends after existing starts
        $start_time, $end_time     // New reservation is within existing reservation
    ]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Time conflict: Lab is already booked for the requested time slot');
    }
    
    // Insert reservation
    $stmt = $pdo->prepare("
        INSERT INTO lab_reservations 
        (lab_id, user_id, purpose, reservation_date, start_time, end_time) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $lab_id, $user_id, $purpose, $reservation_date, $start_time, $end_time
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Lab reservation request submitted successfully'
        ]);
    } else {
        throw new Exception('Failed to submit reservation request');
    }
}

function handleCancelReservation() {
    global $pdo, $user_id, $user_role;
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    $reservation_id = (int)$_POST['reservation_id'];
    
    // Check if reservation exists and belongs to user (or user is admin/staff)
    $stmt = $pdo->prepare("
        SELECT lr.*, l.name as lab_name 
        FROM lab_reservations lr 
        JOIN labs l ON lr.lab_id = l.id 
        WHERE lr.id = ?
    ");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        throw new Exception('Reservation not found');
    }
    
    // Check permissions
    if ($reservation['user_id'] != $user_id && !in_array($user_role, ['admin', 'staff'])) {
        throw new Exception('You can only cancel your own reservations');
    }
    
    // Check if reservation can be cancelled (not already completed)
    if ($reservation['status'] === 'completed') {
        throw new Exception('Cannot cancel completed reservations');
    }
    
    // Update reservation status
    $stmt = $pdo->prepare("UPDATE lab_reservations SET status = 'cancelled' WHERE id = ?");
    $result = $stmt->execute([$reservation_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Reservation cancelled successfully'
        ]);
    } else {
        throw new Exception('Failed to cancel reservation');
    }
}

function handleGetTimetable() {
    global $pdo;
    
    $lab_id = (int)($_GET['lab_id'] ?? 0);
    
    if (!$lab_id) {
        throw new Exception('Lab ID is required');
    }
    
    // Get lab info
    $stmt = $pdo->prepare("SELECT * FROM labs WHERE id = ?");
    $stmt->execute([$lab_id]);
    $lab = $stmt->fetch();
    
    if (!$lab) {
        throw new Exception('Lab not found');
    }
    
    // Get timetable (using existing table structure)
    $stmt = $pdo->prepare("
        SELECT lt.*, u.name as lecturer_name
        FROM lab_timetables lt
        LEFT JOIN users u ON lt.lecturer_id = u.id
        WHERE lt.lab_id = ?
        ORDER BY 
            CASE lt.day_of_week
                WHEN 'monday' THEN 1
                WHEN 'tuesday' THEN 2
                WHEN 'wednesday' THEN 3
                WHEN 'thursday' THEN 4
                WHEN 'friday' THEN 5
                WHEN 'saturday' THEN 6
                WHEN 'sunday' THEN 7
            END,
            lt.start_time ASC
    ");
    $stmt->execute([$lab_id]);
    $timetable = $stmt->fetchAll();
    
    // Get current reservations for the week
    $stmt = $pdo->prepare("
        SELECT lr.*, u.name as requester_name
        FROM lab_reservations lr
        JOIN users u ON lr.user_id = u.id
        WHERE lr.lab_id = ? 
        AND lr.status = 'approved'
        AND lr.reservation_date >= CURDATE()
        AND lr.reservation_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY lr.reservation_date ASC, lr.start_time ASC
    ");
    $stmt->execute([$lab_id]);
    $reservations = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'lab' => $lab,
        'timetable' => $timetable,
        'reservations' => $reservations
    ]);
}

function handleGetReservationDetails() {
    global $pdo, $user_id, $user_role;
    
    $reservation_id = (int)($_GET['reservation_id'] ?? 0);
    
    if (!$reservation_id) {
        throw new Exception('Reservation ID is required');
    }
    
    $stmt = $pdo->prepare("
        SELECT lr.*, l.name as lab_name, l.code as lab_code, l.location,
               u.name as requester_name, u.user_id as requester_id, u.email as requester_email,
               approved_by.name as approved_by_name
        FROM lab_reservations lr
        JOIN labs l ON lr.lab_id = l.id
        JOIN users u ON lr.user_id = u.id
        LEFT JOIN users approved_by ON lr.approved_by = approved_by.id
        WHERE lr.id = ?
    ");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        throw new Exception('Reservation not found');
    }
    
    // Check permissions (user can view their own reservations, admin/staff can view all)
    if ($reservation['user_id'] != $user_id && !in_array($user_role, ['admin', 'staff'])) {
        throw new Exception('Permission denied');
    }
    
    echo json_encode([
        'success' => true,
        'reservation' => $reservation
    ]);
}

function handleRefreshLabStatus() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT l.*, 
               COUNT(CASE WHEN lr.status = 'approved' AND lr.reservation_date = CURDATE() 
                          AND lr.start_time <= CURTIME() AND lr.end_time >= CURTIME() THEN 1 END) as current_bookings,
               COUNT(CASE WHEN lr.status = 'pending' THEN 1 END) as pending_requests
        FROM labs l
        LEFT JOIN lab_reservations lr ON l.id = lr.lab_id
        GROUP BY l.id
        ORDER BY l.code ASC
    ");
    $labs = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'labs' => $labs
    ]);
}

function handleReportIssue() {
    global $pdo, $user_id;
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    $lab_id = (int)$_POST['lab_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $issue_type = $_POST['issue_type'];
    $priority = $_POST['priority'];
    
    // Validation
    if (empty($lab_id) || empty($title) || empty($description) || empty($issue_type)) {
        throw new Exception('All required fields must be filled');
    }
    
    $valid_types = ['maintenance', 'equipment_fault', 'safety_concern', 'facility_issue', 'other'];
    $valid_priorities = ['low', 'medium', 'high', 'critical'];
    
    if (!in_array($issue_type, $valid_types) || !in_array($priority, $valid_priorities)) {
        throw new Exception('Invalid issue type or priority');
    }
    
    // Check if lab exists
    $stmt = $pdo->prepare("SELECT id FROM labs WHERE id = ?");
    $stmt->execute([$lab_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Lab not found');
    }
    
    // Insert issue
    $stmt = $pdo->prepare("
        INSERT INTO lab_issues 
        (lab_id, title, description, issue_type, priority, reported_by) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([$lab_id, $title, $description, $issue_type, $priority, $user_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Issue reported successfully'
        ]);
    } else {
        throw new Exception('Failed to report issue');
    }
}

function handleApproveReservation() {
    global $pdo, $user_id, $user_role;
    
    // Only admin and staff can approve
    if (!in_array($user_role, ['admin', 'staff'])) {
        throw new Exception('Permission denied');
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    $reservation_id = (int)$_POST['reservation_id'];
    $notes = trim($_POST['notes'] ?? '');
    
    $stmt = $pdo->prepare("
        UPDATE lab_reservations 
        SET status = 'approved', approved_by = ?, approval_date = NOW(), approval_notes = ?
        WHERE id = ? AND status = 'pending'
    ");
    
    $result = $stmt->execute([$user_id, $notes, $reservation_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Reservation approved successfully'
        ]);
    } else {
        throw new Exception('Failed to approve reservation or reservation not found');
    }
}

function handleRejectReservation() {
    global $pdo, $user_id, $user_role;
    
    // Only admin and staff can reject
    if (!in_array($user_role, ['admin', 'staff'])) {
        throw new Exception('Permission denied');
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    $reservation_id = (int)$_POST['reservation_id'];
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($reason)) {
        throw new Exception('Rejection reason is required');
    }
    
    $stmt = $pdo->prepare("
        UPDATE lab_reservations 
        SET status = 'rejected', approved_by = ?, approval_date = NOW(), rejection_reason = ?
        WHERE id = ? AND status = 'pending'
    ");
    
    $result = $stmt->execute([$user_id, $reason, $reservation_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Reservation rejected successfully'
        ]);
    } else {
        throw new Exception('Failed to reject reservation or reservation not found');
    }
}

function handleManageLab() {
    global $pdo, $user_role;
    
    error_log("Labs API: handleManageLab called. User role: $user_role");
    
    // Only admin can manage labs
    if ($user_role !== 'admin') {
        error_log("Labs API: Permission denied. User role: $user_role");
        throw new Exception('Permission denied - Admin access required');
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        error_log("Labs API: Invalid CSRF token");
        throw new Exception('Invalid CSRF token');
    }
    
    $lab_id = (int)($_POST['lab_id'] ?? 0);
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    $description = trim($_POST['description'] ?? '');
    $capacity = (int)$_POST['capacity'];
    $location = trim($_POST['location'] ?? '');
    $status = $_POST['status'];
    $equipment_list = trim($_POST['equipment_list'] ?? '');
    $safety_guidelines = trim($_POST['safety_guidelines'] ?? '');
    
    // Validation
    if (empty($name) || empty($code) || $capacity < 1) {
        throw new Exception('Name, code, and capacity are required');
    }
    
    $valid_statuses = ['available', 'maintenance', 'offline'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid lab status');
    }
    
    if ($lab_id) {
        // Update existing lab
        $stmt = $pdo->prepare("
            UPDATE labs 
            SET name = ?, code = ?, description = ?, capacity = ?, location = ?, 
                status = ?, equipment_list = ?, safety_guidelines = ?
            WHERE id = ?
        ");
        $result = $stmt->execute([
            $name, $code, $description, $capacity, $location, 
            $status, $equipment_list, $safety_guidelines, $lab_id
        ]);
        $message = 'Lab updated successfully';
    } else {
        // Create new lab
        $stmt = $pdo->prepare("
            INSERT INTO labs 
            (name, code, description, capacity, location, status, equipment_list, safety_guidelines) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $name, $code, $description, $capacity, $location, 
            $status, $equipment_list, $safety_guidelines
        ]);
        $message = 'Lab created successfully';
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        throw new Exception('Failed to save lab');
    }
}

function handleUpdateLabStatus() {
    global $pdo, $user_role;
    
    // Only admin and staff can update lab status
    if (!in_array($user_role, ['admin', 'staff'])) {
        throw new Exception('Permission denied');
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    $lab_id = (int)$_POST['lab_id'];
    $status = $_POST['status'];
    
    $valid_statuses = ['available', 'maintenance', 'offline'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid lab status');
    }
    
    $stmt = $pdo->prepare("UPDATE labs SET status = ? WHERE id = ?");
    $result = $stmt->execute([$status, $lab_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Lab status updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update lab status');
    }
}

function handleAssignIssue() {
    global $pdo, $user_role;
    
    // Only admin and staff can assign issues
    if (!in_array($user_role, ['admin', 'staff'])) {
        throw new Exception('Permission denied');
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    $issue_id = (int)$_POST['issue_id'];
    $assigned_to = (int)$_POST['assigned_to'];
    
    $stmt = $pdo->prepare("
        UPDATE lab_issues 
        SET assigned_to = ?, status = 'in_progress' 
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$assigned_to, $issue_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Issue assigned successfully'
        ]);
    } else {
        throw new Exception('Failed to assign issue');
    }
}

function handleUpdateIssueStatus() {
    global $pdo, $user_id, $user_role;
    
    // Only admin, staff, and assigned person can update issue status
    if (!in_array($user_role, ['admin', 'staff'])) {
        throw new Exception('Permission denied');
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    $issue_id = (int)$_POST['issue_id'];
    $status = $_POST['status'];
    $resolution_notes = trim($_POST['resolution_notes'] ?? '');
    
    $valid_statuses = ['open', 'in_progress', 'resolved', 'closed'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid issue status');
    }
    
    // Prepare update query
    if ($status === 'resolved') {
        $stmt = $pdo->prepare("
            UPDATE lab_issues 
            SET status = ?, resolved_by = ?, resolved_date = NOW(), 
                resolution_notes = ?, actual_fix_time = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$status, $user_id, $resolution_notes, $issue_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE lab_issues 
            SET status = ?, resolution_notes = ?
            WHERE id = ?
        ");
        $result = $stmt->execute([$status, $resolution_notes, $issue_id]);
    }
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Issue status updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update issue status');
    }
}

function handleUploadTimetable() {
    global $pdo, $user_role;
    
    // Only admin can upload timetables
    if ($user_role !== 'admin') {
        throw new Exception('Permission denied');
    }
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    $lab_id = (int)$_POST['lab_id'];
    
    if (!isset($_FILES['timetable_file']) || $_FILES['timetable_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }
    
    $file = $_FILES['timetable_file'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, ['csv', 'xlsx', 'xls'])) {
        throw new Exception('Invalid file format. Only CSV and Excel files are supported.');
    }
    
    // Process the file based on type
    if ($file_extension === 'csv') {
        processCsvTimetable($file['tmp_name'], $lab_id);
    } else {
        processExcelTimetable($file['tmp_name'], $lab_id);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Timetable uploaded and processed successfully'
    ]);
}

function processCsvTimetable($file_path, $lab_id) {
    global $pdo, $user_id;
    
    $handle = fopen($file_path, 'r');
    if (!$handle) {
        throw new Exception('Cannot open CSV file');
    }
    
    // Read header row
    $header = fgetcsv($handle);
    
    // Clear existing timetable for this lab
    $stmt = $pdo->prepare("UPDATE lab_timetables SET is_active = 0 WHERE lab_id = ?");
    $stmt->execute([$lab_id]);
    
    $stmt = $pdo->prepare("
        INSERT INTO lab_timetables 
        (lab_id, title, description, day_of_week, start_time, end_time, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($row) >= 5) {
            $day = trim($row[0]);
            $start_time = trim($row[1]);
            $end_time = trim($row[2]);
            $title = trim($row[3]);
            $description = trim($row[4] ?? '');
            
            // Validate day
            $valid_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            if (in_array($day, $valid_days)) {
                $stmt->execute([$lab_id, $title, $description, $day, $start_time, $end_time, $user_id]);
            }
        }
    }
    
    fclose($handle);
}

function processExcelTimetable($file_path, $lab_id) {
    // This would require a library like PhpSpreadsheet
    // For now, we'll just throw an exception
    throw new Exception('Excel file processing not implemented yet. Please use CSV format.');
}
?>