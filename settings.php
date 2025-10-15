<?php
require_once 'php/config.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];
$user_identity = $_SESSION['user_identity'];

$page_title = 'Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .settings-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .settings-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
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
        
        .settings-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .settings-item:last-child {
            border-bottom: none;
        }
        
        .setting-info {
            flex: 1;
        }
        
        .setting-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 4px;
        }
        
        .setting-description {
            font-size: 14px;
            color: #6b7280;
        }
        
        .coming-soon {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .coming-soon-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .redirect-info {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            color: #0c4a6e;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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

            <!-- Settings Content -->
            <div class="settings-container">
                <div class="redirect-info">
                    <strong>💡 Quick Access:</strong> 
                    Most user settings can be found in your <a href="profile.php" style="color: #0ea5e9; text-decoration: underline;">Profile Page</a> 
                    where you can update your personal information and change your password.
                </div>

                <!-- Application Settings -->
                <div class="settings-section">
                    <h2 class="section-title">
                        <span>⚙️</span>
                        Application Settings
                    </h2>
                    
                    <div class="coming-soon">
                        <div class="coming-soon-icon">🔧</div>
                        <h3>Settings Panel Coming Soon</h3>
                        <p>Advanced application settings and preferences will be available here in a future update.</p>
                        <p>For now, you can manage your personal settings in your <a href="profile.php">Profile Page</a>.</p>
                    </div>
                </div>

                <!-- System Information -->
                <div class="settings-section">
                    <h2 class="section-title">
                        <span>📊</span>
                        System Information
                    </h2>
                    
                    <div class="settings-item">
                        <div class="setting-info">
                            <div class="setting-label">Application Name</div>
                            <div class="setting-description">Current application identifier</div>
                        </div>
                        <div class="setting-value">
                            <strong><?php echo APP_NAME; ?></strong>
                        </div>
                    </div>
                    
                    <div class="settings-item">
                        <div class="setting-info">
                            <div class="setting-label">Your Role</div>
                            <div class="setting-description">Current user permission level</div>
                        </div>
                        <div class="setting-value">
                            <span class="role-badge role-<?php echo $user_role; ?>" style="
                                display: inline-block;
                                padding: 4px 12px;
                                border-radius: 20px;
                                font-size: 12px;
                                font-weight: 600;
                                text-transform: uppercase;
                                background: <?php 
                                    echo $user_role === 'admin' ? '#fee2e2' : 
                                         ($user_role === 'lecturer' ? '#dbeafe' : 
                                         ($user_role === 'staff' ? '#d1fae5' : '#fef3c7')); 
                                ?>;
                                color: <?php 
                                    echo $user_role === 'admin' ? '#991b1b' : 
                                         ($user_role === 'lecturer' ? '#1e40af' : 
                                         ($user_role === 'staff' ? '#065f46' : '#92400e')); 
                                ?>;
                            ">
                                <?php echo ucfirst($user_role); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="settings-item">
                        <div class="setting-info">
                            <div class="setting-label">Session Status</div>
                            <div class="setting-description">Current login session information</div>
                        </div>
                        <div class="setting-value">
                            <span style="color: #059669; font-weight: 500;">Active</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="settings-section">
                    <h2 class="section-title">
                        <span>⚡</span>
                        Quick Actions
                    </h2>
                    
                    <div class="settings-item">
                        <div class="setting-info">
                            <div class="setting-label">Profile Settings</div>
                            <div class="setting-description">Update your personal information and change password</div>
                        </div>
                        <div class="setting-action">
                            <a href="profile.php" class="btn btn-primary" style="
                                padding: 8px 16px;
                                border-radius: 6px;
                                background: #3b82f6;
                                color: white;
                                text-decoration: none;
                                font-size: 14px;
                                font-weight: 500;
                            ">Go to Profile</a>
                        </div>
                    </div>
                    
                    <div class="settings-item">
                        <div class="setting-info">
                            <div class="setting-label">Dashboard</div>
                            <div class="setting-description">Return to your main dashboard</div>
                        </div>
                        <div class="setting-action">
                            <a href="dashboard.php" class="btn btn-secondary" style="
                                padding: 8px 16px;
                                border-radius: 6px;
                                background: #6b7280;
                                color: white;
                                text-decoration: none;
                                font-size: 14px;
                                font-weight: 500;
                            ">Go to Dashboard</a>
                        </div>
                    </div>
                    
                    <div class="settings-item">
                        <div class="setting-info">
                            <div class="setting-label">Logout</div>
                            <div class="setting-description">End your current session</div>
                        </div>
                        <div class="setting-action">
                            <a href="php/logout.php" 
                               onclick="return confirm('Are you sure you want to logout?')" 
                               class="btn btn-outline" style="
                                padding: 8px 16px;
                                border-radius: 6px;
                                background: transparent;
                                border: 1px solid #d1d5db;
                                color: #374151;
                                text-decoration: none;
                                font-size: 14px;
                                font-weight: 500;
                            ">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="js/script.js"></script>
</body>
</html>