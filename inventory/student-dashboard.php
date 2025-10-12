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
        SELECT br.*, ii.name as item_name, ii.description as item_description, ii.image_path,
               u.name as approved_by_name
        FROM borrow_requests br
        JOIN inventory_items ii ON br.item_id = ii.id
        LEFT JOIN users u ON br.approved_by = u.id
        WHERE br.user_id = ?
        ORDER BY br.request_date DESC
    ");
    $stmt->execute([$user_id]);
    $my_requests = $stmt->fetchAll();
    
    // Get request statistics
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
    
    // Get available inventory items for borrowing
    $stmt = $pdo->prepare("
        SELECT ii.*, ic.name as category_name
        FROM inventory_items ii
        LEFT JOIN inventory_categories ic ON ii.category_id = ic.id
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
    <title>Inventory Management - <?php echo APP_NAME; ?></title>
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
                        <h1>📦 Inventory Management</h1>
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
    <script src="../js/inventory.js"></script>
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
