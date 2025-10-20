<?php
$page_title = $page_title ?? 'Dashboard';
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
    </div>
</header>