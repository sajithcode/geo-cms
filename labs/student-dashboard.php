<?php
// This file is included from index.php
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

$page_title = 'Labs Management - Student';

// Get student's reservations and labs data
try {
    $user_id = $_SESSION['user_id'];
    
    // Get student's reservations
    $stmt = $pdo->prepare("
        SELECT lr.*, l.name as lab_name, l.capacity, l.status as lab_status,
               approver.name as approved_by_name
        FROM lab_reservations lr
        JOIN labs l ON lr.lab_id = l.id
        LEFT JOIN users approver ON lr.approved_by = approver.id
        WHERE lr.user_id = ?
        ORDER BY lr.reservation_date DESC, lr.start_time DESC
    ");
    $stmt->execute([$user_id]);
    $my_reservations = $stmt->fetchAll();
    
    // Get all labs
    $stmt = $pdo->query("SELECT * FROM labs ORDER BY name ASC");
    $all_labs = $stmt->fetchAll();
    
    // Get reservation statistics for student
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
    
    // Get upcoming approved reservations
    $stmt = $pdo->prepare("
        SELECT lr.*, l.name as lab_name 
        FROM lab_reservations lr
        JOIN labs l ON lr.lab_id = l.id
        WHERE lr.user_id = ? 
        AND lr.status = 'approved' 
        AND lr.reservation_date >= CURDATE()
        ORDER BY lr.reservation_date ASC, lr.start_time ASC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $upcoming_reservations = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Student labs error: " . $e->getMessage());
    $my_reservations = [];
    $all_labs = [];
    $reservation_stats = ['total_reservations' => 0, 'pending_reservations' => 0, 'approved_reservations' => 0, 'rejected_reservations' => 0];
    $upcoming_reservations = [];
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
                        <h1>🔬 Labs Management - Student</h1>
                        <p>View lab availability and manage your reservations</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="showModal('lab-request-modal')">
                            📝 Request Lab Use
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
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['total_reservations']; ?></h3>
                            <p>Total Requests</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔬</div>
                        <div class="stat-info">
                            <h3><?php echo count($all_labs); ?></h3>
                            <p>Available Labs</p>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Reservations -->
                <?php if (!empty($upcoming_reservations)): ?>
                    <div class="content-section">
                        <div class="section-header">
                            <h2>⏰ Upcoming Reservations</h2>
                        </div>
                        
                        <div class="upcoming-reservations-list">
                            <?php foreach ($upcoming_reservations as $reservation): ?>
                                <div class="upcoming-reservation-card">
                                    <div class="reservation-icon">
                                        <span>📅</span>
                                    </div>
                                    <div class="reservation-info">
                                        <h4><?php echo htmlspecialchars($reservation['lab_name']); ?></h4>
                                        <p class="reservation-date">
                                            <?php echo formatDate($reservation['reservation_date'], 'DD/MM/YYYY'); ?> 
                                            • <?php echo date('H:i', strtotime($reservation['start_time'])); ?> - 
                                            <?php echo date('H:i', strtotime($reservation['end_time'])); ?>
                                        </p>
                                        <p class="text-muted"><?php echo htmlspecialchars($reservation['purpose']); ?></p>
                                    </div>
                                    <div class="reservation-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewReservationDetails(<?php echo $reservation['id']; ?>)">
                                            View Details
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Labs Overview Cards -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Available Laboratories</h2>
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
                                <h3>No Labs Available</h3>
                                <p>There are currently no laboratories available.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($all_labs as $lab): ?>
                                <div class="lab-card <?php echo $lab['status']; ?>" data-lab-id="<?php echo $lab['id']; ?>">
                                    <div class="lab-card-header">
                                        <h3><?php echo htmlspecialchars($lab['name']); ?></h3>
                                        <span class="lab-status <?php echo $lab['status']; ?>">
                                            <?php 
                                            $statusText = [
                                                'available' => 'Available',
                                                'in_use' => 'In Use',
                                                'maintenance' => 'Maintenance'
                                            ];
                                            echo $statusText[$lab['status']] ?? ucfirst($lab['status']);
                                            ?>
                                        </span>
                                    </div>
                                    <div class="lab-card-body">
                                        <p class="lab-description">
                                            <?php echo htmlspecialchars($lab['description'] ?? 'No description available'); ?>
                                        </p>
                                        <div class="lab-details">
                                            <div class="detail-item">
                                                <span class="detail-label">Capacity:</span>
                                                <span class="detail-value"><?php echo $lab['capacity']; ?> seats</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="lab-card-footer">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewTimetable(<?php echo $lab['id']; ?>)">
                                            📅 View Timetable
                                        </button>
                                        <?php if ($lab['status'] === 'available'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="requestLab(<?php echo $lab['id']; ?>)">
                                                📝 Request
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- My Reservations -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>My Reservation Requests</h2>
                        <div class="section-actions">
                            <div class="filter-group">
                                <select id="status-filter" class="form-control" onchange="filterMyReservations()">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover" id="my-reservations-table">
                            <thead>
                                <tr>
                                    <th>Lab</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Requested On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($my_reservations)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">📋</div>
                                                <h3>No Reservations Yet</h3>
                                                <p>You haven't made any lab reservation requests yet.</p>
                                                <button class="btn btn-primary" onclick="showModal('lab-request-modal')">
                                                    Make Your First Request
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($my_reservations as $reservation): ?>
                                        <tr data-status="<?php echo $reservation['status']; ?>">
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
                                            <td><?php echo formatDate($reservation['request_date'], 'DD/MM/YYYY'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewReservationDetails(<?php echo $reservation['id']; ?>)">
                                                        View
                                                    </button>
                                                    <?php if ($reservation['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelReservation(<?php echo $reservation['id']; ?>)">
                                                            Cancel
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
            </div>
        </main>
    </div>

    <!-- Lab Request Modal -->
    <div id="lab-request-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Request Lab Use</h3>
                <button onclick="hideModal('lab-request-modal')">&times;</button>
            </div>
            <form id="lab-request-form">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="lab_id" class="form-label">Select Lab *</label>
                        <select id="lab_id" name="lab_id" class="form-control form-select" required>
                            <option value="">Choose a lab</option>
                            <?php foreach ($all_labs as $lab): ?>
                                <?php if ($lab['status'] === 'available'): ?>
                                    <option value="<?php echo $lab['id']; ?>">
                                        <?php echo htmlspecialchars($lab['name']); ?> (Capacity: <?php echo $lab['capacity']; ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reservation_date" class="form-label">Date *</label>
                        <input type="date" id="reservation_date" name="reservation_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" id="start_time" name="start_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="end_time" class="form-label">End Time *</label>
                                <input type="time" id="end_time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="purpose" class="form-label">Purpose *</label>
                        <textarea id="purpose" name="purpose" class="form-control" rows="3" required
                                  placeholder="Describe the purpose of your lab reservation..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <strong>Note:</strong> Your request will be reviewed by lab staff. You will be notified once your request is approved or rejected.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('lab-request-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
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
    <script>
        // Student-specific functionality
        function requestLab(labId) {
            // Pre-fill the lab selection
            document.getElementById('lab_id').value = labId;
            showModal('lab-request-modal');
        }

        function filterMyReservations() {
            const statusFilter = document.getElementById('status-filter');
            const rows = document.querySelectorAll('#my-reservations-table tbody tr');

            rows.forEach(row => {
                if (!row.dataset.status) return;
                
                let showRow = true;

                if (statusFilter && statusFilter.value !== '') {
                    showRow = row.dataset.status === statusFilter.value;
                }

                row.style.display = showRow ? '' : 'none';
            });
        }

        function refreshData() {
            location.reload();
        }
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
?>
