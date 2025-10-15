<?php
require_once 'php/config.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];
$user_identity = $_SESSION['user_identity'];

// Get user statistics based on role
$stats = [];
try {
    switch ($user_role) {
        case 'admin':
            // Admin statistics
            $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE is_active = TRUE");
            $stats['total_users'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total_items FROM inventory_items");
            $stats['total_items'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as pending_requests FROM borrow_requests WHERE status = 'pending'");
            $stats['pending_requests'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as active_reservations FROM lab_reservations WHERE status = 'approved' AND reservation_date >= CURDATE()");
            $stats['active_reservations'] = $stmt->fetchColumn();
            break;
            
        case 'staff':
            // Staff statistics
            $stmt = $pdo->query("SELECT COUNT(*) as pending_borrow_requests FROM borrow_requests WHERE status = 'pending'");
            $stats['pending_borrow_requests'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as pending_lab_requests FROM lab_reservations WHERE status = 'pending'");
            $stats['pending_lab_requests'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total_inventory FROM inventory_items");
            $stats['total_inventory'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as open_issues FROM issue_reports WHERE status IN ('pending', 'in_progress')");
            $stats['open_issues'] = $stmt->fetchColumn();
            break;
            
        case 'lecturer':
            // Lecturer statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_reservations FROM lab_reservations WHERE user_id = ? AND reservation_date >= CURDATE()");
            $stmt->execute([$user_id]);
            $stats['my_reservations'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_pending_requests FROM lab_reservations WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$user_id]);
            $stats['my_pending_requests'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as available_labs FROM labs WHERE status = 'available'");
            $stats['available_labs'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_issues FROM issue_reports WHERE reported_by = ? AND status != 'resolved'");
            $stmt->execute([$user_id]);
            $stats['my_issues'] = $stmt->fetchColumn();
            break;
            
        case 'student':
            // Student statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_borrow_requests FROM borrow_requests WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$user_id]);
            $stats['my_borrow_requests'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as borrowed_items FROM borrow_requests WHERE user_id = ? AND status = 'approved'");
            $stmt->execute([$user_id]);
            $stats['borrowed_items'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_lab_requests FROM lab_reservations WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$user_id]);
            $stats['my_lab_requests'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as my_reports FROM issue_reports WHERE reported_by = ? AND status != 'resolved'");
            $stmt->execute([$user_id]);
            $stats['my_reports'] = $stmt->fetchColumn();
            break;
    }
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    // Keep whatever stats we got, don't reset to empty array
    // $stats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="images/faculty-logo.png" alt="Faculty Logo" class="sidebar-logo" onerror="this.style.display='none'">
                <h3>Geo CMS</h3>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="active">
                        <span class="icon">🏠</span>
                        Dashboard
                    </a></li>
                    <li><a href="inventory/">
                        <span class="icon">📦</span>
                        Inventory
                    </a></li>
                    <li><a href="labs/">
                        <span class="icon">🔬</span>
                        Labs
                    </a></li>
                    <li><a href="issues/">
                        <span class="icon">🚨</span>
                        Issues
                    </a></li>
                    <li><a href="profile.php">
                        <span class="icon">👤</span>
                        Profile
                    </a></li>
                    <li><a href="settings.php">
                        <span class="icon">⚙️</span>
                        Settings
                    </a></li>
                    <li><a href="php/logout.php" onclick="return confirm('Are you sure you want to logout?')">
                        <span class="icon">🚪</span>
                        Logout
                    </a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
                    <h1>Dashboard</h1>
                </div>
                
                <div class="header-right">
                    <div class="user-info">
                        <span class="user-role"><?php echo ucfirst($user_role); ?></span>
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-id">(<?php echo htmlspecialchars($user_identity); ?>)</span>
                    </div>
                    <div class="current-time" id="current-time"></div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p>Here's what's happening in the Faculty of Geomatics today.</p>
                </div>

                <!-- Quick Stats -->
                <div class="stats-grid">
                    <?php if ($user_role === 'admin'): ?>
                        <div class="stat-card">
                            <div class="stat-icon">👥</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_users'] ?? 0; ?></h3>
                                <p>Total Users</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📦</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_items'] ?? 0; ?></h3>
                                <p>Inventory Items</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">⏳</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['pending_requests'] ?? 0; ?></h3>
                                <p>Pending Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🔬</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['active_reservations'] ?? 0; ?></h3>
                                <p>Active Reservations</p>
                            </div>
                        </div>
                    <?php elseif ($user_role === 'staff'): ?>
                        <div class="stat-card">
                            <div class="stat-icon">📋</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['pending_borrow_requests'] ?? 0; ?></h3>
                                <p>Pending Borrow Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🔬</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['pending_lab_requests'] ?? 0; ?></h3>
                                <p>Pending Lab Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📦</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_inventory'] ?? 0; ?></h3>
                                <p>Total Inventory</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🚨</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['open_issues'] ?? 0; ?></h3>
                                <p>Open Issues</p>
                            </div>
                        </div>
                    <?php elseif ($user_role === 'lecturer'): ?>
                        <div class="stat-card">
                            <div class="stat-icon">🔬</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['my_reservations'] ?? 0; ?></h3>
                                <p>My Reservations</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">⏳</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['my_pending_requests'] ?? 0; ?></h3>
                                <p>Pending Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['available_labs'] ?? 0; ?></h3>
                                <p>Available Labs</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🚨</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['my_issues'] ?? 0; ?></h3>
                                <p>My Reports</p>
                            </div>
                        </div>
                    <?php else: // student ?>
                        <div class="stat-card">
                            <div class="stat-icon">📋</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['my_borrow_requests'] ?? 0; ?></h3>
                                <p>Pending Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📦</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['borrowed_items'] ?? 0; ?></h3>
                                <p>Borrowed Items</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🔬</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['my_lab_requests'] ?? 0; ?></h3>
                                <p>Lab Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🚨</div>
                            <div class="stat-info">
                                <h3><?php echo $stats['my_reports'] ?? 0; ?></h3>
                                <p>My Reports</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Main Tiles -->
                <div class="main-tiles">
                    <div class="tile inventory-tile">
                        <div class="tile-header">
                            <h3>📦 Inventory Management</h3>
                            <p>Manage equipment and borrowing requests</p>
                        </div>
                        <div class="tile-actions">
                            <?php if ($user_role === 'student'): ?>
                                <a href="inventory/" class="btn btn-primary">Request to Borrow</a>
                                <a href="inventory/" class="btn btn-outline-primary">My Requests</a>
                            <?php elseif ($user_role === 'staff'): ?>
                                <a href="inventory/" class="btn btn-primary">Manage Requests</a>
                                <a href="inventory/" class="btn btn-outline-primary">Inventory Status</a>
                            <?php elseif ($user_role === 'admin'): ?>
                                <a href="inventory/" class="btn btn-primary">Admin Dashboard</a>
                                <a href="inventory/" class="btn btn-outline-primary">Manage Items</a>
                            <?php else: ?>
                                <a href="inventory/" class="btn btn-primary">View Inventory</a>
                                <a href="inventory/" class="btn btn-outline-primary">Request Items</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tile labs-tile">
                        <div class="tile-header">
                            <h3>🔬 Labs Management</h3>
                            <p>Manage laboratory reservations and schedules</p>
                        </div>
                        <div class="tile-actions">
                            <?php if ($user_role === 'student'): ?>
                                <a href="labs/" class="btn btn-primary">Request Lab Use</a>
                                <a href="labs/" class="btn btn-outline-primary">View My Requests</a>
                            <?php elseif ($user_role === 'lecturer'): ?>
                                <a href="labs/" class="btn btn-primary">Request Lab Reservation</a>
                                <a href="labs/" class="btn btn-outline-primary">View Timetables</a>
                            <?php else: ?>
                                <a href="labs/" class="btn btn-primary">Manage Labs</a>
                                <a href="labs/" class="btn btn-outline-primary">Admin Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="issues/" class="action-card">
                            <div class="action-icon">🚨</div>
                            <div class="action-text">
                                <h4>Report Issue</h4>
                                <p>Report a problem with equipment or labs</p>
                            </div>
                        </a>
                        
                        <a href="labs/" class="action-card">
                            <div class="action-icon">🏢</div>
                            <div class="action-text">
                                <h4>Labs Overview</h4>
                                <p>View all lab statuses and availability</p>
                            </div>
                        </a>
                        
                        <?php if ($user_role === 'admin'): ?>
                            <a href="admin/manage-users.php" class="action-card">
                                <div class="action-icon">👥</div>
                                <div class="action-text">
                                    <h4>Manage Users</h4>
                                    <p>Add, edit, or remove user accounts</p>
                                </div>
                            </a>
                            
                            <a href="admin/system-logs.php" class="action-card">
                                <div class="action-icon">📊</div>
                                <div class="action-text">
                                    <h4>System Analytics</h4>
                                    <p>View system logs and analytics</p>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Faculty of Geomatics - Sabaragamuwa University of Sri Lanka</p>
            <div class="footer-links">
                <a href="mailto:geomatics@sab.ac.lk">Contact: geomatics@sab.ac.lk</a>
                <a href="#" onclick="showModal('help-modal')">Quick Help</a>
            </div>
        </div>
    </footer>

    <!-- Help Modal -->
    <div id="help-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Quick Help</h3>
                <button onclick="hideModal('help-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4>Getting Started:</h4>
                <ul>
                    <li><strong>Inventory:</strong> Request equipment or manage borrowing requests</li>
                    <li><strong>Labs:</strong> Reserve laboratory time or view schedules</li>
                    <li><strong>Issues:</strong> Report problems with equipment or facilities</li>
                    <li><strong>Profile:</strong> Update your personal information and preferences</li>
                </ul>
                <p>For technical support, contact the IT department or send an email to the support team.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('help-modal')">Close</button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>