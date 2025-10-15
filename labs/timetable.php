<?php
// This file is included from index.php
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

$page_title = 'Lab Timetable';

// Get filter parameters
$filter_lab = $_GET['filter_lab'] ?? '';
$filter_lecturer = $_GET['filter_lecturer'] ?? '';
$current_week = date('Y-m-d', strtotime('monday this week'));

// Define time slots (8 AM to 5 PM)
$time_slots = [
    '08:00' => '08:00 - 09:00',
    '09:00' => '09:00 - 10:00',
    '10:00' => '10:00 - 11:00',
    '11:00' => '11:00 - 12:00',
    '12:00' => '12:00 - 13:00',
    '13:00' => '13:00 - 14:00',
    '14:00' => '14:00 - 15:00',
    '15:00' => '15:00 - 16:00',
    '16:00' => '16:00 - 17:00'
];

$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
$day_names = [
    'monday' => 'Monday',
    'tuesday' => 'Tuesday', 
    'wednesday' => 'Wednesday',
    'thursday' => 'Thursday',
    'friday' => 'Friday'
];

try {
    // Get all labs for filter dropdown
    $stmt = $pdo->query("SELECT id, name FROM labs ORDER BY name");
    $all_labs = $stmt->fetchAll();
    
    // Get all lecturers for filter dropdown
    $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'lecturer' ORDER BY name");
    $all_lecturers = $stmt->fetchAll();
    
    // Build query for timetable data
    $timetable_query = "
        SELECT 
            lt.id,
            lt.lab_id,
            lt.day_of_week,
            lt.start_time,
            lt.end_time,
            lt.subject,
            lt.semester,
            lt.batch,
            l.name as lab_name,
            u.name as lecturer_name
        FROM lab_timetables lt
        JOIN labs l ON lt.lab_id = l.id
        LEFT JOIN users u ON lt.lecturer_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($filter_lab) {
        $timetable_query .= " AND lt.lab_id = ?";
        $params[] = $filter_lab;
    }
    
    if ($filter_lecturer) {
        $timetable_query .= " AND lt.lecturer_id = ?";
        $params[] = $filter_lecturer;
    }
    
    $timetable_query .= " ORDER BY lt.day_of_week, lt.start_time";
    
    $stmt = $pdo->prepare($timetable_query);
    $stmt->execute($params);
    $timetable_data = $stmt->fetchAll();
    
    // Organize data by day and time
    $timetable = [];
    foreach ($timetable_data as $row) {
        $day = $row['day_of_week'];
        $start_time = substr($row['start_time'], 0, 5); // Format HH:MM
        
        if (!isset($timetable[$day])) {
            $timetable[$day] = [];
        }
        
        $timetable[$day][$start_time] = $row;
    }
    
    // Also get today's reservations for additional context
    $today = date('Y-m-d');
    $reservation_query = "
        SELECT 
            lr.lab_id,
            lr.start_time,
            lr.end_time,
            lr.purpose,
            l.name as lab_name,
            u.name as requester_name
        FROM lab_reservations lr
        JOIN labs l ON lr.lab_id = l.id
        JOIN users u ON lr.user_id = u.id
        WHERE lr.reservation_date = ? AND lr.status = 'approved'
    ";
    
    if ($filter_lab) {
        $reservation_query .= " AND lr.lab_id = ?";
        $res_params = [$today, $filter_lab];
    } else {
        $res_params = [$today];
    }
    
    $stmt = $pdo->prepare($reservation_query);
    $stmt->execute($res_params);
    $today_reservations = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
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
    <style>
        .timetable-container {
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .timetable-filters {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: bold;
            color: #495057;
            font-size: 0.9em;
        }

        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            min-width: 150px;
        }

        .filter-actions {
            margin-left: auto;
        }

        .btn-filter {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-filter:hover {
            background: #0056b3;
        }

        .btn-clear {
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 5px;
        }

        .timetable-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9em;
            table-layout: fixed;
            display: table !important;
        }

        .timetable-grid thead {
            display: table-header-group !important;
        }

        .timetable-grid tbody {
            display: table-row-group !important;
        }

        .timetable-grid tr {
            display: table-row !important;
        }

        .timetable-grid th,
        .timetable-grid td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: top;
            display: table-cell !important;
        }

        .timetable-grid th {
            background: #343a40;
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .time-slot {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
            min-width: 120px;
        }

        .timetable-cell {
            height: 60px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .timetable-cell:hover {
            background: #e3f2fd;
        }

        .timetable-entry {
            background: #007bff;
            color: white;
            padding: 4px 6px;
            border-radius: 4px;
            font-size: 0.8em;
            line-height: 1.2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .timetable-entry:hover {
            background: #0056b3;
            transform: scale(1.02);
        }

        .entry-subject {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .entry-details {
            font-size: 0.7em;
            opacity: 0.9;
        }

        .reservation-entry {
            background: #28a745;
            color: white;
            padding: 4px 6px;
            border-radius: 4px;
            font-size: 0.8em;
            line-height: 1.2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            cursor: pointer;
            border-left: 4px solid #1e7e34;
        }

        .empty-cell {
            background: #f8f9fa;
            color: #6c757d;
            font-size: 0.8em;
        }

        .today-indicator {
            background: background-color;
            border-left: 4px solid #ffc107;
        }

        .legend {
            margin-top: 15px;
            display: flex;
            gap: 20px;
            font-size: 0.9em;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 15px;
            border-radius: 3px;
        }

        .tooltip {
            position: relative;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 250px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 6px;
            padding: 10px;
            position: absolute;
            z-index: 1000;
            top: -5px;
            left: 105%;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8em;
            line-height: 1.4;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .timetable-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-actions {
                margin-left: 0;
                margin-top: 10px;
            }
            
            .timetable-grid {
                font-size: 0.8em;
            }
            
            .timetable-grid th,
            .timetable-grid td {
                padding: 4px;
            }
            
            .legend {
                flex-direction: column;
                gap: 10px;
            }
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
                        <h1>📅 Lab Timetable</h1>
                        <p>Weekly schedule and lab availability overview</p>
                    </div>
                    <div class="page-actions">
                        <a href="./" class="btn btn-outline-secondary">
                            ← Back to Dashboard
                        </a>
                    </div>
                </div>

                <div class="timetable-container">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filter Controls -->
                    <form method="GET" class="timetable-filters">
                        <input type="hidden" name="page" value="timetable">
                        <div class="filter-group">
                            <label for="filter_lab">Filter by Lab:</label>
                            <select name="filter_lab" id="filter_lab">
                                <option value="">All Labs</option>
                                <?php foreach ($all_labs as $lab): ?>
                                    <option value="<?php echo $lab['id']; ?>" <?php echo $filter_lab == $lab['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lab['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter_lecturer">Filter by Lecturer:</label>
                            <select name="filter_lecturer" id="filter_lecturer">
                                <option value="">All Lecturers</option>
                                <?php foreach ($all_lecturers as $lecturer): ?>
                                    <option value="<?php echo $lecturer['id']; ?>" <?php echo $filter_lecturer == $lecturer['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lecturer['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">Apply Filter</button>
                            <a href="?page=timetable" class="btn-clear">Clear</a>
                        </div>
                    </form>
                    
                    <!-- Timetable Grid -->
                    <table class="timetable-grid">
                        <thead>
                            <tr>
                                <th class="time-slot">Time</th>
                                <?php foreach ($days as $day): ?>
                                    <th class="<?php echo date('l') == ucfirst($day) ? 'today-indicator' : ''; ?>">
                                        <?php echo $day_names[$day]; ?>
                                        <?php if (date('l') == ucfirst($day)): ?>
                                            <br><small>(Today)</small>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($time_slots as $time => $time_label): ?>
                                <tr>
                                    <td class="time-slot"><?php echo $time_label; ?></td>
                                    <?php foreach ($days as $day): ?>
                                        <td class="timetable-cell <?php echo date('l') == ucfirst($day) ? 'today-indicator' : ''; ?>">
                                            <?php if (isset($timetable[$day][$time])): ?>
                                                <?php $entry = $timetable[$day][$time]; ?>
                                                <div class="timetable-entry tooltip">
                                                    <div class="entry-subject"><?php echo htmlspecialchars($entry['subject']); ?></div>
                                                    <div class="entry-details">
                                                        <?php echo htmlspecialchars($entry['lab_name']); ?><br>
                                                        <?php echo htmlspecialchars($entry['batch']); ?>
                                                    </div>
                                                    <span class="tooltiptext">
                                                        <strong>Subject:</strong> <?php echo htmlspecialchars($entry['subject']); ?><br>
                                                        <strong>Lab:</strong> <?php echo htmlspecialchars($entry['lab_name']); ?><br>
                                                        <strong>Lecturer:</strong> <?php echo htmlspecialchars($entry['lecturer_name'] ?? 'Not assigned'); ?><br>
                                                        <strong>Time:</strong> <?php echo substr($entry['start_time'], 0, 5) . ' - ' . substr($entry['end_time'], 0, 5); ?><br>
                                                        <strong>Batch:</strong> <?php echo htmlspecialchars($entry['batch']); ?><br>
                                                        <strong>Semester:</strong> <?php echo htmlspecialchars($entry['semester']); ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <!-- Check for today's reservations if this is today -->
                                                <?php 
                                                $found_reservation = false;
                                                if (date('l') == ucfirst($day)) {
                                                    foreach ($today_reservations as $reservation) {
                                                        $res_start = substr($reservation['start_time'], 0, 5);
                                                        $res_end = substr($reservation['end_time'], 0, 5);
                                                        if ($res_start <= $time && $time < $res_end) {
                                                            $found_reservation = $reservation;
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                
                                                <?php if ($found_reservation): ?>
                                                    <div class="reservation-entry tooltip">
                                                        <div class="entry-subject">Reserved</div>
                                                        <div class="entry-details">
                                                            <?php echo htmlspecialchars($found_reservation['lab_name']); ?>
                                                        </div>
                                                        <span class="tooltiptext">
                                                            <strong>Type:</strong> Reservation<br>
                                                            <strong>Lab:</strong> <?php echo htmlspecialchars($found_reservation['lab_name']); ?><br>
                                                            <strong>Requester:</strong> <?php echo htmlspecialchars($found_reservation['requester_name']); ?><br>
                                                            <strong>Time:</strong> <?php echo substr($found_reservation['start_time'], 0, 5) . ' - ' . substr($found_reservation['end_time'], 0, 5); ?><br>
                                                            <strong>Purpose:</strong> <?php echo htmlspecialchars($found_reservation['purpose']); ?>
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="empty-cell">Free</div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Legend -->
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #007bff;"></div>
                            <span>Regular Timetable</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #28a745;"></div>
                            <span>Today's Reservations</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #fff3cd; border: 1px solid #ffc107;"></div>
                            <span>Today</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f8f9fa; border: 1px solid #ddd;"></div>
                            <span>Free Time</span>
                        </div>
                    </div>
                    
                    <?php if (empty($timetable_data) && empty($today_reservations)): ?>
                        <div class="no-data">
                            <h4>No timetable data found</h4>
                            <p>No regular classes or reservations are scheduled. Contact the administrator to set up the timetable.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add click handlers for timetable entries
        document.addEventListener('DOMContentLoaded', function() {
            const entries = document.querySelectorAll('.timetable-entry, .reservation-entry');
            
            entries.forEach(entry => {
                entry.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // Get tooltip text and show in a more prominent way
                    const tooltip = this.querySelector('.tooltiptext');
                    if (tooltip) {
                        const details = tooltip.innerHTML;
                        
                        // Create a modal-like overlay
                        const overlay = document.createElement('div');
                        overlay.style.cssText = `
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0,0,0,0.5);
                            z-index: 9999;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        `;
                        
                        const modal = document.createElement('div');
                        modal.style.cssText = `
                            background: white;
                            padding: 20px;
                            border-radius: 8px;
                            max-width: 400px;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                        `;
                        
                        modal.innerHTML = `
                            <h4 style="margin-top: 0; color: #343a40;">Schedule Details</h4>
                            <div style="line-height: 1.6;">${details}</div>
                            <button style="margin-top: 15px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Close</button>
                        `;
                        
                        overlay.appendChild(modal);
                        document.body.appendChild(overlay);
                        
                        // Close modal handlers
                        const closeBtn = modal.querySelector('button');
                        closeBtn.onclick = () => document.body.removeChild(overlay);
                        overlay.onclick = (e) => {
                            if (e.target === overlay) {
                                document.body.removeChild(overlay);
                            }
                        };
                    }
                });
            });
        });
    </script>
</body>
</html>