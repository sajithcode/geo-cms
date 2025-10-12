<?php
$page_title = $page_title ?? 'Dashboard';
$unread_notifications = $unread_notifications ?? getUnreadNotificationCount($_SESSION['user_id']);
?>
<header class="top-header">
    <div class="header-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </div>
    
    <div class="header-right">
        <div class="user-info">
            <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <span class="user-id">(<?php echo htmlspecialchars($_SESSION['user_identity']); ?>)</span>
        </div>
        <div class="current-time" id="current-time"></div>
        <div class="notification-icon" onclick="toggleNotificationDropdown()">
            <span class="icon">🔔</span>
            <?php if ($unread_notifications > 0): ?>
                <span class="notification-badge"><?php echo $unread_notifications; ?></span>
            <?php endif; ?>
        </div>
    </div>
</header>