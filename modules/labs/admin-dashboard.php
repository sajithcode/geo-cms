<?php
// This file is included from index.php
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

$page_title = 'Labs Management - Admin';

// Get comprehensive statistics
try {
    // Reservation statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_reservations,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reservations,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reservations
        FROM lab_reservations
    ");
    $reservation_stats = $stmt->fetch();
    
    // Labs statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_labs,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_labs,
            SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use_labs,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_labs
        FROM labs
    ");
    $labs_stats = $stmt->fetch();
    
    // Issue reports statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_issues,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_issues,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as fixed_issues
        FROM issue_reports
    ");
    $issue_stats = $stmt->fetch();
    
    // Get all labs
    $stmt = $pdo->query("SELECT * FROM labs ORDER BY name ASC");
    $all_labs = $stmt->fetchAll();
    
    // Get all reservations
    $stmt = $pdo->prepare("
        SELECT lr.*, l.name as lab_name, u.name as requester_name, u.user_id as requester_id,
               approver.name as approved_by_name
        FROM lab_reservations lr
        JOIN labs l ON lr.lab_id = l.id
        JOIN users u ON lr.user_id = u.id
        LEFT JOIN users approver ON lr.approved_by = approver.id
        ORDER BY 
            CASE WHEN lr.status = 'pending' THEN 1 ELSE 2 END,
            lr.reservation_date DESC,
            lr.start_time DESC
    ");
    $stmt->execute();
    $all_reservations = $stmt->fetchAll();
    
    // Get all issue reports
    $stmt = $pdo->prepare("
        SELECT ir.*, l.name as lab_name, u.name as reporter_name,
               assigned.name as assigned_to_name
        FROM issue_reports ir
        LEFT JOIN labs l ON ir.lab_id = l.id
        JOIN users u ON ir.reported_by = u.id
        LEFT JOIN users assigned ON ir.assigned_to = assigned.id
        ORDER BY 
            CASE WHEN ir.status = 'pending' THEN 1 
                 WHEN ir.status = 'in_progress' THEN 2 ELSE 3 END,
            ir.reported_date DESC
    ");
    $stmt->execute();
    $all_issues = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin labs error: " . $e->getMessage());
    $reservation_stats = ['total_reservations' => 0, 'pending_reservations' => 0, 'approved_reservations' => 0, 'rejected_reservations' => 0, 'completed_reservations' => 0];
    $labs_stats = ['total_labs' => 0, 'available_labs' => 0, 'in_use_labs' => 0, 'maintenance_labs' => 0];
    $issue_stats = ['total_issues' => 0, 'pending_issues' => 0, 'in_progress_issues' => 0, 'fixed_issues' => 0];
    $all_labs = [];
    $all_reservations = [];
    $all_issues = [];
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
    <link rel="stylesheet" href="../css/labs.css">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
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
                        <p>Complete laboratory control and analytics</p>
                    </div>
                    <div class="page-actions">
                        <a href="?page=timetable" class="btn btn-outline-success">
                            📅 View Timetable
                        </a>
                        <button class="btn btn-outline-secondary" onclick="exportReport()">
                            📊 Export Report
                        </button>
                        <button class="btn btn-outline-primary" onclick="showModal('upload-timetable-modal')">
                            📅 Upload Timetable
                        </button>
                        <button class="btn btn-primary" onclick="showModal('lab-modal')">
                            ➕ Add Lab
                        </button>
                    </div>
                </div>

                <!-- Analytics Panel -->
                <div class="analytics-panel">
                    <h2>📊 Analytics Dashboard</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">🔬</div>
                            <div class="stat-info">
                                <h3><?php echo $labs_stats['total_labs']; ?></h3>
                                <p>Total Labs</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-info">
                                <h3><?php echo $labs_stats['available_labs']; ?></h3>
                                <p>Available</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📋</div>
                            <div class="stat-info">
                                <h3><?php echo $reservation_stats['pending_reservations']; ?></h3>
                                <p>Pending Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🚨</div>
                            <div class="stat-info">
                                <h3><?php echo $issue_stats['pending_issues']; ?></h3>
                                <p>Open Issues</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🔄</div>
                            <div class="stat-info">
                                <h3><?php echo $labs_stats['in_use_labs']; ?></h3>
                                <p>In Use</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🔧</div>
                            <div class="stat-info">
                                <h3><?php echo $labs_stats['maintenance_labs']; ?></h3>
                                <p>Maintenance</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Labs Overview Cards -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Laboratory Overview</h2>
                        <div class="section-actions">
                            <button class="btn btn-outline-primary" onclick="refreshData()">
                                🔄 Refresh
                            </button>
                        </div>
                    </div>

                    <div class="labs-grid">
                        <?php if (empty($all_labs)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">🔬</div>
                                <h3>No Labs Found</h3>
                                <p>Start by adding your first laboratory.</p>
                                <button class="btn btn-primary" onclick="showModal('lab-modal')">Add First Lab</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($all_labs as $lab): ?>
                                <div class="lab-card <?php echo $lab['status']; ?>" data-lab-id="<?php echo $lab['id']; ?>">
                                    <div class="lab-card-header">
                                        <h3><?php echo htmlspecialchars($lab['name']); ?></h3>
                                        <span class="lab-status <?php echo $lab['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $lab['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="lab-card-body">
                                        <p class="lab-description">
                                            <?php echo htmlspecialchars($lab['description'] ?? 'No description'); ?>
                                        </p>
                                        <div class="lab-details">
                                            <div class="detail-item">
                                                <span class="detail-label">Capacity:</span>
                                                <span class="detail-value"><?php echo $lab['capacity']; ?> seats</span>
                                            </div>
                                            <?php
                                            // Count today's reservations for this lab
                                            $stmt = $pdo->prepare("
                                                SELECT COUNT(*) as today_reservations 
                                                FROM lab_reservations 
                                                WHERE lab_id = ? AND reservation_date = CURDATE() AND status = 'approved'
                                            ");
                                            $stmt->execute([$lab['id']]);
                                            $today_count = $stmt->fetchColumn();
                                            ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Today's Reservations:</span>
                                                <span class="detail-value"><?php echo $today_count; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="lab-card-footer">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewTimetable(<?php echo $lab['id']; ?>)">
                                            📅 View Timetable
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editLab(<?php echo $lab['id']; ?>)">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="changeLabStatus(<?php echo $lab['id']; ?>)">
                                            🔄 Change Status
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Reservations -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Reservation Requests</h2>
                        <div class="section-actions">
                            <div class="filter-group">
                                <select id="status-filter" class="form-control" onchange="filterReservations()">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <select id="lab-filter" class="form-control" onchange="filterReservations()">
                                    <option value="">All Labs</option>
                                    <?php foreach ($all_labs as $lab): ?>
                                        <option value="<?php echo $lab['id']; ?>"><?php echo htmlspecialchars($lab['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover" id="reservations-table">
                            <thead>
                                <tr>
                                    <th>Requester</th>
                                    <th>Lab</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_reservations)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="empty-state">
                                                <p>No reservations found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_reservations as $reservation): ?>
                                        <tr data-status="<?php echo $reservation['status']; ?>" data-lab-id="<?php echo $reservation['lab_id']; ?>">
                                            <td>
                                                <div class="requester-info">
                                                    <strong><?php echo htmlspecialchars($reservation['requester_name']); ?></strong>
                                                    <small class="text-muted"><?php echo htmlspecialchars($reservation['requester_id']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($reservation['lab_name']); ?></td>
                                            <td><?php echo formatDate($reservation['reservation_date'], 'DD/MM/YYYY'); ?></td>
                                            <td><?php echo date('H:i', strtotime($reservation['start_time'])); ?> - <?php echo date('H:i', strtotime($reservation['end_time'])); ?></td>
                                            <td>
                                                <div class="purpose-text" title="<?php echo htmlspecialchars($reservation['purpose']); ?>">
                                                    <?php echo strlen($reservation['purpose']) > 50 ? substr(htmlspecialchars($reservation['purpose']), 0, 50) . '...' : htmlspecialchars($reservation['purpose']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo getReservationBadgeClass($reservation['status']); ?>">
                                                    <?php echo ucfirst($reservation['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewReservationDetails(<?php echo $reservation['id']; ?>)">
                                                        View
                                                    </button>
                                                    <?php if ($reservation['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="approveReservation(<?php echo $reservation['id']; ?>)">
                                                            Approve
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="rejectReservation(<?php echo $reservation['id']; ?>)">
                                                            Reject
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Issue Reports -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Issue Reports</h2>
                        <div class="section-actions">
                            <div class="filter-group">
                                <select id="issue-status-filter" class="form-control" onchange="filterIssues()">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="fixed">Fixed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover" id="issues-table">
                            <thead>
                                <tr>
                                    <th>Reporter</th>
                                    <th>Lab</th>
                                    <th>Computer</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_issues)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="empty-state">
                                                <p>No issue reports found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_issues as $issue): ?>
                                        <tr data-status="<?php echo $issue['status']; ?>">
                                            <td><?php echo htmlspecialchars($issue['reporter_name']); ?></td>
                                            <td><?php echo htmlspecialchars($issue['lab_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($issue['computer_serial_no'] ?? 'N/A'); ?></td>
                                            <td>
                                                <div class="issue-description" title="<?php echo htmlspecialchars($issue['description']); ?>">
                                                    <?php echo strlen($issue['description']) > 60 ? substr(htmlspecialchars($issue['description']), 0, 60) . '...' : htmlspecialchars($issue['description']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo getIssueBadgeClass($issue['status']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($issue['assigned_to_name'] ?? 'Unassigned'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewIssueDetails(<?php echo $issue['id']; ?>)">
                                                        View
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="assignIssue(<?php echo $issue['id']; ?>)">
                                                        Assign
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" onclick="updateIssueStatus(<?php echo $issue['id']; ?>)">
                                                        Update
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
            </div>
        </main>
    </div>

    <!-- Add/Edit Lab Modal -->
    <div id="lab-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="lab-modal-title">Add New Lab</h3>
                <button onclick="hideModal('lab-modal')">&times;</button>
            </div>
            <form id="lab-form">
                <div class="modal-body">
                    <input type="hidden" id="lab-id" name="lab_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="lab-name" class="form-label">Lab Name *</label>
                        <input type="text" id="lab-name" name="name" class="form-control" required
                               placeholder="e.g., Lab 01">
                    </div>

                    <div class="form-group">
                        <label for="lab-description" class="form-label">Description</label>
                        <textarea id="lab-description" name="description" class="form-control" rows="3"
                                  placeholder="Enter lab description"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="lab-capacity" class="form-label">Capacity *</label>
                                <input type="number" id="lab-capacity" name="capacity" class="form-control" 
                                       min="1" required placeholder="30">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="lab-status" class="form-label">Status</label>
                                <select id="lab-status" name="status" class="form-control form-select">
                                    <option value="available">Available</option>
                                    <option value="in_use">In Use</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('lab-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Lab</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Timetable Modal -->
    <div id="upload-timetable-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Upload Lab Timetable</h3>
                <button onclick="hideModal('upload-timetable-modal')">&times;</button>
            </div>
            <form id="timetable-upload-form" action="php/labs_api.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="upload_timetable">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="timetable-lab" class="form-label">Select Lab *</label>
                        <select id="timetable-lab" name="lab_id" class="form-control form-select" required>
                            <option value="">Choose a lab</option>
                            <?php foreach ($all_labs as $lab): ?>
                                <option value="<?php echo $lab['id']; ?>"><?php echo htmlspecialchars($lab['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="timetable-file" class="form-label">Upload CSV/Excel File</label>
                        <input type="file" id="timetable-file" name="timetable_file" class="form-control" 
                               accept=".csv,.xlsx,.xls">
                        <small class="form-text">Upload a CSV or Excel file with columns: Day, Start Time, End Time, Subject, Lecturer, Batch</small>
                    </div>

                    <div class="alert alert-info">
                        <strong>Note:</strong> The file should have the following format:
                        <ul class="mb-0">
                            <li>Day: Monday, Tuesday, Wednesday, Thursday, Friday</li>
                            <li>Start Time: HH:MM format (e.g., 08:00)</li>
                            <li>End Time: HH:MM format (e.g., 10:00)</li>
                            <li>Subject: Course name</li>
                            <li>Lecturer: Lecturer name or ID</li>
                            <li>Batch: Student batch/group</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('upload-timetable-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Timetable</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Timetable View Modal -->
    <div id="timetable-modal" class="modal" style="display: none;">
        <div class="modal-content modal-xl">
            <div class="modal-header">
                <h3 id="timetable-modal-title">Lab Timetable</h3>
                <button onclick="hideModal('timetable-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="timetable-content">
                    <!-- Timetable will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('timetable-modal')">Close</button>
                <button class="btn btn-primary" onclick="editTimetable()">Edit Timetable</button>
            </div>
        </div>
    </div>

    <!-- Reservation Details Modal -->
    <div id="reservation-details-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reservation Details</h3>
                <button onclick="hideModal('reservation-details-modal')">&times;</button>
            </div>
            <div class="modal-body" id="reservation-details-content">
                <!-- Content will be loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('reservation-details-modal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Issue Details Modal -->
    <div id="issue-details-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Issue Details</h3>
                <button onclick="hideModal('issue-details-modal')">&times;</button>
            </div>
            <div class="modal-body" id="issue-details-content">
                <!-- Content will be loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('issue-details-modal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Approval/Rejection Modal -->
    <div id="approval-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="approval-title">Approve Reservation</h3>
                <button onclick="hideModal('approval-modal')">&times;</button>
            </div>
            <form id="approval-form">
                <div class="modal-body">
                    <input type="hidden" id="approval-reservation-id" name="reservation_id">
                    <input type="hidden" id="approval-action" name="action">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="approval-notes" class="form-label">Notes (Optional)</label>
                        <textarea id="approval-notes" name="notes" class="form-control" rows="4"
                                  placeholder="Add any notes or conditions for this decision..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <strong>Note:</strong> This action will notify the requester about your decision.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('approval-modal')">Cancel</button>
                    <button type="submit" class="btn btn-success" id="approval-submit-btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lab Status Change Modal -->
    <div id="lab-status-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Lab Status</h3>
                <button onclick="hideModal('lab-status-modal')">&times;</button>
            </div>
            <form id="lab-status-form">
                <div class="modal-body">
                    <input type="hidden" id="lab-status-lab-id" name="lab_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="lab-new-status" class="form-label">New Status *</label>
                        <select id="lab-new-status" name="status" class="form-control form-select" required>
                            <option value="">Select status</option>
                            <option value="available">Available</option>
                            <option value="in_use">In Use</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>

                    <div class="alert alert-info">
                        <strong>Note:</strong> Changing the lab status will affect reservation availability.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('lab-status-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal (Generic) -->
    <div id="confirm-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="confirm-title">Confirm Action</h3>
                <button onclick="hideModal('confirm-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirm-message">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideModal('confirm-modal')">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-yes-btn">Confirm</button>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script src="../js/labs.js"></script>
    <script src="../js/admin-labs.js"></script>
    
    <script>
    // Handle timetable upload form
    document.getElementById('timetable-upload-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        // Show loading state
        submitButton.disabled = true;
        submitButton.textContent = 'Uploading...';
        
        fetch('php/labs_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('✅ ' + data.message + '\n\nProcessed: ' + (data.processed || 0) + ' entries' + 
                      (data.errors > 0 ? '\nErrors: ' + data.errors : ''));
                
                // Close modal and reset form
                hideModal('upload-timetable-modal');
                document.getElementById('timetable-upload-form').reset();
                
                // Optionally refresh the page to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('❌ Error: ' + (data.message || 'Upload failed'));
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert('❌ Upload failed. Please check your file format and try again.');
        })
        .finally(() => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        });
    });
    </script>
</body>
</html>

<?php
function getReservationBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'completed': return 'secondary';
        default: return 'secondary';
    }
}

function getIssueBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'danger';
        case 'in_progress': return 'warning';
        case 'resolved': return 'success';
        default: return 'secondary';
    }
}
?>
