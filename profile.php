<?php
require_once 'php/config.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];
$user_identity = $_SESSION['user_identity'];

// Get full user details from database
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_details = $stmt->fetch();
    
    if (!$user_details) {
        throw new Exception('User not found');
    }
    
    // Get additional statistics based on user role
    $user_stats = [];
    switch ($user_role) {
        case 'admin':
            $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE id != {$user_id}");
            $user_stats['managed_users'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total_labs FROM labs");
            $user_stats['total_labs'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total_items FROM store_items");
            $user_stats['total_items'] = $stmt->fetchColumn();
            break;
            
        case 'lecturer':
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_reservations FROM lab_reservations WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_stats['my_reservations'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_timetable FROM lab_timetables WHERE lecturer_id = ?");
            $stmt->execute([$user_id]);
            $user_stats['scheduled_sessions'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_issues FROM issue_reports WHERE reported_by = ?");
            $stmt->execute([$user_id]);
            $user_stats['reported_issues'] = $stmt->fetchColumn();
            break;
            
        case 'staff':
            $stmt = $pdo->query("SELECT COUNT(*) as pending_requests FROM lab_reservations WHERE status = 'pending'");
            $user_stats['pending_requests'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as open_issues FROM issue_reports WHERE status IN ('pending', 'in_progress')");
            $user_stats['open_issues'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as inventory_requests FROM borrow_requests WHERE status = 'pending'");
            $user_stats['inventory_requests'] = $stmt->fetchColumn();
            break;
            
        case 'student':
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_reservations FROM lab_reservations WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_stats['my_reservations'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_borrows FROM borrow_requests WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_stats['borrowed_items'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_issues FROM issue_reports WHERE reported_by = ?");
            $stmt->execute([$user_id]);
            $user_stats['reported_issues'] = $stmt->fetchColumn();
            break;
    }
    
    // Get recent activity
    $recent_activity = [];
    
    // Get recent reservations
    $stmt = $pdo->prepare("
        SELECT 'reservation' as type, lr.*, l.name as lab_name 
        FROM lab_reservations lr 
        LEFT JOIN labs l ON lr.lab_id = l.id 
        WHERE lr.user_id = ? 
        ORDER BY lr.request_date DESC 
        LIMIT 5
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
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_activity = array_merge($recent_activity, $stmt->fetchAll());
    
    // Sort by date
    usort($recent_activity, function($a, $b) {
        $date_a = $a['type'] === 'reservation' ? $a['request_date'] : $a['reported_date'];
        $date_b = $b['type'] === 'reservation' ? $b['request_date'] : $b['reported_date'];
        return strtotime($date_b) - strtotime($date_a);
    });
    
    $recent_activity = array_slice($recent_activity, 0, 5);
    
} catch (PDOException $e) {
    error_log("Profile error: " . $e->getMessage());
    $user_stats = [];
    $recent_activity = [];
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    redirectTo('dashboard.php');
}

$page_title = 'Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateX(-100px) translateY(-100px); }
            100% { transform: translateX(100px) translateY(100px); }
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .profile-name {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        
        .profile-role {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }
        
        .profile-id {
            font-size: 16px;
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .profile-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .profile-field {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .profile-field:last-child {
            border-bottom: none;
        }
        
        .field-label {
            font-weight: 500;
            color: #6b7280;
        }
        
        .field-value {
            font-weight: 600;
            color: #374151;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s;
        }
        
        .activity-item:hover {
            background-color: #f8fafc;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .activity-icon.reservation {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .activity-icon.issue {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            color: #374151;
            margin-bottom: 2px;
        }
        
        .activity-meta {
            font-size: 12px;
            color: #6b7280;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #d1d5db;
            color: #374151;
        }
        
        .btn-outline:hover {
            background: #f9fafb;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .role-admin {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .role-lecturer {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .role-staff {
            background: #d1fae5;
            color: #065f46;
        }
        
        .role-student {
            background: #fef3c7;
            color: #92400e;
        }
        
        .member-since {
            font-size: 14px;
            color: #6b7280;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <?php include 'includes/header.php'; ?>

            <!-- Profile Content -->
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php
                        $avatar_emoji = [
                            'admin' => '👑',
                            'lecturer' => '👨‍🏫',
                            'staff' => '👨‍💼',
                            'student' => '🎓'
                        ];
                        echo $avatar_emoji[$user_role] ?? '👤';
                        ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user_details['name']); ?></div>
                    <div class="profile-role">
                        <span class="role-badge role-<?php echo $user_role; ?>">
                            <?php echo ucfirst($user_role); ?>
                        </span>
                    </div>
                    <div class="profile-id">ID: <?php echo htmlspecialchars($user_details['user_id']); ?></div>
                    <div class="member-since">
                        Member since <?php echo formatDate($user_details['created_at'], 'MMMM YYYY'); ?>
                    </div>
                </div>

                <!-- Profile Grid -->
                <div class="profile-grid">
                    <!-- User Details -->
                    <div class="profile-section">
                        <h2 class="section-title">
                            <span>👤</span>
                            User Information
                        </h2>
                        
                        <div class="profile-field">
                            <span class="field-label">Full Name</span>
                            <span class="field-value"><?php echo htmlspecialchars($user_details['name']); ?></span>
                        </div>
                        
                        <div class="profile-field">
                            <span class="field-label">Email Address</span>
                            <span class="field-value"><?php echo htmlspecialchars($user_details['email']); ?></span>
                        </div>
                        
                        <div class="profile-field">
                            <span class="field-label">User ID</span>
                            <span class="field-value"><?php echo htmlspecialchars($user_details['user_id']); ?></span>
                        </div>
                        
                        <div class="profile-field">
                            <span class="field-label">Role</span>
                            <span class="field-value">
                                <span class="role-badge role-<?php echo $user_role; ?>">
                                    <?php echo ucfirst($user_role); ?>
                                </span>
                            </span>
                        </div>
                        
                        <div class="profile-field">
                            <span class="field-label">Account Created</span>
                            <span class="field-value"><?php echo formatDate($user_details['created_at'], 'DD/MM/YYYY'); ?></span>
                        </div>
                        
                        <div class="profile-field">
                            <span class="field-label">Last Updated</span>
                            <span class="field-value"><?php echo formatDate($user_details['updated_at'], 'DD/MM/YYYY HH:mm'); ?></span>
                        </div>

                        <div class="action-buttons">
                            <button class="btn btn-primary" onclick="showModal('edit-profile-modal')">
                                <span>✏️</span>
                                Edit Profile
                            </button>
                            <button class="btn btn-secondary" onclick="showModal('change-password-modal')">
                                <span>🔒</span>
                                Change Password
                            </button>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="profile-section">
                        <h2 class="section-title">
                            <span>📊</span>
                            My Statistics
                        </h2>
                        
                        <div class="stats-grid">
                            <?php if ($user_role === 'admin'): ?>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['managed_users'] ?? 0; ?></div>
                                    <div class="stat-label">Users Managed</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['total_labs'] ?? 0; ?></div>
                                    <div class="stat-label">Total Labs</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['total_items'] ?? 0; ?></div>
                                    <div class="stat-label">Store Items</div>
                                </div>
                            <?php elseif ($user_role === 'lecturer'): ?>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['my_reservations'] ?? 0; ?></div>
                                    <div class="stat-label">Lab Reservations</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['scheduled_sessions'] ?? 0; ?></div>
                                    <div class="stat-label">Scheduled Sessions</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['reported_issues'] ?? 0; ?></div>
                                    <div class="stat-label">Issues Reported</div>
                                </div>
                            <?php elseif ($user_role === 'staff'): ?>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['pending_requests'] ?? 0; ?></div>
                                    <div class="stat-label">Pending Requests</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['open_issues'] ?? 0; ?></div>
                                    <div class="stat-label">Open Issues</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['inventory_requests'] ?? 0; ?></div>
                                    <div class="stat-label">Store Requests</div>
                                </div>
                            <?php elseif ($user_role === 'student'): ?>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['my_reservations'] ?? 0; ?></div>
                                    <div class="stat-label">Lab Reservations</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['borrowed_items'] ?? 0; ?></div>
                                    <div class="stat-label">Borrowed Items</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user_stats['reported_issues'] ?? 0; ?></div>
                                    <div class="stat-label">Issues Reported</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="profile-section">
                    <h2 class="section-title">
                        <span>📝</span>
                        Recent Activity
                    </h2>
                    
                    <?php if (empty($recent_activity)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <h3>No Recent Activity</h3>
                            <p>Your recent activities will appear here once you start using the system.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo $activity['type']; ?>">
                                    <?php echo $activity['type'] === 'reservation' ? '📅' : '🚨'; ?>
                                </div>
                                <div class="activity-content">
                                    <?php if ($activity['type'] === 'reservation'): ?>
                                        <div class="activity-title">
                                            Lab Reservation - <?php echo htmlspecialchars($activity['lab_name'] ?? 'Unknown Lab'); ?>
                                        </div>
                                        <div class="activity-meta">
                                            <?php echo formatDate($activity['reservation_date'], 'DD/MM/YYYY'); ?> 
                                            • Status: <?php echo ucfirst($activity['status']); ?>
                                            • Requested: <?php echo formatDate($activity['request_date'], 'DD/MM/YYYY HH:mm'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="activity-title">
                                            Issue Report - <?php echo htmlspecialchars($activity['lab_name'] ?? 'General'); ?>
                                        </div>
                                        <div class="activity-meta">
                                            <?php echo substr(htmlspecialchars($activity['description']), 0, 60); ?>...
                                            • Status: <?php echo ucfirst($activity['status']); ?>
                                            • Reported: <?php echo formatDate($activity['reported_date'], 'DD/MM/YYYY HH:mm'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Profile Modal -->
    <div id="edit-profile-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button onclick="hideModal('edit-profile-modal')">&times;</button>
            </div>
            <form id="edit-profile-form">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="edit-name" class="form-label">Full Name *</label>
                        <input type="text" id="edit-name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($user_details['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit-email" class="form-label">Email Address *</label>
                        <input type="email" id="edit-email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user_details['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">User ID</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($user_details['user_id']); ?>" disabled>
                        <small class="form-text">User ID cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" 
                               value="<?php echo ucfirst($user_role); ?>" disabled>
                        <small class="form-text">Role is managed by administrators</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('edit-profile-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="change-password-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button onclick="hideModal('change-password-modal')">&times;</button>
            </div>
            <form id="change-password-form">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current-password" class="form-label">Current Password *</label>
                        <input type="password" id="current-password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="new-password" class="form-label">New Password *</label>
                        <input type="password" id="new-password" name="new_password" class="form-control" 
                               minlength="8" required>
                        <small class="form-text">Password must be at least 8 characters long</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password" class="form-label">Confirm New Password *</label>
                        <input type="password" id="confirm-password" name="confirm_password" class="form-control" 
                               minlength="8" required>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Note:</strong> You will be logged out after changing your password and will need to log in again with your new password.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('change-password-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Profile form submissions
        document.getElementById('edit-profile-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
            
            try {
                const response = await fetch('php/profile_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    hideModal('edit-profile-modal');
                    
                    // Update the page content
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(result.message || 'Failed to update profile', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while updating profile', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Save Changes';
            }
        });

        document.getElementById('change-password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            // Validate password match
            if (newPassword !== confirmPassword) {
                showNotification('New passwords do not match', 'error');
                return;
            }
            
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Changing...';
            
            try {
                const response = await fetch('php/profile_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    hideModal('change-password-modal');
                    
                    // Redirect to login after password change
                    setTimeout(() => {
                        window.location.href = 'php/logout.php?password_changed=1';
                    }, 2000);
                } else {
                    showNotification(result.message || 'Failed to change password', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while changing password', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Change Password';
            }
        });

        // Show notification function (if not already defined)
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
                padding: 15px;
                border-radius: 6px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                background: ${type === 'success' ? '#d1fae5' : type === 'error' ? '#fee2e2' : '#dbeafe'};
                color: ${type === 'success' ? '#065f46' : type === 'error' ? '#991b1b' : '#1e40af'};
                border: 1px solid ${type === 'success' ? '#a7f3d0' : type === 'error' ? '#fecaca' : '#bfdbfe'};
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    </script>
</body>
</html>