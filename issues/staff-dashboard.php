<?php
require_once '../php/config.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];

// Only allow staff and admin
if (!in_array($user_role, ['staff', 'admin'])) {
    redirectTo('index.php');
}

// Get all issue reports with detailed information
try {
    $stmt = $pdo->prepare("
        SELECT ir.*, 
               l.name as lab_name,
               reporter.name as reporter_name,
               reporter.role as reporter_role,
               assigned.name as assigned_to_name,
               resolved.name as resolved_by_name,
               GROUP_CONCAT(iac.computer_serial_no SEPARATOR ', ') as affected_computers
        FROM issue_reports ir
        LEFT JOIN labs l ON ir.lab_id = l.id
        LEFT JOIN users reporter ON ir.reported_by = reporter.id
        LEFT JOIN users assigned ON ir.assigned_to = assigned.id
        LEFT JOIN users resolved ON ir.resolved_by = resolved.id
        LEFT JOIN issue_affected_computers iac ON ir.id = iac.issue_id
        GROUP BY ir.id
        ORDER BY 
            CASE ir.status 
                WHEN 'pending' THEN 1 
                WHEN 'in_progress' THEN 2 
                WHEN 'resolved' THEN 3 
            END,
            ir.reported_date DESC
    ");
    $stmt->execute();
    $all_reports = $stmt->fetchAll();
    
    // Get report statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_reports,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_reports,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports
        FROM issue_reports
    ");
    $report_stats = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Staff issue error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $all_reports = [];
    $report_stats = ['total_reports' => 0, 'pending_reports' => 0, 'in_progress_reports' => 0, 'resolved_reports' => 0];
}

// Get labs for dropdown - separate try/catch to ensure it always runs
$labs = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM labs ORDER BY name ASC");
    $labs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Check if labs were fetched
    if (empty($labs)) {
        error_log("WARNING: No labs found in database for staff issue management");
    } else {
        error_log("SUCCESS: Fetched " . count($labs) . " labs for staff issue management");
    }
} catch (PDOException $e) {
    error_log("ERROR fetching labs: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $labs = [];
}

// Get technicians for assignment - separate try/catch
$technicians = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role IN ('staff', 'admin') ORDER BY name ASC");
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("ERROR fetching technicians: " . $e->getMessage());
    $technicians = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Management - <?php echo APP_NAME; ?></title>
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
                        <h1>🚨 Issue Management</h1>
                        <p>Manage and resolve technical issues reported by students and lecturers</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3><?php echo $report_stats['total_reports']; ?></h3>
                            <p>Total Reports</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🟡</div>
                        <div class="stat-info">
                            <h3><?php echo $report_stats['pending_reports']; ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🟠</div>
                        <div class="stat-info">
                            <h3><?php echo $report_stats['in_progress_reports']; ?></h3>
                            <p>In Progress</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🟢</div>
                        <div class="stat-info">
                            <h3><?php echo $report_stats['resolved_reports']; ?></h3>
                            <p>Resolved</p>
                        </div>
                    </div>
                </div>

                <!-- All Reports Table -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>All Issue Reports</h2>
                        <div class="section-actions">
                            <div class="filter-group">
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <select id="lab-filter" class="form-control">
                                    <option value="">All Labs</option>
                                    <?php foreach ($labs as $lab): ?>
                                        <option value="<?php echo htmlspecialchars($lab['name']); ?>">
                                            <?php echo htmlspecialchars($lab['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <select id="category-filter" class="form-control">
                                    <option value="">All Categories</option>
                                    <option value="hardware">Hardware</option>
                                    <option value="software">Software</option>
                                    <option value="network">Network</option>
                                    <option value="projector">Projector</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="search-group">
                                <input type="text" id="report-search" class="form-control" placeholder="Search by report ID, computer, or reporter...">
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table" id="reports-table">
                            <thead>
                                <tr>
                                    <th>Report ID</th>
                                    <th>Computer/Lab</th>
                                    <th>Category</th>
                                    <th>Reporter</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_reports)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">🚨</div>
                                                <h3>No Reports Yet</h3>
                                                <p>There are no issue reports in the system.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_reports as $report): ?>
                                        <tr data-status="<?php echo $report['status']; ?>" 
                                            data-category="<?php echo $report['issue_category']; ?>"
                                            data-lab="<?php echo htmlspecialchars($report['lab_name']); ?>"
                                            data-report-id="<?php echo $report['id']; ?>">
                                            <td><strong><?php echo htmlspecialchars($report['report_id']); ?></strong></td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($report['lab_name']); ?></strong>
                                                    <?php if ($report['affected_computers']): ?>
                                                        <br><small class="text-muted"><?php 
                                                            $computers_array = explode(', ', $report['affected_computers']);
                                                            $count = count($computers_array);
                                                            if ($count <= 2) {
                                                                echo htmlspecialchars($report['affected_computers']);
                                                            } else {
                                                                echo htmlspecialchars($computers_array[0]) . ' +' . ($count - 1);
                                                            }
                                                        ?></small>
                                                    <?php elseif ($report['computer_serial_no']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($report['computer_serial_no']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo getCategoryIcon($report['issue_category']); ?> <?php echo ucfirst($report['issue_category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php echo htmlspecialchars($report['reporter_name']); ?>
                                                    <br><small class="text-muted"><?php echo ucfirst($report['reporter_role']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="description-preview" title="<?php echo htmlspecialchars($report['description']); ?>">
                                                    <?php echo htmlspecialchars(substr($report['description'], 0, 50)) . (strlen($report['description']) > 50 ? '...' : ''); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo getIssueStatusBadgeClass($report['status']); ?>">
                                                    <?php echo getIssueStatusLabel($report['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($report['assigned_to_name']): ?>
                                                    <small><?php echo htmlspecialchars($report['assigned_to_name']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($report['reported_date'], 'DD/MM/YYYY'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewReportDetails(<?php echo $report['id']; ?>)" title="View Details">
                                                        👁️
                                                    </button>
                                                    <?php if ($report['status'] !== 'resolved'): ?>
                                                        <button class="btn btn-sm btn-outline-info" onclick="assignTechnician(<?php echo $report['id']; ?>)" title="Assign Technician">
                                                            🧩
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success" onclick="markAsFixed(<?php echo $report['id']; ?>)" title="Mark as Fixed">
                                                            🔧
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning" onclick="addRemarks(<?php echo $report['id']; ?>)" title="Add Remarks">
                                                            📝
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($user_role === 'admin'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteReport(<?php echo $report['id']; ?>)" title="Delete Report">
                                                            🗑️
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

    <!-- Report Details Modal -->
    <div id="report-details-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Report Details</h3>
                <button onclick="hideModal('report-details-modal')">&times;</button>
            </div>
            <div class="modal-body" id="report-details-content">
                <!-- Content will be loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="hideModal('report-details-modal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Assign Technician Modal -->
    <div id="assign-technician-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Technician</h3>
                <button onclick="hideModal('assign-technician-modal')">&times;</button>
            </div>
            <form id="assign-technician-form">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" id="assign-report-id" name="report_id">
                    
                    <div class="form-group">
                        <label for="technician_id" class="form-label">Select Technician *</label>
                        <select id="technician_id" name="technician_id" class="form-control form-select" required>
                            <option value="">Choose a technician</option>
                            <?php foreach ($technicians as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>">
                                    <?php echo htmlspecialchars($tech['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('assign-technician-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Remarks Modal -->
    <div id="remarks-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Remarks</h3>
                <button onclick="hideModal('remarks-modal')">&times;</button>
            </div>
            <form id="remarks-form">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" id="remarks-report-id" name="report_id">
                    
                    <div class="form-group">
                        <label for="remarks" class="form-label">Remarks *</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="4" required
                                  placeholder="Add notes or updates about this issue..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('remarks-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Remarks</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
        // View report details
        async function viewReportDetails(reportId) {
            try {
                const response = await fetch(`php/get_issue_details.php?id=${reportId}`);
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('report-details-content').innerHTML = result.html;
                    showModal('report-details-modal');
                } else {
                    showNotification(result.message || 'Failed to load report details', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while loading report details', 'error');
            }
        }

        // Assign technician
        function assignTechnician(reportId) {
            document.getElementById('assign-report-id').value = reportId;
            showModal('assign-technician-modal');
        }

        document.getElementById('assign-technician-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('php/assign_technician.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Technician assigned successfully!', 'success');
                    hideModal('assign-technician-modal');
                    location.reload();
                } else {
                    showNotification(result.message || 'Failed to assign technician', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while assigning technician', 'error');
            }
        });

        // Mark as fixed
        async function markAsFixed(reportId) {
            if (!confirm('Are you sure you want to mark this issue as resolved?')) return;
            
            try {
                const response = await fetch('php/mark_as_fixed.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        report_id: reportId,
                        csrf_token: document.querySelector('meta[name="csrf-token"]').content
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Issue marked as resolved!', 'success');
                    location.reload();
                } else {
                    showNotification(result.message || 'Failed to mark as fixed', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            }
        }

        // Add remarks
        function addRemarks(reportId) {
            document.getElementById('remarks-report-id').value = reportId;
            showModal('remarks-modal');
        }

        document.getElementById('remarks-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('php/add_remarks.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Remarks added successfully!', 'success');
                    hideModal('remarks-modal');
                    this.reset();
                    location.reload();
                } else {
                    showNotification(result.message || 'Failed to add remarks', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while adding remarks', 'error');
            }
        });

        // Delete report
        async function deleteReport(reportId) {
            if (!confirm('Are you sure you want to delete this report? This action cannot be undone.')) return;
            
            try {
                const response = await fetch('php/delete_issue.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        report_id: reportId,
                        csrf_token: document.querySelector('meta[name="csrf-token"]').content
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Report deleted successfully!', 'success');
                    location.reload();
                } else {
                    showNotification(result.message || 'Failed to delete report', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            }
        }

        // Filter and search functionality
        document.getElementById('status-filter').addEventListener('change', filterReports);
        document.getElementById('lab-filter').addEventListener('change', filterReports);
        document.getElementById('category-filter').addEventListener('change', filterReports);
        document.getElementById('report-search').addEventListener('input', filterReports);

        function filterReports() {
            const statusFilter = document.getElementById('status-filter').value.toLowerCase();
            const labFilter = document.getElementById('lab-filter').value.toLowerCase();
            const categoryFilter = document.getElementById('category-filter').value.toLowerCase();
            const searchTerm = document.getElementById('report-search').value.toLowerCase();
            const rows = document.querySelectorAll('#reports-table tbody tr');

            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                
                const status = row.dataset.status;
                const lab = row.dataset.lab.toLowerCase();
                const category = row.dataset.category;
                const text = row.textContent.toLowerCase();
                
                const statusMatch = !statusFilter || status === statusFilter;
                const labMatch = !labFilter || lab === labFilter;
                const categoryMatch = !categoryFilter || category === categoryFilter;
                const searchMatch = !searchTerm || text.includes(searchTerm);
                
                row.style.display = (statusMatch && labMatch && categoryMatch && searchMatch) ? '' : 'none';
            });
        }
    </script>
</body>
</html>

<?php
function getIssueStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'in_progress': return 'info';
        case 'resolved': return 'success';
        default: return 'secondary';
    }
}

function getIssueStatusLabel($status) {
    switch ($status) {
        case 'pending': return '🟡 Pending';
        case 'in_progress': return '🟠 In Progress';
        case 'resolved': return '🟢 Resolved';
        default: return ucfirst($status);
    }
}

function getCategoryIcon($category) {
    switch ($category) {
        case 'hardware': return '🖥️';
        case 'software': return '💾';
        case 'network': return '🌐';
        case 'projector': return '📽️';
        case 'other': return '📋';
        default: return '📋';
    }
}
?>
