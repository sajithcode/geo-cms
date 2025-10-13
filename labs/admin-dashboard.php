<?php
require_once '../php/config.php';

// Require user to be logged in as admin or staff
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Only allow admin and staff
if (!in_array($user_role, ['admin', 'staff'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Labs Management - Admin';

// Get comprehensive lab management data
try {
    // Get all labs
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
    
    // Get all pending reservations
    $stmt = $pdo->query("
        SELECT lr.*, l.name as lab_name, l.code as lab_code,
               u.name as requester_name, u.user_id as requester_id
        FROM lab_reservations lr
        JOIN labs l ON lr.lab_id = l.id
        JOIN users u ON lr.user_id = u.id
        WHERE lr.status = 'pending'
        ORDER BY lr.request_date ASC
    ");
    $pending_reservations = $stmt->fetchAll();
    
    // Get recent reservations
    $stmt = $pdo->query("
        SELECT lr.*, l.name as lab_name, l.code as lab_code,
               u.name as requester_name, u.user_id as requester_id,
               approved_by.name as approved_by_name
        FROM lab_reservations lr
        JOIN labs l ON lr.lab_id = l.id
        JOIN users u ON lr.user_id = u.id
        LEFT JOIN users approved_by ON lr.approved_by = approved_by.id
        ORDER BY lr.request_date DESC
        LIMIT 20
    ");
    $recent_reservations = $stmt->fetchAll();
    
    // Get reservation statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_reservations,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reservations
        FROM lab_reservations
    ");
    $reservation_stats = $stmt->fetch();
    
    // Get lab issues
    $stmt = $pdo->query("
        SELECT li.*, l.name as lab_name, l.code as lab_code,
               reported_by.name as reported_by_name,
               assigned_to.name as assigned_to_name,
               resolved_by.name as resolved_by_name
        FROM lab_issues li
        JOIN labs l ON li.lab_id = l.id
        JOIN users reported_by ON li.reported_by = reported_by.id
        LEFT JOIN users assigned_to ON li.assigned_to = assigned_to.id
        LEFT JOIN users resolved_by ON li.resolved_by = resolved_by.id
        WHERE li.status != 'closed'
        ORDER BY 
            CASE li.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            li.reported_date ASC
    ");
    $active_issues = $stmt->fetchAll();
    
    // Get staff members for assignment
    $stmt = $pdo->query("
        SELECT id, name, user_id 
        FROM users 
        WHERE role IN ('admin', 'staff') 
        ORDER BY name ASC
    ");
    $staff_members = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin labs error: " . $e->getMessage());
    $labs = [];
    $pending_reservations = [];
    $recent_reservations = [];
    $reservation_stats = ['total_reservations' => 0, 'pending_reservations' => 0, 'approved_reservations' => 0, 'rejected_reservations' => 0];
    $active_issues = [];
    $staff_members = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/inventory.css">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <style>
        .labs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .lab-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .lab-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .lab-card.in-use {
            border-left-color: #e74c3c;
        }
        
        .lab-card.maintenance {
            border-left-color: #f39c12;
        }
        
        .lab-card.offline {
            border-left-color: #95a5a6;
        }
        
        .lab-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .lab-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        
        .lab-code {
            font-size: 0.9em;
            color: #7f8c8d;
            margin: 0;
        }
        
        .lab-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .lab-status.available {
            background: #d4edda;
            color: #155724;
        }
        
        .lab-status.in-use {
            background: #f8d7da;
            color: #721c24;
        }
        
        .lab-status.maintenance {
            background: #fff3cd;
            color: #856404;
        }
        
        .lab-status.offline {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .lab-info {
            margin-bottom: 15px;
        }
        
        .lab-info p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .lab-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .reservation-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            margin-bottom: 10px;
        }
        
        .reservation-item.pending {
            border-left-color: #f39c12;
        }
        
        .reservation-item.approved {
            border-left-color: #27ae60;
        }
        
        .reservation-item.rejected {
            border-left-color: #e74c3c;
        }
        
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .reservation-title {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .reservation-details {
            font-size: 0.9em;
            color: #6c757d;
        }
        
        .issue-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            margin-bottom: 10px;
        }
        
        .issue-item.high {
            border-left-color: #e74c3c;
        }
        
        .issue-item.medium {
            border-left-color: #f39c12;
        }
        
        .issue-item.low {
            border-left-color: #27ae60;
        }
        
        .issue-item.critical {
            border-left-color: #8e44ad;
        }
        
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-align: center;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .action-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .action-text h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .action-text p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <?php include '../includes/header.php'; ?>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Notification Container -->
                <div id="notification-container" class="notification-container"></div>
                
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-title">
                        <h1>🔬 Labs Management - Admin</h1>
                        <p>Comprehensive lab administration and oversight</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-outline-secondary" onclick="exportReport()">
                            📊 Export Report
                        </button>
                        <button class="btn btn-outline-primary" onclick="showModal('upload-timetable-modal')">
                            📅 Upload Timetable
                        </button>
                        <button class="btn btn-primary" onclick="showModal('add-lab-modal')">
                            ➕ Add Lab
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🏢</div>
                        <div class="stat-info">
                            <h3><?php echo count($labs); ?></h3>
                            <p>Total Labs</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['pending_reservations']; ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['total_reservations']; ?></h3>
                            <p>Total Reservations</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🚨</div>
                        <div class="stat-info">
                            <h3><?php echo count($active_issues); ?></h3>
                            <p>Active Issues</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="quick-actions-grid">
                        <div class="action-card" onclick="approveAllPending()">
                            <div class="action-icon">✅</div>
                            <div class="action-text">
                                <h4>Approve Requests</h4>
                                <p>Review and approve pending lab reservations</p>
                            </div>
                        </div>
                        <div class="action-card" onclick="manageIssues()">
                            <div class="action-icon">🔧</div>
                            <div class="action-text">
                                <h4>Manage Issues</h4>
                                <p>Assign and track maintenance issues</p>
                            </div>
                        </div>
                        <div class="action-card" onclick="showModal('upload-timetable-modal')">
                            <div class="action-icon">📅</div>
                            <div class="action-text">
                                <h4>Upload Timetables</h4>
                                <p>Import lab schedules from Excel/CSV</p>
                            </div>
                        </div>
                        <div class="action-card" onclick="generateReport()">
                            <div class="action-icon">📊</div>
                            <div class="action-text">
                                <h4>Generate Reports</h4>
                                <p>Create utilization and maintenance reports</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Labs Overview -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Labs Overview</h2>
                        <div class="section-actions">
                            <button class="btn btn-outline-primary" onclick="refreshLabStatus()">
                                🔄 Refresh Status
                            </button>
                        </div>
                    </div>

                    <div class="labs-grid">
                        <?php if (empty($labs)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">🔬</div>
                                <h3>No Labs Configured</h3>
                                <p>Start by adding your first laboratory.</p>
                                <button class="btn btn-primary" onclick="showModal('add-lab-modal')">Add First Lab</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($labs as $lab): ?>
                                <?php 
                                $display_status = $lab['current_bookings'] > 0 ? 'in-use' : $lab['status'];
                                $status_text = $lab['current_bookings'] > 0 ? 'In Use' : ucfirst($lab['status']);
                                ?>
                                <div class="lab-card <?php echo $display_status; ?>">
                                    <div class="lab-header">
                                        <div>
                                            <h3 class="lab-title"><?php echo htmlspecialchars($lab['name']); ?></h3>
                                            <p class="lab-code"><?php echo htmlspecialchars($lab['code']); ?></p>
                                        </div>
                                        <span class="lab-status <?php echo $display_status; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="lab-info">
                                        <p><strong>Capacity:</strong> <?php echo $lab['capacity']; ?> students</p>
                                        <p><strong>Location:</strong> <?php echo htmlspecialchars($lab['location'] ?? 'Not specified'); ?></p>
                                        <p><strong>Pending Requests:</strong> <?php echo $lab['pending_requests']; ?></p>
                                        <?php if ($lab['current_bookings'] > 0): ?>
                                            <p><strong>Current Bookings:</strong> <?php echo $lab['current_bookings']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="lab-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editLab(<?php echo $lab['id']; ?>)">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="manageTimetable(<?php echo $lab['id']; ?>)">
                                            📅 Timetable
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="changeLabStatus(<?php echo $lab['id']; ?>)">
                                            🔄 Status
                                        </button>
                                        <?php if ($lab['pending_requests'] > 0): ?>
                                            <button class="btn btn-sm btn-primary" onclick="viewPendingRequests(<?php echo $lab['id']; ?>)">
                                                📋 Requests (<?php echo $lab['pending_requests']; ?>)
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Reservations -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Pending Reservations</h2>
                        <div class="section-actions">
                            <button class="btn btn-outline-success" onclick="approveAllVisible()">
                                ✅ Approve All Visible
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover" id="pending-reservations-table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all-pending" onchange="toggleAllPending()">
                                    </th>
                                    <th>Requester</th>
                                    <th>Lab</th>
                                    <th>Date & Time</th>
                                    <th>Purpose</th>
                                    <th>Attendees</th>
                                    <th>Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending_reservations)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">✅</div>
                                                <h3>No Pending Reservations</h3>
                                                <p>All lab reservation requests have been processed.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pending_reservations as $reservation): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="pending-checkbox" value="<?php echo $reservation['id']; ?>">
                                            </td>
                                            <td>
                                                <div class="requester-info">
                                                    <strong><?php echo htmlspecialchars($reservation['requester_name']); ?></strong>
                                                    <small class="text-muted"><?php echo htmlspecialchars($reservation['requester_id']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="lab-info">
                                                    <strong><?php echo htmlspecialchars($reservation['lab_name']); ?></strong>
                                                    <small class="text-muted"><?php echo htmlspecialchars($reservation['lab_code']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="datetime-info">
                                                    <strong><?php echo formatDate($reservation['reservation_date'], 'DD/MM/YYYY'); ?></strong><br>
                                                    <small><?php echo formatTime($reservation['start_time']); ?> - <?php echo formatTime($reservation['end_time']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="purpose-text" title="<?php echo htmlspecialchars($reservation['purpose']); ?>">
                                                    <?php echo strlen($reservation['purpose']) > 50 ? substr(htmlspecialchars($reservation['purpose']), 0, 50) . '...' : htmlspecialchars($reservation['purpose']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo $reservation['expected_attendees']; ?></td>
                                            <td><?php echo formatDate($reservation['request_date'], 'DD/MM/YYYY HH:mm'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-success" onclick="approveReservation(<?php echo $reservation['id']; ?>)">
                                                        Approve
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="rejectReservation(<?php echo $reservation['id']; ?>)">
                                                        Reject
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewReservationDetails(<?php echo $reservation['id']; ?>)">
                                                        Details
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Active Issues -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Active Maintenance Issues</h2>
                        <div class="section-actions">
                            <button class="btn btn-outline-primary" onclick="viewAllIssues()">
                                View All Issues
                            </button>
                        </div>
                    </div>

                    <div class="issues-list">
                        <?php if (empty($active_issues)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">✅</div>
                                <h3>No Active Issues</h3>
                                <p>All labs are functioning properly.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($active_issues, 0, 5) as $issue): ?>
                                <div class="issue-item <?php echo $issue['priority']; ?>">
                                    <div class="issue-header">
                                        <div class="issue-title">
                                            <?php echo htmlspecialchars($issue['lab_name']); ?> - <?php echo htmlspecialchars($issue['title']); ?>
                                        </div>
                                        <div class="issue-badges">
                                            <span class="badge badge-<?php echo getIssuePriorityBadgeClass($issue['priority']); ?>">
                                                <?php echo ucfirst($issue['priority']); ?>
                                            </span>
                                            <span class="badge badge-secondary">
                                                <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="issue-details">
                                        <p><?php echo htmlspecialchars(strlen($issue['description']) > 150 ? substr($issue['description'], 0, 150) . '...' : $issue['description']); ?></p>
                                        <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $issue['issue_type'])); ?></p>
                                        <p><strong>Reported by:</strong> <?php echo htmlspecialchars($issue['reported_by_name']); ?></p>
                                        <?php if ($issue['assigned_to_name']): ?>
                                            <p><strong>Assigned to:</strong> <?php echo htmlspecialchars($issue['assigned_to_name']); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Reported:</strong> <?php echo formatDate($issue['reported_date'], 'DD/MM/YYYY HH:mm'); ?></p>
                                    </div>
                                    <div class="issue-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewIssueDetails(<?php echo $issue['id']; ?>)">
                                            View Details
                                        </button>
                                        <?php if (!$issue['assigned_to']): ?>
                                            <button class="btn btn-sm btn-outline-warning" onclick="assignIssue(<?php echo $issue['id']; ?>)">
                                                Assign
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-success" onclick="updateIssueStatus(<?php echo $issue['id']; ?>)">
                                            Update Status
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Lab Modal -->
    <div id="add-lab-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 id="lab-modal-title">Add New Lab</h3>
                <button onclick="hideModal('add-lab-modal')">&times;</button>
            </div>
            <form id="lab-form">
                <div class="modal-body">
                    <input type="hidden" id="lab-id" name="lab_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-8">
                            <div class="form-group">
                                <label for="lab-name" class="form-label">Lab Name *</label>
                                <input type="text" id="lab-name" name="name" class="form-control" required
                                       placeholder="e.g., Computer Lab 01">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="lab-code" class="form-label">Lab Code *</label>
                                <input type="text" id="lab-code" name="code" class="form-control" required
                                       placeholder="e.g., LAB01">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="lab-description" class="form-label">Description</label>
                        <textarea id="lab-description" name="description" class="form-control" rows="3"
                                  placeholder="Brief description of the lab and its purpose"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="lab-capacity" class="form-label">Capacity *</label>
                                <input type="number" id="lab-capacity" name="capacity" class="form-control" 
                                       min="1" max="100" required value="30">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="lab-status" class="form-label">Status *</label>
                                <select id="lab-status" name="status" class="form-control form-select" required>
                                    <option value="available">Available</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="lab-location" class="form-label">Location</label>
                        <input type="text" id="lab-location" name="location" class="form-control"
                               placeholder="e.g., Building A, Ground Floor">
                    </div>

                    <div class="form-group">
                        <label for="lab-equipment" class="form-label">Equipment List</label>
                        <textarea id="lab-equipment" name="equipment_list" class="form-control" rows="3"
                                  placeholder="List the major equipment available in this lab"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="lab-safety" class="form-label">Safety Guidelines</label>
                        <textarea id="lab-safety" name="safety_guidelines" class="form-control" rows="3"
                                  placeholder="Important safety guidelines and rules for this lab"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('add-lab-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Lab</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Timetable Modal -->
    <div id="upload-timetable-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Lab Timetable</h3>
                <button onclick="hideModal('upload-timetable-modal')">&times;</button>
            </div>
            <form id="timetable-upload-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="timetable-lab" class="form-label">Select Lab *</label>
                        <select id="timetable-lab" name="lab_id" class="form-control form-select" required>
                            <option value="">Choose a lab</option>
                            <?php foreach ($labs as $lab): ?>
                                <option value="<?php echo $lab['id']; ?>">
                                    <?php echo htmlspecialchars($lab['name']); ?> (<?php echo htmlspecialchars($lab['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="timetable-file" class="form-label">Upload File *</label>
                        <input type="file" id="timetable-file" name="timetable_file" class="form-control" 
                               accept=".xlsx,.xls,.csv" required>
                        <small class="form-text">Supported formats: Excel (.xlsx, .xls) or CSV (.csv)</small>
                    </div>

                    <div class="alert alert-info">
                        <strong>File Format Requirements:</strong>
                        <ul class="mb-0">
                            <li>Columns: Day, Start Time, End Time, Title, Description, Instructor</li>
                            <li>Day format: Monday, Tuesday, etc.</li>
                            <li>Time format: HH:MM (24-hour format)</li>
                            <li>First row should contain headers</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('upload-timetable-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload & Process</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Other modals for reservations, issues, etc. -->
    
    <script src="../js/script.js"></script>
    <script src="../js/labs.js"></script>
    <script src="../js/admin-labs.js"></script>
</body>
</html>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'cancelled': return 'secondary';
        case 'completed': return 'success';
        default: return 'secondary';
    }
}

function getIssuePriorityBadgeClass($priority) {
    switch ($priority) {
        case 'low': return 'success';
        case 'medium': return 'warning';
        case 'high': return 'danger';
        case 'critical': return 'dark';
        default: return 'secondary';
    }
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}
?>