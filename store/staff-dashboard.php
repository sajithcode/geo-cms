<?php
require_once '../php/config.php';

// Require user to be logged in as staff or admin
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Only allow staff and admin
if (!in_array($user_role, ['staff', 'admin'])) {
    redirectTo('index.php');
}

$page_title = 'Store Management - Staff';

// Get all borrow requests
try {
    $stmt = $pdo->prepare("
        SELECT br.*, ii.name as item_name, ii.description as item_description, ii.image_path,
               u.name as requester_name, u.user_id as requester_id, u.email as requester_email,
               approver.name as approved_by_name
        FROM borrow_requests br
        JOIN store_items ii ON br.item_id = ii.id
        JOIN users u ON br.user_id = u.id
        LEFT JOIN users approver ON br.approved_by = approver.id
        ORDER BY 
            CASE WHEN br.status = 'pending' THEN 1 ELSE 2 END,
            br.request_date DESC
    ");
    $stmt->execute();
    $all_requests = $stmt->fetchAll();
    
    // Get request statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
        FROM borrow_requests
    ");
    $request_stats = $stmt->fetch();
    
    // Get inventory status
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
    
    // Get low stock items (less than 20% available)
    $stmt = $pdo->query("
        SELECT ii.*, ic.name as category_name,
               ROUND((quantity_available / quantity_total) * 100, 1) as availability_percentage
        FROM store_items ii
        LEFT JOIN store_categories ic ON ii.category_id = ic.id
        WHERE quantity_total > 0 
        AND (quantity_available / quantity_total) < 0.2
        ORDER BY availability_percentage ASC
    ");
    $low_stock_items = $stmt->fetchAll();

    // Get all store items for display to staff
    $stmt = $pdo->prepare("SELECT ii.*, ic.name as category_name FROM store_items ii LEFT JOIN store_categories ic ON ii.category_id = ic.id ORDER BY ii.name ASC");
    $stmt->execute();
    $inventory_items = $stmt->fetchAll();

    // Prepare available items for request modal (items with available quantity > 0)
    $available_items = [];
    foreach ($inventory_items as $it) {
        if (isset($it['quantity_available']) && $it['quantity_available'] > 0) {
            $available_items[] = $it;
        }
    }
    
} catch (PDOException $e) {
    error_log("Staff inventory error: " . $e->getMessage());
    $all_requests = [];
    $request_stats = ['total_requests' => 0, 'pending_requests' => 0, 'approved_requests' => 0, 'rejected_requests' => 0];
    $inventory_stats = ['total_items' => 0, 'total_quantity' => 0, 'available_quantity' => 0, 'borrowed_quantity' => 0, 'maintenance_quantity' => 0];
    $low_stock_items = [];
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
                        <h1>📦 Store Management - Staff</h1>
                        <p>Manage borrow requests and monitor store status</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-outline-primary" onclick="refreshData()">
                            🔄 Refresh
                        </button>
                        <button class="btn btn-primary" onclick="exportRequests()">
                            📊 Export Report
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $request_stats['pending_requests']; ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-info">
                            <h3><?php echo $inventory_stats['total_items']; ?></h3>
                            <p>Total Items</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3><?php echo $inventory_stats['available_quantity']; ?></h3>
                            <p>Available</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔄</div>
                        <div class="stat-info">
                            <h3><?php echo $inventory_stats['borrowed_quantity']; ?></h3>
                            <p>Borrowed</p>
                        </div>
                    </div>
                        </div>

                        <!-- Inventory Items (Staff view) -->
                        <div class="content-section">
                            <div class="section-header">
                                <h2>Inventory Items</h2>
                                <div class="section-actions">
                                    <div class="filter-group">
                                        <select id="staff-category-filter" class="form-control">
                                            <option value="">All Categories</option>
                                            <?php foreach (array_unique(array_column($inventory_items, 'category_name')) as $catName): if (!$catName) continue; ?>
                                                <option value="<?php echo htmlspecialchars($catName); ?>"><?php echo htmlspecialchars($catName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="search-group">
                                        <input type="text" id="staff-item-search" class="form-control" placeholder="Search items...">
                                    </div>
                                </div>
                            </div>

                            <div class="table-container">
                                <table class="table table-hover" id="staff-items-table">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Item Name</th>
                                            <th>Category</th>
                                            <th>Total</th>
                                            <th>Available</th>
                                            <th>Borrow</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($inventory_items)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <div class="empty-state">
                                                        <div class="empty-icon">📦</div>
                                                        <h3>No Items Found</h3>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($inventory_items as $item): ?>
                                                <tr data-category="<?php echo htmlspecialchars($item['category_name'] ?? ''); ?>">
                                                    <td class="image-column">
                                                        <?php if (!empty($item['image_path'])): ?>
                                                            <div class="item-image-container">
                                                                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-table-image clickable-image" onclick="showImagePreview('../<?php echo htmlspecialchars($item['image_path']); ?>', '<?php echo htmlspecialchars($item['name']); ?>')" onerror="this.parentElement.innerHTML='<span class=&quot;no-image&quot;>📷</span>'">
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="no-image">📷</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                        <?php if ($item['description']): ?>
                                                            <div class="text-muted"><?php echo htmlspecialchars($item['description']); ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo $item['quantity_total']; ?></td>
                                                    <td>
                                                        <span class="quantity-badge <?php echo $item['quantity_available'] > 0 ? 'available' : 'unavailable'; ?>">
                                                            <?php echo $item['quantity_available']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" onclick="openRequestModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>', <?php echo (int)$item['quantity_available']; ?>)">Request</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                <!-- Low Stock Alert -->
                <?php if (!empty($low_stock_items)): ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ Low Stock Alert:</strong>
                        <?php echo count($low_stock_items); ?> item(s) are running low on stock.
                        <button class="btn btn-sm btn-outline-warning" onclick="showModal('low-stock-modal')">View Details</button>
                    </div>
                <?php endif; ?>

                <!-- Borrow Requests Table -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Borrow Requests Management</h2>
                        <div class="section-actions">
                            <div class="filter-group">
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="returned">Returned</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <select id="item-filter" class="form-control">
                                    <option value="">All Items</option>
                                    <?php
                                    $items = array_unique(array_column($all_requests, 'item_name'));
                                    sort($items);
                                    foreach ($items as $item):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="search-group">
                                <input type="text" id="request-search" class="form-control" placeholder="Search by requester...">
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover" id="requests-table">
                            <thead>
                                <tr>
                                    <th>Requester</th>
                                    <th>Image</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                    <th>Borrow Period</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_requests)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">📋</div>
                                                <h3>No Requests Found</h3>
                                                <p>There are no borrow requests to display.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_requests as $request): ?>
                                        <tr data-status="<?php echo $request['status']; ?>" data-item="<?php echo htmlspecialchars($request['item_name']); ?>">
                                            <td>
                                                <div class="requester-info">
                                                    <strong><?php echo htmlspecialchars($request['requester_name']); ?></strong>
                                                    <small class="text-muted"><?php echo htmlspecialchars($request['requester_id']); ?></small>
                                                </div>
                                            </td>
                                            <td class="image-column">
                                                <?php if ($request['image_path']): ?>
                                                    <div class="item-image-container">
                                                        <img src="../<?php echo htmlspecialchars($request['image_path']); ?>" 
                                                             alt="<?php echo htmlspecialchars($request['item_name']); ?>"
                                                             class="item-table-image clickable-image"
                                                             onclick="showImagePreview('../<?php echo htmlspecialchars($request['image_path']); ?>', '<?php echo htmlspecialchars($request['item_name']); ?>')"
                                                             onerror="this.parentElement.innerHTML='<span class=&quot;no-image&quot;>📷</span>'">
                                                    </div>
                                                <?php else: ?>
                                                    <span class="no-image">📷</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="item-info">
                                                    <strong><?php echo htmlspecialchars($request['item_name']); ?></strong>
                                                    <?php if ($request['item_description']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($request['item_description']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo $request['quantity']; ?></td>
                                            <td>
                                                <div class="reason-text" title="<?php echo htmlspecialchars($request['reason']); ?>">
                                                    <?php echo strlen($request['reason']) > 50 ? substr(htmlspecialchars($request['reason']), 0, 50) . '...' : htmlspecialchars($request['reason']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($request['borrow_start_date'] && $request['borrow_end_date']): ?>
                                                    <div class="date-range">
                                                        <strong>From:</strong> <?php echo formatDate($request['borrow_start_date'], 'DD/MM/YYYY'); ?><br>
                                                        <strong>To:</strong> <?php echo formatDate($request['borrow_end_date'], 'DD/MM/YYYY'); ?>
                                                    </div>
                                                <?php elseif ($request['expected_return_date']): ?>
                                                    <div class="date-range">
                                                        <strong>Expected Return:</strong> <?php echo formatDate($request['expected_return_date'], 'DD/MM/YYYY'); ?>
                                                    </div>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($request['request_date'], 'DD/MM/YYYY HH:mm'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo getStatusBadgeClass($request['status']); ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewRequestDetails(<?php echo $request['id']; ?>)">
                                                        View
                                                    </button>
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="approveRequest(<?php echo $request['id']; ?>)">
                                                            Approve
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                                            Reject
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Inventory Status Table -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Store Status Overview</h2>
                        <div class="section-actions">
                            <button class="btn btn-outline-primary" onclick="window.location.href='../admin/manage-inventory.php'">
                                Manage Items
                            </button>
                        </div>
                    </div>

                    <div class="inventory-summary">
                        <div class="summary-grid">
                            <div class="summary-card">
                                <h4><?php echo $inventory_stats['total_quantity']; ?></h4>
                                <p>Total Items</p>
                            </div>
                            <div class="summary-card available">
                                <h4><?php echo $inventory_stats['available_quantity']; ?></h4>
                                <p>Available</p>
                            </div>
                            <div class="summary-card borrowed">
                                <h4><?php echo $inventory_stats['borrowed_quantity']; ?></h4>
                                <p>Borrowed</p>
                            </div>
                            <div class="summary-card maintenance">
                                <h4><?php echo $inventory_stats['maintenance_quantity']; ?></h4>
                                <p>Under Maintenance</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Request Details Modal -->
                <!-- Request Modal (Staff) -->
                <div id="request-modal" class="modal" style="display: none;">
                    <div class="modal-content modal-lg">
                        <div class="modal-header">
                            <h3>Request to Borrow Equipment</h3>
                            <button onclick="hideModal('request-modal')">&times;</button>
                        </div>
                        <form id="borrow-request-form">
                            <div class="modal-body">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                                <div class="form-group">
                                    <label for="item_id" class="form-label">Select Item *</label>
                                    <select id="item_id" name="item_id" class="form-control form-select" required>
                                        <option value="">Choose an item to borrow</option>
                                        <?php foreach ($available_items as $item): ?>
                                            <option value="<?php echo $item['id']; ?>" 
                                                    data-available="<?php echo $item['quantity_available']; ?>"
                                                    data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                                    data-image="<?php echo htmlspecialchars($item['image_path'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($item['name']); ?> 
                                                (Available: <?php echo $item['quantity_available']; ?>)
                                                <?php if ($item['category_name']): ?>
                                                    - <?php echo htmlspecialchars($item['category_name']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="item-details" id="item-details" style="display: none;">
                                    <div class="alert alert-info">
                                        <div class="row">
                                            <div class="col-8">
                                                <div class="item-info-text">
                                                    <strong>Item Description:</strong>
                                                    <p id="item-description"></p>
                                                    <strong>Available Quantity:</strong> <span id="available-quantity"></span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div id="item-image-preview" class="item-image-preview" style="display: none;">
                                                    <img id="selected-item-image" src="" alt="Item image" class="selected-item-image">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="quantity" class="form-label">Quantity *</label>
                                            <input type="number" id="quantity" name="quantity" class="form-control" 
                                                   min="1" max="1" required placeholder="Enter quantity">
                                            <small class="form-text">Maximum available: <span id="max-quantity">-</span></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="borrow_start_date" class="form-label">Borrow Start Date *</label>
                                            <input type="date" id="borrow_start_date" name="borrow_start_date" 
                                                   class="form-control" required 
                                                   min="<?php echo date('Y-m-d'); ?>">
                                            <small class="form-text">When you need to start using the equipment</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="borrow_end_date" class="form-label">Borrow End Date *</label>
                                            <input type="date" id="borrow_end_date" name="borrow_end_date" 
                                                   class="form-control" required 
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                            <small class="form-text">When you will return the equipment</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="reason" class="form-label">Reason for Borrowing *</label>
                                    <textarea id="reason" name="reason" class="form-control" rows="3" required
                                              placeholder="Please provide a detailed reason for borrowing this equipment..."></textarea>
                                    <small class="form-text">Be specific about your intended use to help with approval</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="hideModal('request-modal')">Cancel</button>
                                <button type="submit" class="btn btn-primary">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
    <div id="request-details-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Request Details</h3>
                <button onclick="hideModal('request-details-modal')">&times;</button>
            </div>
            <div class="modal-body" id="request-details-content">
                <!-- Content will be loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('request-details-modal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div id="approval-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="approval-title">Approve Request</h3>
                <button onclick="hideModal('approval-modal')">&times;</button>
            </div>
            <form id="approval-form">
                <div class="modal-body">
                    <input type="hidden" id="approval-request-id" name="request_id">
                    <input type="hidden" id="approval-action" name="action">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="approval-notes" class="form-label">Notes (Optional)</label>
                        <textarea id="approval-notes" name="notes" class="form-control" rows="3"
                                  placeholder="Add any notes or conditions for this decision..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('approval-modal')">Cancel</button>
                    <button type="submit" class="btn" id="approval-submit-btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Low Stock Modal -->
    <div id="low-stock-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Low Stock Items</h3>
                <button onclick="hideModal('low-stock-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Total</th>
                                <th>Available</th>
                                <th>Availability %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $item['quantity_total']; ?></td>
                                    <td><?php echo $item['quantity_available']; ?></td>
                                    <td>
                                        <span class="badge badge-warning">
                                            <?php echo $item['availability_percentage']; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('low-stock-modal')">Close</button>
            </div>
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
    <script src="../js/staff-store.js"></script>
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
