<?php
$current_page = basename($_SERVER['PHP_SELF']);
$unread_notifications = getUnreadNotificationCount($_SESSION['user_id']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../images/faculty-logo.png" alt="Faculty Logo" class="sidebar-logo" onerror="this.style.display='none'">
        <h3>Geo CMS</h3>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li><a href="../dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <span class="icon">🏠</span>
                Dashboard
            </a></li>
            <li><a href="../inventory/" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/inventory/') !== false) ? 'active' : ''; ?>">
                <span class="icon">📦</span>
                Inventory
            </a></li>
            <li><a href="../labs/" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/labs/') !== false) ? 'active' : ''; ?>">
                <span class="icon">🔬</span>
                Labs
            </a></li>
            <li><a href="../issues/" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/issues/') !== false) ? 'active' : ''; ?>">
                <span class="icon">🚨</span>
                Issues
            </a></li>
            <li><a href="../profile.php" class="<?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <span class="icon">👤</span>
                Profile
            </a></li>
            <li><a href="../notifications.php" class="<?php echo ($current_page === 'notifications.php') ? 'active' : ''; ?>">
                <span class="icon">🔔</span>
                Notifications
                <?php if ($unread_notifications > 0): ?>
                    <span class="badge badge-danger"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="../admin/" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? 'active' : ''; ?>">
                    <span class="icon">⚙️</span>
                    Admin Panel
                </a></li>
            <?php endif; ?>
            <li><a href="../settings.php" class="<?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <span class="icon">⚙️</span>
                Settings
            </a></li>
            <li><a href="../php/logout.php" onclick="return confirm('Are you sure you want to logout?')">
                <span class="icon">🚪</span>
                Logout
            </a></li>
        </ul>
    </nav>
</aside>