<?php
$current_page = basename($_SERVER['PHP_SELF']);
$request_uri = $_SERVER['REQUEST_URI'];

// Simple and reliable path calculation
// Count how many directories deep we are from the geo-cms root
$script_path = $_SERVER['SCRIPT_NAME'];
$path_segments = explode('/', trim($script_path, '/'));

// Find geo-cms position
$geo_cms_pos = false;
foreach ($path_segments as $i => $segment) {
    if ($segment === 'geo-cms') {
        $geo_cms_pos = $i;
        break;
    }
}

if ($geo_cms_pos !== false) {
    // Calculate depth: total segments - geo_cms_position - 1 (for the file itself) - 1 (for zero-based)
    $depth = count($path_segments) - $geo_cms_pos - 2;
    $base_path = str_repeat('../', max(0, $depth));
} else {
    // Fallback
    $base_path = '../';
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo $base_path; ?>images/faculty-logo.png" alt="Faculty Logo" class="sidebar-logo" onerror="this.style.display='none'">
        <h3>Geo CMS</h3>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li><a href="<?php echo $base_path; ?>dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <span class="icon">🏠</span>
                Dashboard
            </a></li>
            <li><a href="<?php echo $base_path; ?>store/" class="<?php echo (strpos($request_uri, '/store/') !== false) ? 'active' : ''; ?>">
                    <span class="icon">📦</span>
                    Store
                </a></li>
            <li><a href="<?php echo $base_path; ?>labs/" class="<?php echo (strpos($request_uri, '/labs/') !== false) ? 'active' : ''; ?>">
                <span class="icon">🔬</span>
                Labs
            </a></li>
            <li><a href="<?php echo $base_path; ?>issues/" class="<?php echo (strpos($request_uri, '/issues/') !== false) ? 'active' : ''; ?>">
                <span class="icon">🚨</span>
                Issues
            </a></li>
            <li><a href="<?php echo $base_path; ?>profile.php" class="<?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <span class="icon">👤</span>
                Profile
            </a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="<?php echo $base_path; ?>admin/" class="<?php echo (strpos($request_uri, '/admin/') !== false) ? 'active' : ''; ?>">
                    <span class="icon">⚙️</span>
                    Admin Panel
                </a></li>
            <?php endif; ?>
            <li><a href="<?php echo $base_path; ?>settings.php" class="<?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <span class="icon">⚙️</span>
                Settings
            </a></li>
            <li><a href="<?php echo $base_path; ?>php/logout.php" onclick="return confirm('Are you sure you want to logout?')">
                <span class="icon">🚪</span>
                Logout
            </a></li>
        </ul>
    </nav>
</aside>