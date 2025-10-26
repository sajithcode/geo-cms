<?php
require_once '../php/config.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];

// Only allow students and lecturers
if (!in_array($user_role, ['student', 'lecturer'])) {
    redirectTo('index.php');
}

    // Get user's borrow requests
    try {
        $stmt = $pdo->prepare("
            SELECT br.*,
                   GROUP_CONCAT(
                       CONCAT(ii.name, ' (x', bri.quantity, ')')
                       ORDER BY ii.name SEPARATOR ', '
                   ) as item_names,
                   GROUP_CONCAT(ii.image_path SEPARATOR ',') as image_paths,
                   GROUP_CONCAT(ii.description SEPARATOR ' | ') as item_descriptions,
                   SUM(bri.quantity) as total_quantity,
                   u.name as approved_by_name
            FROM borrow_requests br
            JOIN borrow_request_items bri ON br.id = bri.borrow_request_id
            JOIN store_items ii ON bri.item_id = ii.id
            LEFT JOIN users u ON br.approved_by = u.id
            WHERE br.user_id = ?
            GROUP BY br.id
            ORDER BY br.request_date DESC
        ");
        $stmt->execute([$user_id]);
        $my_requests = $stmt->fetchAll();    // Get request statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
        FROM borrow_requests 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $request_stats = $stmt->fetch();
    
    // Get available store items for borrowing
    $stmt = $pdo->prepare("
        SELECT ii.*, ic.name as category_name
        FROM store_items ii
        LEFT JOIN store_categories ic ON ii.category_id = ic.id
        WHERE ii.quantity_available > 0
        ORDER BY ii.name ASC
    ");
    $stmt->execute();
    $available_items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Student inventory error: " . $e->getMessage());
    $my_requests = [];
    $request_stats = ['total_requests' => 0, 'pending_requests' => 0, 'approved_requests' => 0, 'rejected_requests' => 0];
    $available_items = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management - <?php echo APP_NAME; ?></title>
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
                        <h1>📦 Store Management</h1>
                        <p>Request equipment and track your borrowing status</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="showModal('request-modal')">
                            ➕ Request to Borrow
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $request_stats['total_requests']; ?></h3>
                            <p>Total Requests</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3><?php echo $request_stats['pending_requests']; ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3><?php echo $request_stats['approved_requests']; ?></h3>
                            <p>Approved Requests</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">❌</div>
                        <div class="stat-info">
                            <h3><?php echo $request_stats['rejected_requests']; ?></h3>
                            <p>Rejected Requests</p>
                        </div>
                    </div>
                </div>

                <!-- Browse Available Items Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>📦 Browse Available Items</h2>
                        <div class="section-actions">
                            <select id="category-filter" class="form-control">
                                <option value="">All Categories</option>
                                <?php
                                $cat_stmt = $pdo->query("SELECT id, name FROM store_categories ORDER BY name");
                                while ($category = $cat_stmt->fetch()):
                                ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="search-group">
                            <input type="text" id="items-search" class="form-control" placeholder="Search items...">
                        </div>
                    </div>

                    <!-- DEBUG: Show available items count -->
                    <div style="background: #f8f9fa; padding: 10px; margin: 10px 0; border: 1px solid #dee2e6; border-radius: 5px;">
                        <strong>DEBUG:</strong> Found <?php echo count($available_items); ?> available items in database.
                        <?php if (count($available_items) > 0): ?>
                            <br>First item: <?php echo htmlspecialchars($available_items[0]['name']); ?> (Category: <?php echo htmlspecialchars($available_items[0]['category_name'] ?? 'None'); ?>)
                        <?php endif; ?>
                        <br><strong>If you see this debug info but no table below, there might be a JavaScript or CSS issue.</strong>
                        <br><strong>If you see "Found 0 available items", there's a database connection issue.</strong>
                    </div>

                    <div class="table-container">
                        <table class="table" id="items-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Available Quantity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($available_items)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">📭</div>
                                                <h3>No Items Available</h3>
                                                <p>There are currently no items available for borrowing.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($available_items as $item): ?>
                                        <tr data-category="<?php echo $item['category_id'] ?? ''; ?>">
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
                                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($item['category_name']): ?>
                                                    <span class="badge badge-info"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Uncategorized</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $desc = $item['description'] ?? '';
                                                echo $desc ? htmlspecialchars(substr($desc, 0, 100)) . (strlen($desc) > 100 ? '...' : '') : '-';
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-success"><?php echo $item['quantity_available']; ?> available</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="openRequestModalForItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $item['quantity_available']; ?>)">
                                                    Request to Borrow
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- My Requests Table -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>My Borrow Requests</h2>
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
                            <div class="search-group">
                                <input type="text" id="request-search" class="form-control" placeholder="Search requests...">
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table" id="requests-table">
                            <thead>
                                <tr>
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
                                <?php if (empty($my_requests)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">📦</div>
                                                <h3>No Requests Yet</h3>
                                                <p>You haven't made any borrow requests. Click "Request to Borrow" to get started.</p>
                                                <button class="btn btn-primary" onclick="showModal('request-modal')">Make Your First Request</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($my_requests as $request): ?>
                                        <tr data-status="<?php echo $request['status']; ?>">
                                            <td class="image-column">
                                                <?php
                                                $image_paths = explode(',', $request['image_paths']);
                                                $first_image = $image_paths[0] ?? '';
                                                ?>
                                                <?php if ($first_image): ?>
                                                    <div class="item-image-container">
                                                        <img src="../<?php echo htmlspecialchars($first_image); ?>"
                                                             alt="Items"
                                                             class="item-table-image clickable-image"
                                                             onclick="showImagePreview('../<?php echo htmlspecialchars($first_image); ?>', 'Request Items')"
                                                             onerror="this.parentElement.innerHTML='<span class=&quot;no-image&quot;>�</span>'">
                                                        <?php if (count($image_paths) > 1): ?>
                                                            <span class="badge badge-info" style="position: absolute; top: -5px; right: -5px;">+<?php echo count($image_paths) - 1; ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="no-image">�</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="item-info">
                                                    <strong><?php echo htmlspecialchars($request['item_names']); ?></strong>
                                                    <?php if ($request['item_descriptions']): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars(substr($request['item_descriptions'], 0, 100)) . (strlen($request['item_descriptions']) > 100 ? '...' : ''); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo $request['total_quantity']; ?></td>
                                            <td><?php echo htmlspecialchars($request['reason'] ?? '-'); ?></td>
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
                                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelRequest(<?php echo $request['id']; ?>)">
                                                            Cancel
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
            </div>
        </main>
    </div>

    <!-- Request Modal -->
    <div id="request-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Request to Borrow Equipment</h3>
                <button onclick="hideModal('request-modal')">&times;</button>
            </div>
            <form id="borrow-request-form">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <!-- Items Section -->
                    <div class="form-group">
                        <label class="form-label">Items to Borrow *</label>
                        <div id="items-container">
                            <div class="item-request-row" data-row-id="1">
                                <div class="row">
                                    <div class="col-6">
                                        <select name="items[1][item_id]" class="form-control form-select item-select" required>
                                            <option value="">Choose an item</option>
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
                                    <div class="col-4">
                                        <input type="number" name="items[1][quantity]" class="form-control item-quantity"
                                               min="1" max="1" required placeholder="Qty">
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-item-btn" style="display: none;">
                                            <i class="fa fa-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                <div class="item-details mt-2" style="display: none;">
                                    <div class="alert alert-info">
                                        <div class="row">
                                            <div class="col-8">
                                                <div class="item-info-text">
                                                    <strong>Description:</strong>
                                                    <p class="item-description"></p>
                                                    <strong>Available:</strong> <span class="available-quantity"></span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="item-image-preview" style="display: none;">
                                                    <img class="selected-item-image" src="" alt="Item image" style="max-width: 100px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add-item-btn" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="fa fa-plus"></i> Add Another Item
                        </button>
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

    <!-- Request Details Modal -->
    <div id="request-details-modal" class="modal" style="display: none;">
        <div class="modal-content">
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
