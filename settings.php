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
        
        /* Toggle Switch Styles */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #3b82f6;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .form-control {
            min-width: 150px;
        }
        
        /* Basic theme support */
        .theme-dark {
            --bg-color: #1f2937;
            --text-color: #f9fafb;
            --card-bg: #374151;
            --border-color: #4b5563;
        }
        
        .theme-light {
            --bg-color: #ffffff;
            --text-color: #111827;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
        }
        
        body.theme-dark {
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        body.theme-dark .settings-section {
            background: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-color);
        }
        
        body.theme-dark .settings-item {
            border-color: var(--border-color);
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
                    
                    <form id="settingsForm" method="POST" action="php/settings_process.php">
                        <!-- Theme Settings -->
                        <div class="settings-item">
                            <div class="setting-info">
                                <div class="setting-label">Theme Preference</div>
                                <div class="setting-description">Choose your preferred color theme</div>
                            </div>
                            <div class="setting-action">
                                <select name="theme" class="form-control" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;">
                                    <option value="light" <?php echo (isset($_SESSION['theme']) && $_SESSION['theme'] === 'light') ? 'selected' : ''; ?>>Light</option>
                                    <option value="dark" <?php echo (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'selected' : ''; ?>>Dark</option>
                                    <option value="auto" <?php echo (!isset($_SESSION['theme']) || $_SESSION['theme'] === 'auto') ? 'selected' : ''; ?>>Auto (System)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Notification Settings -->
                        <div class="settings-item">
                            <div class="setting-info">
                                <div class="setting-label">Email Notifications</div>
                                <div class="setting-description">Receive email notifications for important updates</div>
                            </div>
                            <div class="setting-action">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="email_notifications" value="1" 
                                           <?php echo (isset($_SESSION['email_notifications']) && $_SESSION['email_notifications']) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Dashboard Layout -->
                        <div class="settings-item">
                            <div class="setting-info">
                                <div class="setting-label">Dashboard Layout</div>
                                <div class="setting-description">Choose your preferred dashboard view</div>
                            </div>
                            <div class="setting-action">
                                <select name="dashboard_layout" class="form-control" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;">
                                    <option value="cards" <?php echo (isset($_SESSION['dashboard_layout']) && $_SESSION['dashboard_layout'] === 'cards') ? 'selected' : ''; ?>>Card View</option>
                                    <option value="list" <?php echo (isset($_SESSION['dashboard_layout']) && $_SESSION['dashboard_layout'] === 'list') ? 'selected' : ''; ?>>List View</option>
                                    <option value="compact" <?php echo (!isset($_SESSION['dashboard_layout']) || $_SESSION['dashboard_layout'] === 'compact') ? 'selected' : ''; ?>>Compact</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Items Per Page -->
                        <div class="settings-item">
                            <div class="setting-info">
                                <div class="setting-label">Items Per Page</div>
                                <div class="setting-description">Number of items to display per page in lists</div>
                            </div>
                            <div class="setting-action">
                                <select name="items_per_page" class="form-control" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;">
                                    <option value="10" <?php echo (isset($_SESSION['items_per_page']) && $_SESSION['items_per_page'] == 10) ? 'selected' : ''; ?>>10</option>
                                    <option value="25" <?php echo (isset($_SESSION['items_per_page']) && $_SESSION['items_per_page'] == 25) ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo (!isset($_SESSION['items_per_page']) || $_SESSION['items_per_page'] == 50) ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo (isset($_SESSION['items_per_page']) && $_SESSION['items_per_page'] == 100) ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Language Settings -->
                        <div class="settings-item">
                            <div class="setting-info">
                                <div class="setting-label">Language</div>
                                <div class="setting-description">Select your preferred language</div>
                            </div>
                            <div class="setting-action">
                                <select name="language" class="form-control" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;">
                                    <option value="en" <?php echo (!isset($_SESSION['language']) || $_SESSION['language'] === 'en') ? 'selected' : ''; ?>>English</option>
                                    <option value="si" <?php echo (isset($_SESSION['language']) && $_SESSION['language'] === 'si') ? 'selected' : ''; ?>>සිංහල (Sinhala)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Save Button -->
                        <div class="settings-item" style="border-top: 2px solid #e5e7eb; margin-top: 20px; padding-top: 20px;">
                            <div class="setting-info">
                                <div class="setting-label">Save Settings</div>
                                <div class="setting-description">Apply your preference changes</div>
                            </div>
                            <div class="setting-action">
                                <button type="submit" class="btn btn-primary" style="
                                    padding: 10px 20px;
                                    border-radius: 6px;
                                    background: #3b82f6;
                                    color: white;
                                    border: none;
                                    font-size: 14px;
                                    font-weight: 500;
                                    cursor: pointer;
                                ">Save Changes</button>
                            </div>
                        </div>
                    </form>
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
    <script>
        // Handle success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['settings_success'])): ?>
                showMessage('<?php echo $_SESSION['settings_success']; ?>', 'success');
                <?php unset($_SESSION['settings_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['settings_error'])): ?>
                showMessage('<?php echo $_SESSION['settings_error']; ?>', 'error');
                <?php unset($_SESSION['settings_error']); ?>
            <?php endif; ?>
        });

        // Apply theme immediately when changed
        document.querySelector('select[name="theme"]').addEventListener('change', function() {
            const theme = this.value;
            applyTheme(theme);
        });

        function applyTheme(theme) {
            const body = document.body;
            body.classList.remove('theme-light', 'theme-dark', 'theme-auto');

            if (theme === 'dark') {
                body.classList.add('theme-dark');
            } else if (theme === 'light') {
                body.classList.add('theme-light');
            } else {
                // Auto theme - detect system preference
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    body.classList.add('theme-dark');
                } else {
                    body.classList.add('theme-light');
                }
            }
        }

        function showMessage(message, type) {
            // Create message element
            const messageDiv = document.createElement('div');
            messageDiv.className = `alert alert-${type}`;
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 1000;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
            `;
            messageDiv.textContent = message;

            document.body.appendChild(messageDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }

        // Apply current theme on page load
        const currentTheme = '<?php echo isset($_SESSION['theme']) ? $_SESSION['theme'] : 'auto'; ?>';
        applyTheme(currentTheme);
    </script>
</body>
</html>