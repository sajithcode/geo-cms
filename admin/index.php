<?php
require_once '../php/config.php';

// Require user to be logged in
requireLogin();

// Only allow admin
if ($_SESSION['role'] !== 'admin') {
    redirectTo('../dashboard.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];
$user_identity = $_SESSION['user_identity'];

$page_title = 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .admin-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .card-icon {
            font-size: 24px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
        }
        
        .card-description {
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .coming-soon {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
            background: #f8fafc;
            border-radius: 8px;
            border: 2px dashed #d1d5db;
        }
        
        .coming-soon-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #d1d5db;
            color: #374151;
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

            <!-- Admin Content -->
            <div class="admin-container">
                <!-- Admin Header -->
                <div class="admin-header">
                    <h1>👑 Admin Panel</h1>
                    <p>System administration and management tools</p>
                </div>

                <!-- Quick Stats -->
                <div class="admin-card">
                    <div class="card-header">
                        <span class="card-icon">📊</span>
                        <h2 class="card-title">System Overview</h2>
                    </div>
                    
                    <?php
                    try {
                        // Get system statistics
                        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                        $total_users = $stmt->fetchColumn();
                        
                        $stmt = $pdo->query("SELECT COUNT(*) FROM labs");
                        $total_labs = $stmt->fetchColumn();
                        
                        $stmt = $pdo->query("SELECT COUNT(*) FROM issue_reports");
                        $total_issues = $stmt->fetchColumn();
                        
                        $stmt = $pdo->query("SELECT COUNT(*) FROM lab_reservations");
                        $total_reservations = $stmt->fetchColumn();
                    ?>
                    
                    <div class="quick-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $total_users; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $total_labs; ?></div>
                            <div class="stat-label">Labs</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $total_issues; ?></div>
                            <div class="stat-label">Issues</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $total_reservations; ?></div>
                            <div class="stat-label">Reservations</div>
                        </div>
                    </div>
                    
                    <?php
                    } catch (PDOException $e) {
                        echo '<p style="color: #dc2626;">Error loading system statistics</p>';
                    }
                    ?>
                </div>

                <!-- Admin Modules Grid -->
                <div class="admin-grid">
                    <!-- User Management -->
                    <div class="admin-card">
                        <div class="card-header">
                            <span class="card-icon">👥</span>
                            <h3 class="card-title">User Management</h3>
                        </div>
                        <div class="card-description">
                            Manage user accounts, roles, and permissions
                        </div>
                        <div class="coming-soon">
                            <div class="coming-soon-icon">🚧</div>
                            <p><strong>Coming Soon</strong><br>User management interface</p>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="admin-card">
                        <div class="card-header">
                            <span class="card-icon">⚙️</span>
                            <h3 class="card-title">System Settings</h3>
                        </div>
                        <div class="card-description">
                            Configure application settings and preferences
                        </div>
                        <div class="coming-soon">
                            <div class="coming-soon-icon">🔧</div>
                            <p><strong>Coming Soon</strong><br>System configuration panel</p>
                        </div>
                    </div>

                    <!-- Reports & Analytics -->
                    <div class="admin-card">
                        <div class="card-header">
                            <span class="card-icon">📈</span>
                            <h3 class="card-title">Reports & Analytics</h3>
                        </div>
                        <div class="card-description">
                            View system reports and usage analytics
                        </div>
                        <div class="coming-soon">
                            <div class="coming-soon-icon">📊</div>
                            <p><strong>Coming Soon</strong><br>Analytics dashboard</p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="admin-card">
                        <div class="card-header">
                            <span class="card-icon">⚡</span>
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-description">
                            Access existing system features as admin
                        </div>
                        <div class="admin-actions">
                            <a href="../labs/admin-dashboard.php" class="btn btn-primary">
                                <span>🔬</span>
                                Labs Management
                            </a>
                            <a href="../store/admin-dashboard.php" class="btn btn-primary">
                                <span>📦</span>
                                Store Management
                            </a>
                            <a href="../issues/" class="btn btn-secondary">
                                <span>🚨</span>
                                Issue Reports
                            </a>
                            <a href="../profile.php" class="btn btn-outline">
                                <span>👤</span>
                                My Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="admin-card">
                    <div class="card-header">
                        <span class="card-icon">💡</span>
                        <h3 class="card-title">Information</h3>
                    </div>
                    <div style="background: #f0f9ff; border: 1px solid #0ea5e9; color: #0c4a6e; padding: 15px; border-radius: 8px;">
                        <strong>Admin Panel Under Development</strong><br>
                        This admin panel is currently under development. Most administrative functions can be accessed through the existing module dashboards:
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Labs Management: <a href="../labs/admin-dashboard.php" style="color: #0ea5e9;">Labs Admin Dashboard</a></li>
                            <li>Store Management: <a href="../store/admin-dashboard.php" style="color: #0ea5e9;">Store Admin Dashboard</a></li>
                            <li>User Profile: <a href="../profile.php" style="color: #0ea5e9;">Profile Page</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>