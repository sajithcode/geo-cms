<?php
require_once '../php/config.php';

// Require user to be logged in as student
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Only allow students
if ($user_role !== 'student') {
    header('Location: index.php');
    exit;
}

$page_title = 'Labs - Student';

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
    
    // Get user's reservations
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
    
} catch (PDOException $e) {
    error_log("Student labs error: " . $e->getMessage());
    $labs = [];
    $my_reservations = [];
    $reservation_stats = ['total_reservations' => 0, 'pending_reservations' => 0, 'approved_reservations' => 0, 'rejected_reservations' => 0];
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
                        <h1>🔬 Labs Management</h1>
                        <p>Request lab access and track your reservations</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="showModal('request-lab-modal')">
                            ➕ Request Lab Use
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['total_reservations']; ?></h3>
                            <p>Total Requests</p>
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
                        <div class="stat-icon">❌</div>
                        <div class="stat-info">
                            <h3><?php echo $reservation_stats['rejected_reservations']; ?></h3>
                            <p>Rejected</p>
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
                                    </div>
                                    
                                    <div class="lab-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewTimetable(<?php echo $lab['id']; ?>)">
                                            📅 View Timetable
                                        </button>
                                        <?php if ($lab['status'] === 'available'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="requestLabUse(<?php echo $lab['id']; ?>)">
                                                📝 Request Use
                                            </button>
                                        <?php endif; ?>
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
                                <p>You haven't made any lab reservations. Click "Request Lab Use" to get started.</p>
                                <button class="btn btn-primary" onclick="showModal('request-lab-modal')">Make Your First Request</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($my_reservations as $reservation): ?>
                                <div class="reservation-item <?php echo $reservation['status']; ?>" data-status="<?php echo $reservation['status']; ?>">
                                    <div class="reservation-header">
                                        <div class="reservation-title">
                                            <?php echo htmlspecialchars($reservation['lab_name']); ?>
                                        </div>
                                        <span class="badge badge-<?php echo getStatusBadgeClass($reservation['status']); ?>">
                                            <?php echo ucfirst($reservation['status']); ?>
                                        </span>
                                    </div>
                                    <div class="reservation-details">
                                        <p><strong>Date:</strong> <?php echo formatDate($reservation['reservation_date'], 'DD/MM/YYYY'); ?></p>
                                        <p><strong>Time:</strong> <?php echo formatTime($reservation['start_time']); ?> - <?php echo formatTime($reservation['end_time']); ?></p>
                                        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($reservation['purpose']); ?></p>
                                        <p><strong>Requested:</strong> <?php echo formatDate($reservation['request_date'], 'DD/MM/YYYY HH:mm'); ?></p>
                                        <?php if ($reservation['status'] === 'approved' && $reservation['approved_by_name']): ?>
                                            <p><strong>Approved by:</strong> <?php echo htmlspecialchars($reservation['approved_by_name']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($reservation['notes']): ?>
                                            <p><strong>Notes:</strong> <?php echo htmlspecialchars($reservation['notes']); ?></p>
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
            </div>
        </main>
    </div>

    <!-- Request Lab Modal -->
    <div id="request-lab-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Request Lab Use</h3>
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
                                            data-description="<?php echo htmlspecialchars($lab['description']); ?>">
                                        <?php echo htmlspecialchars($lab['name']); ?> (Capacity: <?php echo $lab['capacity']; ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lab-details" id="lab-details" style="display: none;">
                        <div class="alert alert-info">
                            <p id="lab-description"></p>
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
                                <label for="expected_attendees" class="form-label">Expected Attendees *</label>
                                <input type="number" id="expected_attendees" name="expected_attendees" 
                                       class="form-control" min="1" max="50" required value="1">
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
                        <label for="purpose" class="form-label">Purpose *</label>
                        <textarea id="purpose" name="purpose" class="form-control" rows="3" required
                                  placeholder="Please describe the purpose of your lab reservation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('request-lab-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Timetable Modal -->
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

function formatTime($time) {
    return date('H:i', strtotime($time));
}
?>