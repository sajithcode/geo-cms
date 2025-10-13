<?php
require_once '../php/config.php';

// Require user to be logged in as lecturer
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Only allow lecturers
if ($user_role !== 'lecturer') {
    header('Location: index.php');
    exit;
}

$page_title = 'Labs - Lecturer';

// Get all labs with current status
try {
    $stmt = $pdo->query("
        SELECT l.*, 
               COUNT(CASE WHEN lr.status = 'approved' AND lr.reservation_date = CURDATE() 
                          AND lr.start_time <= CURTIME() AND lr.end_time >= CURTIME() THEN 1 END) as current_bookings
        FROM labs l
        LEFT JOIN lab_reservations lr ON l.id = lr.lab_id
        GROUP BY l.id
        ORDER BY l.code ASC
    ");
    $labs = $stmt->fetchAll();
    
    // Get lecturer's reservations
    $stmt = $pdo->prepare("
        SELECT lr.*, l.name as lab_name, l.code as lab_code,
               approved_by.name as approved_by_name
        FROM lab_reservations lr
        JOIN labs l ON lr.lab_id = l.id
        LEFT JOIN users approved_by ON lr.approved_by = approved_by.id
        WHERE lr.user_id = ?
        ORDER BY lr.request_date DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $my_reservations = $stmt->fetchAll();
    
    // Get reservation statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_reservations,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_reservations
        FROM lab_reservations 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $reservation_stats = $stmt->fetch();
    
    // Get lab issues that the lecturer can report
    $stmt = $pdo->prepare("
        SELECT li.*, l.name as lab_name, l.code as lab_code,
               reported_by.name as reported_by_name
        FROM lab_issues li
        JOIN labs l ON li.lab_id = l.id
        JOIN users reported_by ON li.reported_by = reported_by.id
        WHERE li.reported_by = ? OR li.status != 'resolved'
        ORDER BY li.reported_date DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_issues = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Lecturer labs error: " . $e->getMessage());
    $labs = [];
    $my_reservations = [];
    $reservation_stats = ['total_reservations' => 0, 'pending_reservations' => 0, 'approved_reservations' => 0, 'rejected_reservations' => 0];
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
                        <h1>🔬 Labs Management - Lecturer</h1>
                        <p>Reserve labs for practicals and manage your sessions</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-outline-secondary" onclick="showModal('report-issue-modal')">
                            🚨 Report Issue
                        </button>
                        <button class="btn btn-primary" onclick="showModal('request-lab-modal')">
                            ➕ Request Lab Reservation
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['total_reservations']; ?></h3>
                            <p>Total Reservations</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['pending_reservations']; ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['approved_reservations']; ?></h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🚨</div>
                        <div class="stat-info">
                            <h3><?php echo count($recent_issues); ?></h3>
                            <p>Issues Reported</p>
                        </div>
                    </div>
                </div>

                <!-- Labs Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Available Labs</h2>
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
                                <h3>No Labs Available</h3>
                                <p>No laboratories are currently configured in the system.</p>
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
                                        <?php if ($lab['description']): ?>
                                            <p><strong>Description:</strong> <?php echo htmlspecialchars($lab['description']); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Capacity:</strong> <?php echo $lab['capacity']; ?> students</p>
                                        <?php if ($lab['location']): ?>
                                            <p><strong>Location:</strong> <?php echo htmlspecialchars($lab['location']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($lab['equipment_list']): ?>
                                            <p><strong>Equipment:</strong> <?php echo strlen($lab['equipment_list']) > 80 ? substr(htmlspecialchars($lab['equipment_list']), 0, 80) . '...' : htmlspecialchars($lab['equipment_list']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="lab-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewTimetable(<?php echo $lab['id']; ?>)">
                                            📅 View Timetable
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="viewEquipmentStatus(<?php echo $lab['id']; ?>)">
                                            🔧 Equipment Status
                                        </button>
                                        <?php if ($lab['status'] === 'available'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="requestLabReservation(<?php echo $lab['id']; ?>)">
                                                📝 Reserve Lab
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-warning" onclick="reportIssue(<?php echo $lab['id']; ?>)">
                                            🚨 Report Issue
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- My Reservations Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>My Lab Reservations</h2>
                        <div class="section-actions">
                            <div class="filter-group">
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="reservations-list">
                        <?php if (empty($my_reservations)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📋</div>
                                <h3>No Reservations Yet</h3>
                                <p>You haven't made any lab reservations. Click "Request Lab Reservation" to get started.</p>
                                <button class="btn btn-primary" onclick="showModal('request-lab-modal')">Make Your First Request</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($my_reservations as $reservation): ?>
                                <div class="reservation-item <?php echo $reservation['status']; ?>" data-status="<?php echo $reservation['status']; ?>">
                                    <div class="reservation-header">
                                        <div class="reservation-title">
                                            <?php echo htmlspecialchars($reservation['lab_name']); ?> (<?php echo htmlspecialchars($reservation['lab_code']); ?>)
                                        </div>
                                        <span class="badge badge-<?php echo getStatusBadgeClass($reservation['status']); ?>">
                                            <?php echo ucfirst($reservation['status']); ?>
                                        </span>
                                    </div>
                                    <div class="reservation-details">
                                        <p><strong>Date:</strong> <?php echo formatDate($reservation['reservation_date'], 'DD/MM/YYYY'); ?></p>
                                        <p><strong>Time:</strong> <?php echo formatTime($reservation['start_time']); ?> - <?php echo formatTime($reservation['end_time']); ?></p>
                                        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($reservation['purpose']); ?></p>
                                        <p><strong>Expected Students:</strong> <?php echo $reservation['expected_attendees']; ?></p>
                                        <?php if ($reservation['special_requirements']): ?>
                                            <p><strong>Special Requirements:</strong> <?php echo htmlspecialchars($reservation['special_requirements']); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Requested:</strong> <?php echo formatDate($reservation['request_date'], 'DD/MM/YYYY HH:mm'); ?></p>
                                        <?php if ($reservation['status'] === 'approved' && $reservation['approved_by_name']): ?>
                                            <p><strong>Approved by:</strong> <?php echo htmlspecialchars($reservation['approved_by_name']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($reservation['status'] === 'rejected' && $reservation['rejection_reason']): ?>
                                            <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($reservation['rejection_reason']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="reservation-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewReservationDetails(<?php echo $reservation['id']; ?>)">
                                            View Details
                                        </button>
                                        <?php if ($reservation['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">
                                                Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Issues Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Recent Issues</h2>
                        <div class="section-actions">
                            <button class="btn btn-outline-primary" onclick="viewAllIssues()">
                                View All Issues
                            </button>
                        </div>
                    </div>

                    <div class="issues-list">
                        <?php if (empty($recent_issues)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">✅</div>
                                <h3>No Recent Issues</h3>
                                <p>No issues have been reported recently.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_issues as $issue): ?>
                                <div class="issue-item <?php echo $issue['priority']; ?>">
                                    <div class="issue-header">
                                        <div class="issue-title">
                                            <?php echo htmlspecialchars($issue['lab_name']); ?> - <?php echo htmlspecialchars($issue['title']); ?>
                                        </div>
                                        <span class="badge badge-<?php echo getIssuePriorityBadgeClass($issue['priority']); ?>">
                                            <?php echo ucfirst($issue['priority']); ?>
                                        </span>
                                    </div>
                                    <div class="issue-details">
                                        <p><?php echo htmlspecialchars($issue['description']); ?></p>
                                        <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $issue['issue_type'])); ?></p>
                                        <p><strong>Status:</strong> <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?></p>
                                        <p><strong>Reported:</strong> <?php echo formatDate($issue['reported_date'], 'DD/MM/YYYY HH:mm'); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Request Lab Modal (same as student but with additional fields for lecturers) -->
    <div id="request-lab-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Request Lab Reservation</h3>
                <button onclick="hideModal('request-lab-modal')">&times;</button>
            </div>
            <form id="lab-request-form">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="lab_id" class="form-label">Select Lab *</label>
                        <select id="lab_id" name="lab_id" class="form-control form-select" required>
                            <option value="">Choose a lab</option>
                            <?php foreach ($labs as $lab): ?>
                                <?php if ($lab['status'] === 'available'): ?>
                                    <option value="<?php echo $lab['id']; ?>" 
                                            data-capacity="<?php echo $lab['capacity']; ?>"
                                            data-description="<?php echo htmlspecialchars($lab['description']); ?>"
                                            data-location="<?php echo htmlspecialchars($lab['location']); ?>">
                                        <?php echo htmlspecialchars($lab['name']); ?> (<?php echo htmlspecialchars($lab['code']); ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lab-details" id="lab-details" style="display: none;">
                        <div class="alert alert-info">
                            <p id="lab-description"></p>
                            <p><strong>Location:</strong> <span id="lab-location"></span></p>
                            <p><strong>Capacity:</strong> <span id="lab-capacity"></span> students</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="reservation_date" class="form-label">Date *</label>
                                <input type="date" id="reservation_date" name="reservation_date" 
                                       class="form-control" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="expected_attendees" class="form-label">Expected Students *</label>
                                <input type="number" id="expected_attendees" name="expected_attendees" 
                                       class="form-control" min="1" max="50" required value="20">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" id="start_time" name="start_time" 
                                       class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="end_time" class="form-label">End Time *</label>
                                <input type="time" id="end_time" name="end_time" 
                                       class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="purpose" class="form-label">Purpose/Subject *</label>
                        <textarea id="purpose" name="purpose" class="form-control" rows="3" required
                                  placeholder="Please describe the purpose (e.g., CS101 Practical Session, Physics Lab Experiment...)"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="special_requirements" class="form-label">Special Requirements</label>
                        <textarea id="special_requirements" name="special_requirements" class="form-control" rows="2"
                                  placeholder="Any special equipment, software, or setup requirements..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('request-lab-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Issue Modal -->
    <div id="report-issue-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Report Lab Issue</h3>
                <button onclick="hideModal('report-issue-modal')">&times;</button>
            </div>
            <form id="issue-report-form">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="issue_lab_id" class="form-label">Select Lab *</label>
                        <select id="issue_lab_id" name="lab_id" class="form-control form-select" required>
                            <option value="">Choose a lab</option>
                            <?php foreach ($labs as $lab): ?>
                                <option value="<?php echo $lab['id']; ?>">
                                    <?php echo htmlspecialchars($lab['name']); ?> (<?php echo htmlspecialchars($lab['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="issue_type" class="form-label">Issue Type *</label>
                                <select id="issue_type" name="issue_type" class="form-control form-select" required>
                                    <option value="">Select issue type</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="equipment_fault">Equipment Fault</option>
                                    <option value="safety_concern">Safety Concern</option>
                                    <option value="facility_issue">Facility Issue</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="priority" class="form-label">Priority *</label>
                                <select id="priority" name="priority" class="form-control form-select" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="issue_title" class="form-label">Issue Title *</label>
                        <input type="text" id="issue_title" name="title" class="form-control" required
                               placeholder="Brief title describing the issue">
                    </div>

                    <div class="form-group">
                        <label for="issue_description" class="form-label">Description *</label>
                        <textarea id="issue_description" name="description" class="form-control" rows="4" required
                                  placeholder="Detailed description of the issue, including steps to reproduce if applicable..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('report-issue-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Other modals (timetable, reservation details, etc.) - same as student dashboard -->
    <div id="timetable-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 id="timetable-title">Lab Timetable</h3>
                <button onclick="hideModal('timetable-modal')">&times;</button>
            </div>
            <div class="modal-body" id="timetable-content">
                <!-- Content will be loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('timetable-modal')">Close</button>
            </div>
        </div>
    </div>

    <div id="equipment-status-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 id="equipment-title">Equipment Status</h3>
                <button onclick="hideModal('equipment-status-modal')">&times;</button>
            </div>
            <div class="modal-body" id="equipment-content">
                <!-- Content will be loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('equipment-status-modal')">Close</button>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script src="../js/labs.js"></script>
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