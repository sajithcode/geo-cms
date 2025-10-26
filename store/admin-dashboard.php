<?php
require_once '../php/config.php';

// Require user to be logged in as admin
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Only allow admin
if ($user_role !== 'admin') {
    redirectTo('index.php');
}

$page_title = 'Store Management - Admin';

// Get comprehensive statistics
try {
    // Request statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
        FROM borrow_requests
    ");
    $request_stats = $stmt->fetch();
    
    // Store statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_items,
            SUM(quantity_total) as total_quantity,
            SUM(quantity_available) as available_quantity,
            SUM(quantity_borrowed) as borrowed_quantity,
            SUM(quantity_maintenance) as maintenance_quantity
        FROM store_items
    ");
    $inventory_stats = $stmt->fetch();
    
    // Categories
    $stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM store_categories");
    $category_count = $stmt->fetchColumn();
    
    // Recent activity
    $stmt = $pdo->prepare("
        SELECT br.*,
               GROUP_CONCAT(
                   CONCAT(ii.name, ' (x', bri.quantity, ')')
                   ORDER BY ii.name SEPARATOR ', '
               ) as item_names,
               GROUP_CONCAT(ii.image_path SEPARATOR ',') as image_paths,
               SUM(bri.quantity) as total_quantity,
               u.name as requester_name
        FROM borrow_requests br
        JOIN borrow_request_items bri ON br.id = bri.borrow_request_id
        JOIN store_items ii ON bri.item_id = ii.id
        JOIN users u ON br.user_id = u.id
        GROUP BY br.id
        ORDER BY br.request_date DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_requests = $stmt->fetchAll();
    
    // Get all store items
    $stmt = $pdo->prepare("
        SELECT ii.*, ic.name as category_name
        FROM store_items ii
        LEFT JOIN store_categories ic ON ii.category_id = ic.id
        ORDER BY ii.name ASC
    ");
    $stmt->execute();
    $inventory_items = $stmt->fetchAll();
    
    // Get all categories
    $stmt = $pdo->query("SELECT * FROM store_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
    
    // Damage reports (if you implement issue tracking for inventory)
    $damage_reports = 0; // Placeholder
    
} catch (PDOException $e) {
    error_log("Admin inventory error: " . $e->getMessage());
    $request_stats = ['total_requests' => 0, 'pending_requests' => 0, 'approved_requests' => 0, 'rejected_requests' => 0];
    $inventory_stats = ['total_items' => 0, 'total_quantity' => 0, 'available_quantity' => 0, 'borrowed_quantity' => 0, 'maintenance_quantity' => 0];
    $category_count = 0;
    $recent_requests = [];
    $inventory_items = [];
    $categories = [];
    $damage_reports = 0;
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
    <link rel="stylesheet" href="../css/store.css">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
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
                        <h1>📦 Store Management - Admin</h1>
                        <p>Complete store control and analytics</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-outline-secondary" onclick="exportReport()">
                            📊 Export Report
                        </button>
                        <button class="btn btn-outline-primary" onclick="showModal('category-modal')">
                            ➕ Add Category
                        </button>
                        <button class="btn btn-primary" onclick="showModal('item-modal')">
                            ➕ Add Item
                        </button>
                    </div>
                </div>

                <!-- Analytics Panel -->
                <div class="analytics-panel">
                    <h2>📊 Analytics Dashboard</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">📦</div>
                            <div class="stat-info">
                                <h3><?php echo $inventory_stats['total_items']; ?></h3>
                                <p>Total Items</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🔢</div>
                            <div class="stat-info">
                                <h3><?php echo $inventory_stats['total_quantity']; ?></h3>
                                <p>Total Quantity</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🔄</div>
                            <div class="stat-info">
                                <h3><?php echo $inventory_stats['borrowed_quantity']; ?></h3>
                                <p>Borrowed Count</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">⚠️</div>
                            <div class="stat-info">
                                <h3><?php echo $damage_reports; ?></h3>
                                <p>Damage Reports</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📋</div>
                            <div class="stat-info">
                                <h3><?php echo $request_stats['pending_requests']; ?></h3>
                                <p>Pending Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📂</div>
                            <div class="stat-info">
                                <h3><?php echo $category_count; ?></h3>
                                <p>Categories</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="quick-actions-grid">
                        <div class="action-card" onclick="window.location.href='staff-dashboard.php'">
                            <div class="action-icon">📋</div>
                            <div class="action-text">
                                <h4>Manage Requests</h4>
                                <p>Review and approve borrow requests</p>
                            </div>
                        </div>
                        <div class="action-card" onclick="showModal('bulk-import-modal')">
                            <div class="action-icon">📥</div>
                            <div class="action-text">
                                <h4>Bulk Import</h4>
                                <p>Import items from CSV file</p>
                            </div>
                        </div>
                        <div class="action-card" onclick="generateReport()">
                            <div class="action-icon">📊</div>
                            <div class="action-text">
                                <h4>Generate Report</h4>
                                <p>Create detailed store reports</p>
                            </div>
                        </div>
                        <div class="action-card" onclick="showModal('maintenance-modal')">
                            <div class="action-icon">🔧</div>
                            <div class="action-text">
                                <h4>Maintenance Mode</h4>
                                <p>Mark items for maintenance</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Items Management -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Inventory Items</h2>
                        <div class="section-actions">
                            <div class="filter-group">
                                <select id="category-filter" class="form-control">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="search-group">
                                <input type="text" id="item-search" class="form-control" placeholder="Search items...">
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover" id="items-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Total</th>
                                    <th>Available</th>
                                    <th>Borrowed</th>
                                    <th>Maintenance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inventory_items)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">📦</div>
                                                <h3>No Items Found</h3>
                                                <p>Start by adding your first store item.</p>
                                                <button class="btn btn-primary" onclick="showModal('item-modal')">Add First Item</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inventory_items as $item): ?>
                                        <tr data-category="<?php echo $item['category_id']; ?>">
                                            <td class="image-column">
                                                <?php if ($item['image_path']): ?>
                                                    <div class="item-image-container">
                                                        <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                             class="item-table-image clickable-image"
                                                             onclick="showImagePreview('../<?php echo htmlspecialchars($item['image_path']); ?>', '<?php echo htmlspecialchars($item['name']); ?>')"
                                                             onerror="this.parentElement.innerHTML='<span class=&quot;no-image&quot;>📷</span>'">
                                                    </div>
                                                <?php else: ?>
                                                    <span class="no-image">📷</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="item-details">
                                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                    <?php if ($item['description']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo $item['quantity_total']; ?></td>
                                            <td>
                                                <span class="quantity-badge <?php echo $item['quantity_available'] > 0 ? 'available' : 'unavailable'; ?>">
                                                    <?php echo $item['quantity_available']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $item['quantity_borrowed']; ?></td>
                                            <td><?php echo $item['quantity_maintenance']; ?></td>
                                            <td>
                                                <?php
                                                $availability_percentage = $item['quantity_total'] > 0 ? ($item['quantity_available'] / $item['quantity_total']) * 100 : 0;
                                                $status_class = $availability_percentage > 50 ? 'good' : ($availability_percentage > 20 ? 'warning' : 'critical');
                                                ?>
                                                <span class="status-indicator status-<?php echo $status_class; ?>">
                                                    <?php echo round($availability_percentage, 1); ?>% Available
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editItem(<?php echo $item['id']; ?>)">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="viewHistory(<?php echo $item['id']; ?>)">
                                                        History
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Recent Activity</h2>
                        <div class="section-actions">
                            <button class="btn btn-outline-primary" onclick="window.location.href='staff-dashboard.php'">
                                View All Requests
                            </button>
                        </div>
                    </div>

                    <div class="activity-list">
                        <?php if (empty($recent_requests)): ?>
                            <div class="empty-state">
                                <p>No recent activity</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($recent_requests, 0, 5) as $request): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php
                                        $image_paths = explode(',', $request['image_paths']);
                                        $first_image = $image_paths[0] ?? '';
                                        ?>
                                        <?php if ($first_image): ?>
                                            <img src="../<?php echo htmlspecialchars($first_image); ?>"
                                                 alt="Items"
                                                 class="activity-item-image clickable-image"
                                                 onclick="showImagePreview('../<?php echo htmlspecialchars($first_image); ?>', 'Request Items')"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                            <span class="badge badge-<?php echo getStatusBadgeClass($request['status']); ?>" style="display: none;">
                                                <?php echo strtoupper(substr($request['status'], 0, 1)); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-<?php echo getStatusBadgeClass($request['status']); ?>">
                                                <?php echo strtoupper(substr($request['status'], 0, 1)); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-content">
                                        <p>
                                            <strong><?php echo htmlspecialchars($request['requester_name']); ?></strong>
                                            requested to borrow
                                            <strong><?php echo htmlspecialchars($request['item_names']); ?></strong>
                                            (Total Qty: <?php echo $request['total_quantity']; ?>)
                                        </p>
                                        <small class="text-muted">
                                            <?php echo formatDate($request['request_date'], 'DD/MM/YYYY HH:mm'); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Item Modal -->
    <div id="item-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 id="item-modal-title">Add New Item</h3>
                <button onclick="hideModal('item-modal')">&times;</button>
            </div>
            <form id="item-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="item-id" name="item_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-8">
                            <div class="form-group">
                                <label for="item-name" class="form-label">Item Name *</label>
                                <input type="text" id="item-name" name="name" class="form-control" required
                                       placeholder="Enter item name">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="item-category" class="form-label">Category</label>
                                <select id="item-category" name="category_id" class="form-control form-select">
                                    <option value="">Select category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="item-description" class="form-label">Description</label>
                        <textarea id="item-description" name="description" class="form-control" rows="2"
                                  placeholder="Enter item description"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="item-image" class="form-label">Item Image</label>
                        <div class="image-upload-container">
                            <input type="file" id="item-image" name="item_image" class="form-control" 
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="image-upload-help">
                                <small class="text-muted">Upload an image for this item (JPEG, PNG, GIF, WebP - Max 5MB)</small>
                            </div>
                            <div id="current-image-preview" class="current-image-preview" style="display: none;">
                                <img id="current-image" src="" alt="Current item image">
                                <div class="image-actions">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCurrentImage()">
                                        Remove Image
                                    </button>
                                </div>
                            </div>
                            <div id="new-image-preview" class="new-image-preview" style="display: none;">
                                <img id="preview-image" src="" alt="Preview">
                                <div class="image-actions">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeNewImage()">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-3">
                            <div class="form-group">
                                <label for="item-quantity-total" class="form-label">Total Quantity *</label>
                                <input type="number" id="item-quantity-total" name="quantity_total" class="form-control" 
                                       min="0" required placeholder="0">
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label for="item-quantity-available" class="form-label">Available *</label>
                                <input type="number" id="item-quantity-available" name="quantity_available" class="form-control" 
                                       min="0" required placeholder="0">
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label for="item-quantity-borrowed" class="form-label">Borrowed</label>
                                <input type="number" id="item-quantity-borrowed" name="quantity_borrowed" class="form-control" 
                                       min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label for="item-quantity-maintenance" class="form-label">Maintenance</label>
                                <input type="number" id="item-quantity-maintenance" name="quantity_maintenance" class="form-control" 
                                       min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('item-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="category-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Category</h3>
                <button onclick="hideModal('category-modal')">&times;</button>
            </div>
            <form id="category-form">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="category-name" class="form-label">Category Name *</label>
                        <input type="text" id="category-name" name="name" class="form-control" required
                               placeholder="Enter category name">
                    </div>

                    <div class="form-group">
                        <label for="category-description" class="form-label">Description</label>
                        <textarea id="category-description" name="description" class="form-control" rows="3"
                                  placeholder="Enter category description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('category-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="image-preview-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 id="image-preview-title">Item Image</h3>
                <button onclick="hideModal('image-preview-modal')">&times;</button>
            </div>
            <div class="modal-body text-center">
                <img id="preview-modal-image" src="" alt="" class="preview-modal-image">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('image-preview-modal')">Close</button>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script src="../js/store.js"></script>
    <script src="../js/admin-store.js"></script>
</body>
</html>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'returned': return 'secondary';
        default: return 'secondary';
    }
}
?>