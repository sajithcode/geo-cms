<?php
require_once 'config.php';

header('Content-Type: application/json');

// Require user to be logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'update_profile':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            // Validate input
            if (empty($name)) {
                throw new Exception('Name is required');
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Valid email address is required');
            }
            
            // Check if email is already used by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetchColumn()) {
                throw new Exception('This email address is already in use by another account');
            }
            
            // Update user profile
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Update session variables
                $_SESSION['user_name'] = $name;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);
            } else {
                throw new Exception('No changes were made to your profile');
            }
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate input
            if (empty($current_password)) {
                throw new Exception('Current password is required');
            }
            
            if (empty($new_password)) {
                throw new Exception('New password is required');
            }
            
            if (strlen($new_password) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('New passwords do not match');
            }
            
            // Get current user details
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Check if new password is different from current
            if (password_verify($new_password, $user['password'])) {
                throw new Exception('New password must be different from your current password');
            }
            
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$hashed_password, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Log the password change
                error_log("Password changed for user ID: {$user_id} (" . $_SESSION['user_name'] . ")");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Password changed successfully. You will be logged out and need to log in with your new password.'
                ]);
            } else {
                throw new Exception('Failed to update password');
            }
            break;
            
        case 'get_profile':
            // Get full user details
            $stmt = $pdo->prepare("
                SELECT id, name, email, user_id, role, created_at, updated_at 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            $user_details = $stmt->fetch();
            
            if (!$user_details) {
                throw new Exception('User not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $user_details
            ]);
            break;
            
        case 'get_user_stats':
            $role = $_SESSION['role'];
            $stats = [];
            
            switch ($role) {
                case 'admin':
                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE id != {$user_id}");
                    $stats['managed_users'] = $stmt->fetchColumn();
                    
                    $stmt = $pdo->query("SELECT COUNT(*) FROM labs");
                    $stats['total_labs'] = $stmt->fetchColumn();
                    
                    $stmt = $pdo->query("SELECT COUNT(*) FROM store_items");
                    $stats['total_items'] = $stmt->fetchColumn();
                    
                    $stmt = $pdo->query("SELECT COUNT(*) FROM issue_reports WHERE status IN ('pending', 'in_progress')");
                    $stats['pending_issues'] = $stmt->fetchColumn();
                    break;
                    
                case 'lecturer':
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_reservations WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $stats['my_reservations'] = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_timetables WHERE lecturer_id = ?");
                    $stmt->execute([$user_id]);
                    $stats['scheduled_sessions'] = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM issue_reports WHERE reported_by = ?");
                    $stmt->execute([$user_id]);
                    $stats['reported_issues'] = $stmt->fetchColumn();
                    break;
                    
                case 'staff':
                    $stmt = $pdo->query("SELECT COUNT(*) FROM lab_reservations WHERE status = 'pending'");
                    $stats['pending_requests'] = $stmt->fetchColumn();
                    
                    $stmt = $pdo->query("SELECT COUNT(*) FROM issue_reports WHERE status IN ('pending', 'in_progress')");
                    $stats['open_issues'] = $stmt->fetchColumn();
                    
                    $stmt = $pdo->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'pending'");
                    $stats['inventory_requests'] = $stmt->fetchColumn();
                    break;
                    
                case 'student':
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_reservations WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $stats['my_reservations'] = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $stats['borrowed_items'] = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM issue_reports WHERE reported_by = ?");
                    $stmt->execute([$user_id]);
                    $stats['reported_issues'] = $stmt->fetchColumn();
                    break;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'get_recent_activity':
            $recent_activity = [];
            
            // Get recent reservations
            $stmt = $pdo->prepare("
                SELECT 'reservation' as type, lr.*, l.name as lab_name 
                FROM lab_reservations lr 
                LEFT JOIN labs l ON lr.lab_id = l.id 
                WHERE lr.user_id = ? 
                ORDER BY lr.request_date DESC 
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $recent_activity = array_merge($recent_activity, $stmt->fetchAll());
            
            // Get recent issues
            $stmt = $pdo->prepare("
                SELECT 'issue' as type, ir.*, l.name as lab_name 
                FROM issue_reports ir 
                LEFT JOIN labs l ON ir.lab_id = l.id 
                WHERE ir.reported_by = ? 
                ORDER BY ir.reported_date DESC 
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $recent_activity = array_merge($recent_activity, $stmt->fetchAll());
            
            // Sort by date
            usort($recent_activity, function($a, $b) {
                $date_a = $a['type'] === 'reservation' ? $a['request_date'] : $a['reported_date'];
                $date_b = $b['type'] === 'reservation' ? $b['request_date'] : $b['reported_date'];
                return strtotime($date_b) - strtotime($date_a);
            });
            
            $recent_activity = array_slice($recent_activity, 0, 10);
            
            echo json_encode([
                'success' => true,
                'data' => $recent_activity
            ]);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (PDOException $e) {
    error_log("Profile process error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>