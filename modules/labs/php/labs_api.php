<?php
require_once '../../php/config.php';

// Require user to be logged in
requireLogin();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

try {
    switch ($action) {
        // Lab Management (Admin only)
        case 'manage_lab':
            if ($user_role !== 'admin') {
                throw new Exception('Unauthorized access');
            }
            
            $lab_id = $_POST['lab_id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $capacity = intval($_POST['capacity'] ?? 30);
            $status = $_POST['status'] ?? 'available';
            
            if (empty($name)) {
                throw new Exception('Lab name is required');
            }
            
            if (!in_array($status, ['available', 'in_use', 'maintenance'])) {
                throw new Exception('Invalid status');
            }
            
            if ($lab_id) {
                // Update existing lab
                $stmt = $pdo->prepare("
                    UPDATE labs 
                    SET name = ?, description = ?, capacity = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $capacity, $status, $lab_id]);
                $message = 'Lab updated successfully';
            } else {
                // Create new lab
                $stmt = $pdo->prepare("
                    INSERT INTO labs (name, description, capacity, status)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $capacity, $status]);
                $message = 'Lab created successfully';
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
            break;

        // Update lab status (Admin only)
        case 'update_lab_status':
            if ($user_role !== 'admin') {
                throw new Exception('Unauthorized access');
            }
            
            $lab_id = $_POST['lab_id'] ?? null;
            $status = $_POST['status'] ?? '';
            
            if (!$lab_id || !in_array($status, ['available', 'in_use', 'maintenance'])) {
                throw new Exception('Invalid data');
            }
            
            $stmt = $pdo->prepare("UPDATE labs SET status = ? WHERE id = ?");
            $stmt->execute([$status, $lab_id]);
            
            echo json_encode(['success' => true, 'message' => 'Lab status updated successfully']);
            break;

        // Submit reservation request
        case 'submit_reservation':
            $lab_id = intval($_POST['lab_id'] ?? 0);
            $reservation_date = $_POST['reservation_date'] ?? '';
            $start_time = $_POST['start_time'] ?? '';
            $end_time = $_POST['end_time'] ?? '';
            $purpose = trim($_POST['purpose'] ?? '');
            
            if (!$lab_id || !$reservation_date || !$start_time || !$end_time || !$purpose) {
                throw new Exception('All fields are required');
            }
            
            // Validate date is in the future
            if (strtotime($reservation_date) < strtotime(date('Y-m-d'))) {
                throw new Exception('Reservation date must be in the future');
            }
            
            // Validate time range
            if (strtotime($start_time) >= strtotime($end_time)) {
                throw new Exception('End time must be after start time');
            }
            
            // Check if lab exists and is available
            $stmt = $pdo->prepare("SELECT status FROM labs WHERE id = ?");
            $stmt->execute([$lab_id]);
            $lab = $stmt->fetch();
            
            if (!$lab) {
                throw new Exception('Lab not found');
            }
            
            // Check for overlapping reservations
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM lab_reservations
                WHERE lab_id = ?
                AND reservation_date = ?
                AND status IN ('pending', 'approved')
                AND (
                    (start_time <= ? AND end_time > ?) OR
                    (start_time < ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )
            ");
            $stmt->execute([
                $lab_id, $reservation_date,
                $start_time, $start_time,
                $end_time, $end_time,
                $start_time, $end_time
            ]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('This time slot is already reserved. Please choose a different time.');
            }
            
            // Insert reservation
            $stmt = $pdo->prepare("
                INSERT INTO lab_reservations 
                (lab_id, user_id, reservation_date, start_time, end_time, purpose, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$lab_id, $user_id, $reservation_date, $start_time, $end_time, $purpose]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Reservation request submitted successfully. Awaiting approval.'
            ]);
            break;

        // Approve reservation (Admin/Staff only)
        case 'approve_reservation':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $reservation_id = intval($_POST['reservation_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            
            if (!$reservation_id) {
                throw new Exception('Reservation ID is required');
            }
            
            // Check if reservation exists and is pending
            $stmt = $pdo->prepare("SELECT status FROM lab_reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch();
            
            if (!$reservation) {
                throw new Exception('Reservation not found');
            }
            
            if ($reservation['status'] !== 'pending') {
                throw new Exception('Only pending reservations can be approved');
            }
            
            $stmt = $pdo->prepare("
                UPDATE lab_reservations 
                SET status = 'approved', approved_by = ?, approved_date = NOW(), notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$user_id, $notes, $reservation_id]);
            
            echo json_encode(['success' => true, 'message' => 'Reservation approved successfully']);
            break;

        // Reject reservation (Admin/Staff only)
        case 'reject_reservation':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $reservation_id = intval($_POST['reservation_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            
            if (!$reservation_id) {
                throw new Exception('Reservation ID is required');
            }
            
            if (empty($reason)) {
                throw new Exception('Rejection reason is required');
            }
            
            $stmt = $pdo->prepare("
                UPDATE lab_reservations 
                SET status = 'rejected', approved_by = ?, notes = ?
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$user_id, $reason, $reservation_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Reservation not found or already processed');
            }
            
            echo json_encode(['success' => true, 'message' => 'Reservation rejected']);
            break;

        // Cancel reservation (by requester)
        case 'cancel_reservation':
            $reservation_id = intval($_POST['reservation_id'] ?? 0);
            
            if (!$reservation_id) {
                throw new Exception('Reservation ID is required');
            }
            
            // Check if user owns this reservation
            $stmt = $pdo->prepare("
                SELECT status FROM lab_reservations 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$reservation_id, $user_id]);
            $reservation = $stmt->fetch();
            
            if (!$reservation) {
                throw new Exception('Reservation not found or you do not have permission to cancel it');
            }
            
            if (!in_array($reservation['status'], ['pending', 'approved'])) {
                throw new Exception('Only pending or approved reservations can be cancelled');
            }
            
            $stmt = $pdo->prepare("
                DELETE FROM lab_reservations 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$reservation_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Reservation cancelled successfully']);
            break;

        // Get reservation details
        case 'get_reservation_details':
            $reservation_id = intval($_GET['reservation_id'] ?? 0);
            
            if (!$reservation_id) {
                throw new Exception('Reservation ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT lr.*, l.name as lab_name, l.capacity,
                       u.name as requester_name, u.user_id as requester_id, u.role as requester_role,
                       approver.name as approved_by_name
                FROM lab_reservations lr
                JOIN labs l ON lr.lab_id = l.id
                JOIN users u ON lr.user_id = u.id
                LEFT JOIN users approver ON lr.approved_by = approver.id
                WHERE lr.id = ?
            ");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch();
            
            if (!$reservation) {
                throw new Exception('Reservation not found');
            }
            
            // Check permission to view
            if ($user_role !== 'admin' && $user_role !== 'staff' && $reservation['user_id'] != $user_id) {
                throw new Exception('Unauthorized access');
            }
            
            echo json_encode(['success' => true, 'reservation' => $reservation]);
            break;

        // Report issue
        case 'report_issue':
            $lab_id = intval($_POST['lab_id'] ?? 0);
            $computer_serial_no = trim($_POST['computer_serial_no'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($description)) {
                throw new Exception('Issue description is required');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO issue_reports (user_id, reported_by, lab_id, computer_serial_no, description, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $user_id,
                $user_id,
                $lab_id ?: null,
                $computer_serial_no ?: null,
                $description
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Issue reported successfully']);
            break;

        // Get timetable
        case 'get_timetable':
            $lab_id = intval($_GET['lab_id'] ?? 0);
            
            if (!$lab_id) {
                throw new Exception('Lab ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT lt.*, l.name as lab_name, u.name as lecturer_name
                FROM lab_timetables lt
                JOIN labs l ON lt.lab_id = l.id
                LEFT JOIN users u ON lt.lecturer_id = u.id
                WHERE lt.lab_id = ?
                ORDER BY 
                    FIELD(lt.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'),
                    lt.start_time ASC
            ");
            $stmt->execute([$lab_id]);
            $timetable = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'timetable' => $timetable]);
            break;

        // Upload timetable (Admin only)
        case 'upload_timetable':
            if ($user_role !== 'admin') {
                throw new Exception('Unauthorized access');
            }
            
            $lab_id = intval($_POST['lab_id'] ?? 0);
            
            if (!$lab_id) {
                throw new Exception('Lab ID is required');
            }
            
            if (!isset($_FILES['timetable_file']) || $_FILES['timetable_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Please upload a valid file');
            }
            
            $file = $_FILES['timetable_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, ['csv'])) {
                throw new Exception('Only CSV files are supported');
            }
            
            // Read and parse CSV file
            $csv_data = file_get_contents($file['tmp_name']);
            $lines = str_getcsv($csv_data, "\n");
            
            if (empty($lines)) {
                throw new Exception('CSV file is empty');
            }
            
            // Get header row
            $header = str_getcsv(array_shift($lines));
            $expected_columns = ['Day', 'Start Time', 'End Time', 'Subject', 'Lecturer', 'Batch'];
            
            // Validate header
            foreach ($expected_columns as $col) {
                if (!in_array($col, $header)) {
                    throw new Exception("Missing required column: $col");
                }
            }
            
            $day_mapping = [
                'monday' => 'monday',
                'tuesday' => 'tuesday', 
                'wednesday' => 'wednesday',
                'thursday' => 'thursday',
                'friday' => 'friday'
            ];
            
            $processed_count = 0;
            $error_count = 0;
            $errors = [];
            
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // Clear existing timetable for this lab
                $stmt = $pdo->prepare("DELETE FROM lab_timetables WHERE lab_id = ?");
                $stmt->execute([$lab_id]);
                
                foreach ($lines as $line_num => $line) {
                    $data = str_getcsv($line);
                    
                    if (count($data) < count($expected_columns)) {
                        $errors[] = "Line " . ($line_num + 2) . ": Insufficient columns";
                        $error_count++;
                        continue;
                    }
                    
                    // Map data to columns
                    $row_data = array_combine($header, $data);
                    
                    // Validate and process data
                    $day = strtolower(trim($row_data['Day']));
                    $start_time = trim($row_data['Start Time']);
                    $end_time = trim($row_data['End Time']);
                    $subject = trim($row_data['Subject']);
                    $lecturer_name = trim($row_data['Lecturer']);
                    $batch = trim($row_data['Batch']);
                    $semester = trim($row_data['Semester'] ?? '');
                    
                    // Validate day
                    if (!isset($day_mapping[$day])) {
                        $errors[] = "Line " . ($line_num + 2) . ": Invalid day '$day'";
                        $error_count++;
                        continue;
                    }
                    
                    // Validate time format
                    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time)) {
                        $errors[] = "Line " . ($line_num + 2) . ": Invalid start time format '$start_time'";
                        $error_count++;
                        continue;
                    }
                    
                    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
                        $errors[] = "Line " . ($line_num + 2) . ": Invalid end time format '$end_time'";
                        $error_count++;
                        continue;
                    }
                    
                    // Find lecturer ID
                    $lecturer_id = null;
                    if (!empty($lecturer_name)) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE name LIKE ? AND role = 'lecturer' LIMIT 1");
                        $stmt->execute(['%' . $lecturer_name . '%']);
                        $lecturer = $stmt->fetch();
                        if ($lecturer) {
                            $lecturer_id = $lecturer['id'];
                        }
                    }
                    
                    // Insert timetable entry
                    $stmt = $pdo->prepare("
                        INSERT INTO lab_timetables (lab_id, day_of_week, start_time, end_time, lecturer_id, subject, semester, batch)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $lab_id,
                        $day_mapping[$day],
                        $start_time,
                        $end_time,
                        $lecturer_id,
                        $subject,
                        $semester,
                        $batch
                    ]);
                    
                    $processed_count++;
                }
                
                // Commit transaction
                $pdo->commit();
                
                $message = "Timetable uploaded successfully! Processed: $processed_count entries";
                if ($error_count > 0) {
                    $message .= ", Errors: $error_count";
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'processed' => $processed_count,
                    'errors' => $error_count,
                    'error_details' => array_slice($errors, 0, 10) // Limit error details
                ]);
                
            } catch (Exception $e) {
                $pdo->rollback();
                throw new Exception('Database error during upload: ' . $e->getMessage());
            }
            break;

        // Assign issue (Admin/Staff only)
        case 'assign_issue':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $issue_id = intval($_POST['issue_id'] ?? 0);
            $assigned_to = intval($_POST['assigned_to'] ?? 0);
            
            if (!$issue_id) {
                throw new Exception('Issue ID is required');
            }
            
            $stmt = $pdo->prepare("
                UPDATE issue_reports 
                SET assigned_to = ?, status = 'in_progress'
                WHERE id = ?
            ");
            $stmt->execute([$assigned_to ?: null, $issue_id]);
            
            echo json_encode(['success' => true, 'message' => 'Issue assigned successfully']);
            break;

        // Update issue status (Admin/Staff only)
        case 'update_issue_status':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $issue_id = intval($_POST['issue_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if (!$issue_id || !in_array($status, ['pending', 'in_progress', 'resolved'])) {
                throw new Exception('Invalid data');
            }
            
            $resolved_at = $status === 'resolved' ? date('Y-m-d H:i:s') : null;
            
            $stmt = $pdo->prepare("
                UPDATE issue_reports 
                SET status = ?, resolved_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$status, $resolved_at, $issue_id]);
            
            echo json_encode(['success' => true, 'message' => 'Issue status updated successfully']);
            break;

        // Get issue details
        case 'get_issue_details':
            $issue_id = intval($_GET['issue_id'] ?? 0);
            
            if (!$issue_id) {
                throw new Exception('Issue ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT ir.*, l.name as lab_name, 
                       u.name as reporter_name,
                       assigned.name as assigned_to_name
                FROM issue_reports ir
                LEFT JOIN labs l ON ir.lab_id = l.id
                JOIN users u ON ir.reported_by = u.id
                LEFT JOIN users assigned ON ir.assigned_to = assigned.id
                WHERE ir.id = ?
            ");
            $stmt->execute([$issue_id]);
            $issue = $stmt->fetch();
            
            if (!$issue) {
                throw new Exception('Issue not found');
            }
            
            echo json_encode(['success' => true, 'issue' => $issue]);
            break;

        // Refresh lab status (Auto-update based on current time and reservations)
        case 'refresh_lab_status':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $current_date = date('Y-m-d');
            $current_time = date('H:i:s');
            
            // Update labs that have active reservations to 'in_use'
            $stmt = $pdo->prepare("
                UPDATE labs l
                SET l.status = 'in_use'
                WHERE l.id IN (
                    SELECT DISTINCT lr.lab_id
                    FROM lab_reservations lr
                    WHERE lr.status = 'approved'
                    AND lr.reservation_date = ?
                    AND lr.start_time <= ?
                    AND lr.end_time >= ?
                )
                AND l.status != 'maintenance'
            ");
            $stmt->execute([$current_date, $current_time, $current_time]);
            
            // Update labs without active reservations to 'available'
            $stmt = $pdo->prepare("
                UPDATE labs l
                SET l.status = 'available'
                WHERE l.status = 'in_use'
                AND l.id NOT IN (
                    SELECT DISTINCT lr.lab_id
                    FROM lab_reservations lr
                    WHERE lr.status = 'approved'
                    AND lr.reservation_date = ?
                    AND lr.start_time <= ?
                    AND lr.end_time >= ?
                )
            ");
            $stmt->execute([$current_date, $current_time, $current_time]);
            
            echo json_encode(['success' => true, 'message' => 'Lab status refreshed successfully']);
            break;

        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Labs API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred. Please try again later.'
    ]);
}
