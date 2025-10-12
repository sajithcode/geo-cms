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

$page_title = 'Inventory Management - Staff';

// Get all borrow requests
try {
    $stmt = $pdo->prepare("
        SELECT br.*, ii.name as item_name, ii.description as item_description,
               u.name as requester_name, u.user_id as requester_id, u.email as requester_email,
               approver.name as approved_by_name
        FROM borrow_requests br
        JOIN inventory_items ii ON br.item_id = ii.id
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
        FROM inventory_items
    ");
    $inventory_stats = $stmt->fetch();
    
    // Get low stock items (less than 20% available)
    $stmt = $pdo->query("
        SELECT ii.*, ic.name as category_name,
               ROUND((quantity_available / quantity_total) * 100, 1) as availability_percentage
        FROM inventory_items ii
        LEFT JOIN inventory_categories ic ON ii.category_id = ic.id
        WHERE quantity_total > 0 
        AND (quantity_available / quantity_total) < 0.2
        ORDER BY availability_percentage ASC
    ");
    $low_stock_items = $stmt->fetchAll();
    
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
    <link rel="stylesheet" href="../css/inventory.css">
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
                        <h1>📦 Inventory Management - Staff</h1>
                        <p>Manage borrow requests and monitor inventory status</p>
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
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                    <th>Expected Return</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_requests)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">
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
                                            <td><?php echo $request['expected_return_date'] ? formatDate($request['expected_return_date']) : '-'; ?></td>
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
                        <h2>Inventory Status Overview</h2>
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

    <script src="../js/script.js"></script>
    <script src="../js/inventory.js"></script>
    <script src="../js/staff-inventory.js"></script>
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