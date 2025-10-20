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
        // Report new issue
        case 'report_issue':
            $lab_id = intval($_POST['lab_id'] ?? 0);
            $computer_number = trim($_POST['computer_number'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $priority = $_POST['priority'] ?? 'medium';
            $category = $_POST['category'] ?? 'other';
            $contact_info = trim($_POST['contact_info'] ?? '');
            
            if (empty($description)) {
                throw new Exception('Issue description is required');
            }
            
            if (!in_array($priority, ['low', 'medium', 'high', 'critical'])) {
                $priority = 'medium';
            }
            
            if (!in_array($category, ['hardware', 'software', 'network', 'lab_equipment', 'other'])) {
                $category = 'other';
            }
            
            // Handle file upload
            $screenshot_path = null;
            if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/issues/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file = $_FILES['screenshot'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $filename = 'issue_' . time() . '_' . $user_id . '.' . $ext;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $screenshot_path = 'uploads/issues/' . $filename;
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO issue_reports 
                (user_id, lab_id, computer_number, description, priority, category, contact_info, screenshot, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $user_id,
                $lab_id ?: null,
                $computer_number ?: null,
                $description,
                $priority,
                $category,
                $contact_info ?: null,
                $screenshot_path
            ]);
            
            $issue_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Issue reported successfully. Report ID: #' . $issue_id,
                'issue_id' => $issue_id
            ]);
            break;

        // Get user's issues
        case 'get_my_issues':
            $status_filter = $_GET['status'] ?? '';
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            $where_clause = "WHERE ir.user_id = ?";
            $params = [$user_id];
            
            if ($status_filter && in_array($status_filter, ['pending', 'in_progress', 'fixed'])) {
                $where_clause .= " AND ir.status = ?";
                $params[] = $status_filter;
            }
            
            $stmt = $pdo->prepare("
                SELECT ir.*, l.name as lab_name, 
                       assigned.name as assigned_to_name,
                       COUNT(*) OVER() as total_count
                FROM issue_reports ir
                LEFT JOIN labs l ON ir.lab_id = l.id
                LEFT JOIN users assigned ON ir.assigned_to = assigned.id
                $where_clause
                ORDER BY ir.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $issues = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'issues' => $issues,
                'total' => $issues[0]['total_count'] ?? 0
            ]);
            break;

        // Get all issues (Admin/Staff only)
        case 'get_all_issues':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $status_filter = $_GET['status'] ?? '';
            $lab_filter = $_GET['lab_id'] ?? '';
            $assignee_filter = $_GET['assignee'] ?? '';
            $date_filter = $_GET['date_range'] ?? '';
            $limit = intval($_GET['limit'] ?? 100);
            $offset = intval($_GET['offset'] ?? 0);
            
            $where_conditions = [];
            $params = [];
            
            if ($status_filter && in_array($status_filter, ['pending', 'in_progress', 'fixed'])) {
                $where_conditions[] = "ir.status = ?";
                $params[] = $status_filter;
            }
            
            if ($lab_filter) {
                $where_conditions[] = "ir.lab_id = ?";
                $params[] = intval($lab_filter);
            }
            
            if ($assignee_filter === 'unassigned') {
                $where_conditions[] = "ir.assigned_to IS NULL";
            } elseif ($assignee_filter && is_numeric($assignee_filter)) {
                $where_conditions[] = "ir.assigned_to = ?";
                $params[] = intval($assignee_filter);
            }
            
            if ($date_filter) {
                switch ($date_filter) {
                    case 'today':
                        $where_conditions[] = "DATE(ir.created_at) = CURDATE()";
                        break;
                    case 'week':
                        $where_conditions[] = "ir.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                        break;
                    case 'month':
                        $where_conditions[] = "ir.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                        break;
                }
            }
            
            $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
            
            $stmt = $pdo->prepare("
                SELECT ir.*, l.name as lab_name, 
                       u.name as reporter_name, u.role as reporter_role,
                       assigned.name as assigned_to_name,
                       COUNT(*) OVER() as total_count
                FROM issue_reports ir
                LEFT JOIN labs l ON ir.lab_id = l.id
                JOIN users u ON ir.user_id = u.id
                LEFT JOIN users assigned ON ir.assigned_to = assigned.id
                $where_clause
                ORDER BY 
                    CASE ir.priority 
                        WHEN 'critical' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                    END,
                    ir.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $issues = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'issues' => $issues,
                'total' => $issues[0]['total_count'] ?? 0
            ]);
            break;

        // Get issue statistics
        case 'get_statistics':
            $user_condition = '';
            $params = [];
            
            if ($user_role === 'student' || $user_role === 'lecturer') {
                $user_condition = 'WHERE user_id = ?';
                $params[] = $user_id;
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'fixed' THEN 1 ELSE 0 END) as fixed,
                    SUM(CASE WHEN assigned_to IS NULL AND status != 'fixed' THEN 1 ELSE 0 END) as unassigned,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN status = 'fixed' AND DATE(resolved_at) = CURDATE() THEN 1 ELSE 0 END) as fixed_today
                FROM issue_reports
                $user_condition
            ");
            $stmt->execute($params);
            $stats = $stmt->fetch();
            
            // Get assigned issues for staff
            if ($user_role === 'staff') {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as my_assigned
                    FROM issue_reports
                    WHERE assigned_to = ? AND status != 'fixed'
                ");
                $stmt->execute([$user_id]);
                $assigned = $stmt->fetch();
                $stats['my_assigned'] = $assigned['my_assigned'];
            }
            
            echo json_encode(['success' => true, 'statistics' => $stats]);
            break;

        // Get issue details
        case 'get_issue_details':
            $issue_id = intval($_GET['issue_id'] ?? 0);
            
            if (!$issue_id) {
                throw new Exception('Issue ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT ir.*, l.name as lab_name, l.capacity,
                       u.name as reporter_name, u.email as reporter_email, u.role as reporter_role,
                       assigned.name as assigned_to_name, assigned.email as assigned_to_email,
                       resolver.name as resolved_by_name
                FROM issue_reports ir
                LEFT JOIN labs l ON ir.lab_id = l.id
                JOIN users u ON ir.user_id = u.id
                LEFT JOIN users assigned ON ir.assigned_to = assigned.id
                LEFT JOIN users resolver ON ir.resolved_by = resolver.id
                WHERE ir.id = ?
            ");
            $stmt->execute([$issue_id]);
            $issue = $stmt->fetch();
            
            if (!$issue) {
                throw new Exception('Issue not found');
            }
            
            // Check permission to view
            if (!in_array($user_role, ['admin', 'staff']) && $issue['user_id'] != $user_id) {
                throw new Exception('Unauthorized access');
            }
            
            // Get follow-up comments
            $stmt = $pdo->prepare("
                SELECT ic.*, u.name as commenter_name, u.role as commenter_role
                FROM issue_comments ic
                JOIN users u ON ic.user_id = u.id
                WHERE ic.issue_id = ?
                ORDER BY ic.created_at ASC
            ");
            $stmt->execute([$issue_id]);
            $comments = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'issue' => $issue,
                'comments' => $comments
            ]);
            break;

        // Assign issue (Admin/Staff only)
        case 'assign_issue':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $issue_id = intval($_POST['issue_id'] ?? 0);
            $assigned_to = intval($_POST['assigned_to'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            
            if (!$issue_id) {
                throw new Exception('Issue ID is required');
            }
            
            // If assigning to someone, update status to in_progress
            $new_status = $assigned_to ? 'in_progress' : 'pending';
            
            $stmt = $pdo->prepare("
                UPDATE issue_reports 
                SET assigned_to = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$assigned_to ?: null, $new_status, $issue_id]);
            
            // Add comment if notes provided
            if ($notes) {
                $stmt = $pdo->prepare("
                    INSERT INTO issue_comments (issue_id, user_id, comment, comment_type)
                    VALUES (?, ?, ?, 'assignment')
                ");
                $stmt->execute([$issue_id, $user_id, $notes]);
            }
            
            $message = $assigned_to ? 'Issue assigned successfully' : 'Issue unassigned';
            echo json_encode(['success' => true, 'message' => $message]);
            break;

        // Update issue status
        case 'update_status':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $issue_id = intval($_POST['issue_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $notes = trim($_POST['notes'] ?? '');
            
            if (!$issue_id || !in_array($status, ['pending', 'in_progress', 'fixed'])) {
                throw new Exception('Invalid data');
            }
            
            $resolved_at = $status === 'fixed' ? date('Y-m-d H:i:s') : null;
            $resolved_by = $status === 'fixed' ? $user_id : null;
            
            $stmt = $pdo->prepare("
                UPDATE issue_reports 
                SET status = ?, resolved_at = ?, resolved_by = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $resolved_at, $resolved_by, $issue_id]);
            
            // Add status update comment
            $comment_type = $status === 'fixed' ? 'resolution' : 'status_update';
            $comment_text = $notes ?: "Status updated to: " . ucfirst(str_replace('_', ' ', $status));
            
            $stmt = $pdo->prepare("
                INSERT INTO issue_comments (issue_id, user_id, comment, comment_type)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$issue_id, $user_id, $comment_text, $comment_type]);
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            break;

        // Add comment/follow-up
        case 'add_comment':
            $issue_id = intval($_POST['issue_id'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');
            $comment_type = $_POST['comment_type'] ?? 'followup';
            
            if (!$issue_id || empty($comment)) {
                throw new Exception('Issue ID and comment are required');
            }
            
            // Check if user can comment on this issue
            $stmt = $pdo->prepare("
                SELECT user_id, assigned_to FROM issue_reports WHERE id = ?
            ");
            $stmt->execute([$issue_id]);
            $issue = $stmt->fetch();
            
            if (!$issue) {
                throw new Exception('Issue not found');
            }
            
            $can_comment = (
                in_array($user_role, ['admin', 'staff']) ||
                $issue['user_id'] == $user_id ||
                $issue['assigned_to'] == $user_id
            );
            
            if (!$can_comment) {
                throw new Exception('You cannot comment on this issue');
            }
            
            // Handle file upload for comment
            $attachment_path = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/issues/';
                $file = $_FILES['attachment'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $filename = 'comment_' . time() . '_' . $user_id . '.' . $ext;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $attachment_path = 'uploads/issues/' . $filename;
                    }
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO issue_comments (issue_id, user_id, comment, comment_type, attachment)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$issue_id, $user_id, $comment, $comment_type, $attachment_path]);
            
            // Update issue timestamp
            $stmt = $pdo->prepare("UPDATE issue_reports SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$issue_id]);
            
            echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
            break;

        // Get labs for dropdown
        case 'get_labs':
            $stmt = $pdo->prepare("
                SELECT id, name, status 
                FROM labs 
                ORDER BY name ASC
            ");
            $stmt->execute();
            $labs = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'labs' => $labs]);
            break;

        // Get staff members for assignment
        case 'get_staff':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $stmt = $pdo->prepare("
                SELECT id, name, email 
                FROM users 
                WHERE role IN ('admin', 'staff') 
                ORDER BY name ASC
            ");
            $stmt->execute();
            $staff = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'staff' => $staff]);
            break;

        // Bulk assign issues (Staff can take multiple issues)
        case 'bulk_assign':
            if ($user_role !== 'staff' && $user_role !== 'admin') {
                throw new Exception('Unauthorized access');
            }
            
            $issue_ids = $_POST['issue_ids'] ?? [];
            $assigned_to = intval($_POST['assigned_to'] ?? $user_id);
            
            if (empty($issue_ids)) {
                throw new Exception('No issues selected');
            }
            
            $placeholders = str_repeat('?,', count($issue_ids) - 1) . '?';
            $stmt = $pdo->prepare("
                UPDATE issue_reports 
                SET assigned_to = ?, status = 'in_progress', updated_at = NOW()
                WHERE id IN ($placeholders) AND status = 'pending'
            ");
            $params = array_merge([$assigned_to], $issue_ids);
            $stmt->execute($params);
            
            $affected = $stmt->rowCount();
            echo json_encode([
                'success' => true,
                'message' => "$affected issues assigned successfully"
            ]);
            break;

        // Export issues (Admin/Staff only)
        case 'export_issues':
            if (!in_array($user_role, ['admin', 'staff'])) {
                throw new Exception('Unauthorized access');
            }
            
            $format = $_GET['format'] ?? 'csv';
            
            $stmt = $pdo->prepare("
                SELECT ir.id, ir.created_at, ir.status, ir.priority, ir.category,
                       l.name as lab_name, ir.computer_number, ir.description,
                       u.name as reporter_name, u.role as reporter_role,
                       assigned.name as assigned_to_name,
                       ir.resolved_at
                FROM issue_reports ir
                LEFT JOIN labs l ON ir.lab_id = l.id
                JOIN users u ON ir.user_id = u.id
                LEFT JOIN users assigned ON ir.assigned_to = assigned.id
                ORDER BY ir.created_at DESC
            ");
            $stmt->execute();
            $issues = $stmt->fetchAll();
            
            if ($format === 'csv') {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="issues_export_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, [
                    'ID', 'Created', 'Status', 'Priority', 'Category',
                    'Lab', 'Computer', 'Description', 'Reporter', 'Role',
                    'Assigned To', 'Resolved At'
                ]);
                
                foreach ($issues as $issue) {
                    fputcsv($output, [
                        $issue['id'],
                        $issue['created_at'],
                        $issue['status'],
                        $issue['priority'],
                        $issue['category'],
                        $issue['lab_name'],
                        $issue['computer_number'],
                        $issue['description'],
                        $issue['reporter_name'],
                        $issue['reporter_role'],
                        $issue['assigned_to_name'],
                        $issue['resolved_at']
                    ]);
                }
                
                fclose($output);
                exit;
            }
            
            echo json_encode(['success' => true, 'issues' => $issues]);
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
    error_log("Issues API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred. Please try again later.'
    ]);
}