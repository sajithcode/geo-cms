<?php
// This file is included from index.php
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

$page_title = 'Labs Management - Staff';

// Get reservation requests and statistics
try {
    // Get all reservations
    $stmt = $pdo->prepare("
        SELECT lr.*, l.name as lab_name, l.capacity, u.name as requester_name, u.user_id as requester_id, u.role as requester_role,
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
    
    // Get all labs
    $stmt = $pdo->query("SELECT * FROM labs ORDER BY name ASC");
    $all_labs = $stmt->fetchAll();
    
    // Get issue reports
    $stmt = $pdo->prepare("
        SELECT ir.*, l.name as lab_name, u.name as reporter_name
        FROM issue_reports ir
        LEFT JOIN labs l ON ir.lab_id = l.id
        JOIN users u ON ir.reported_by = u.id
        WHERE ir.status IN ('pending', 'in_progress')
        ORDER BY ir.reported_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_issues = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Staff labs error: " . $e->getMessage());
    $all_reservations = [];
    $reservation_stats = ['total_reservations' => 0, 'pending_reservations' => 0, 'approved_reservations' => 0, 'rejected_reservations' => 0];
    $all_labs = [];
    $recent_issues = [];
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
                        <h1>🔬 Labs Management - Staff</h1>
                        <p>Manage laboratory reservations and monitor lab status</p>
                    </div>
                    <div class="page-actions">
                        <a href="?page=timetable" class="btn btn-outline-success">
                            📅 View Timetable
                        </a>
                        <button class="btn btn-outline-primary" onclick="refreshData()">
                            🔄 Refresh
                        </button>
                        <button class="btn btn-primary" onclick="exportReservations()">
                            📊 Export Report
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['pending_reservations']; ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['approved_reservations']; ?></h3>
                            <p>Approved Today</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔬</div>
                        <div class="stat-info">
                            <h3><?php echo count($all_labs); ?></h3>
                            <p>Total Labs</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🚨</div>
                        <div class="stat-info">
                            <h3><?php echo count($recent_issues); ?></h3>
                            <p>Open Issues</p>
                        </div>
                    </div>
                </div>

                <!-- Labs Overview -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Laboratory Status</h2>
                    </div>
                    
                    <div class="labs-grid">
                        <?php foreach ($all_labs as $lab): ?>
                            <div class="lab-card-small <?php echo $lab['status']; ?>">
                                <div class="lab-card-header">
                                    <h4><?php echo htmlspecialchars($lab['name']); ?></h4>
                                    <span class="lab-status-badge <?php echo $lab['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $lab['status'])); ?>
                                    </span>
                                </div>
                                <div class="lab-card-body">
                                    <p class="text-muted"><?php echo htmlspecialchars($lab['description'] ?? 'No description'); ?></p>
                                    <div class="lab-info">
                                        <span>Capacity: <?php echo $lab['capacity']; ?></span>
                                    </div>
                                </div>
                                <div class="lab-card-footer">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewTimetable(<?php echo $lab['id']; ?>)">
                                        📅 View Timetable
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Reservation Requests Table -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Reservation Requests Management</h2>
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
                            <div class="search-group">
                                <input type="text" id="reservation-search" class="form-control" placeholder="Search by requester...">
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
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_reservations)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">📋</div>
                                                <h3>No Reservations Found</h3>
                                                <p>There are no lab reservations to display.</p>
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
                                                    <span class="badge badge-secondary"><?php echo ucfirst($reservation['requester_role']); ?></span>
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
                                            <td><?php echo formatDate($reservation['request_date'], 'DD/MM/YYYY HH:mm'); ?></td>
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

                <!-- Recent Issues -->
                <?php if (!empty($recent_issues)): ?>
                    <div class="content-section">
                        <div class="section-header">
                            <h2>Recent Issue Reports</h2>
                        </div>

                        <div class="issues-list">
                            <?php foreach ($recent_issues as $issue): ?>
                                <div class="issue-item">
                                    <div class="issue-icon">
                                        <span class="badge badge-<?php echo getIssueBadgeClass($issue['status']); ?>">
                                            <?php echo strtoupper(substr($issue['status'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <div class="issue-content">
                                        <p>
                                            <strong><?php echo htmlspecialchars($issue['reporter_name']); ?></strong>
                                            reported an issue in
                                            <strong><?php echo htmlspecialchars($issue['lab_name'] ?? 'General'); ?></strong>
                                            <?php if ($issue['computer_serial_no']): ?>
                                                - Computer <?php echo htmlspecialchars($issue['computer_serial_no']); ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-muted"><?php echo htmlspecialchars($issue['description']); ?></p>
                                        <small class="text-muted">
                                            <?php echo formatDate($issue['reported_date'], 'DD/MM/YYYY HH:mm'); ?>
                                        </small>
                                    </div>
                                    <div class="issue-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewIssueDetails(<?php echo $issue['id']; ?>)">
                                            View
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
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

    <!-- Approval Modal -->
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
    <script src="../js/staff-labs.js"></script>
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
        case 'fixed': return 'success';
        default: return 'secondary';
    }
}
?>
