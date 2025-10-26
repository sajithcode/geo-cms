<?php
require_once '../php/config.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];

// Only allow students
if ($user_role !== 'student') {
    redirectTo('index.php');
}

// Get user's issue reports
try {
    $stmt = $pdo->prepare("
        SELECT ir.*, l.name as lab_name, u.name as assigned_to_name
        FROM issue_reports ir
        LEFT JOIN labs l ON ir.lab_id = l.id
        LEFT JOIN users u ON ir.assigned_to = u.id
        WHERE ir.reported_by = ?
        ORDER BY ir.reported_date DESC
    ");
    $stmt->execute([$user_id]);
    $my_reports = $stmt->fetchAll();
    
    // Get report statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reports,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_reports,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports
        FROM issue_reports 
        WHERE reported_by = ?
    ");
    $stmt->execute([$user_id]);
    $report_stats = $stmt->fetch();
    
    // Get available computers for dropdown
    $stmt = $pdo->prepare("
        SELECT c.*, l.name as lab_name
        FROM computers c
        LEFT JOIN labs l ON c.lab_id = l.id
        WHERE c.status = 'active'
        ORDER BY c.serial_no ASC
    ");
    $stmt->execute();
    $computers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Student issue error: " . $e->getMessage());
    $my_reports = [];
    $report_stats = ['total_reports' => 0, 'pending_reports' => 0, 'in_progress_reports' => 0, 'resolved_reports' => 0];
    $computers = [];
}

// Get labs for dropdown - separate try/catch to ensure it always runs
$labs = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM labs ORDER BY name ASC");
    $labs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching labs: " . $e->getMessage());
    $labs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Reporting - <?php echo APP_NAME; ?></title>
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
                        <h1>🚨 Issue Reporting</h1>
                        <p>Report technical issues with lab computers or equipment</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="showModal('report-issue-modal')">
                            ➕ Report Issue
                        </button>
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

                <!-- My Reports Table -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>My Issue Reports</h2>
                        <div class="section-actions">
                            <div class="filter-group">
                                <select id="status-filter" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                            <div class="search-group">
                                <input type="text" id="report-search" class="form-control" placeholder="Search reports...">
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table" id="reports-table">
                            <thead>
                                <tr>
                                    <th>Report ID</th>
                                    <th>Computer Serial No.</th>
                                    <th>Lab</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Reported Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($my_reports)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">🚨</div>
                                                <h3>No Reports Yet</h3>
                                                <p>You haven't reported any issues. Click "Report Issue" to submit your first report.</p>
                                                <button class="btn btn-primary" onclick="showModal('report-issue-modal')">Report Your First Issue</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($my_reports as $report): ?>
                                        <tr data-status="<?php echo $report['status']; ?>">
                                            <td><strong><?php echo htmlspecialchars($report['report_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($report['computer_serial_no']); ?></td>
                                            <td><?php echo htmlspecialchars($report['lab_name']); ?></td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo ucfirst($report['issue_category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo getIssueStatusBadgeClass($report['status']); ?>">
                                                    <?php echo getIssueStatusLabel($report['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($report['reported_date'], 'DD/MM/YYYY HH:mm'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewReportDetails(<?php echo $report['id']; ?>)">
                                                        View
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
            </div>
        </main>
    </div>

    <!-- Report Issue Modal -->
    <div id="report-issue-modal" class="modal" style="display: none;">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Report Technical Issue</h3>
                <button onclick="hideModal('report-issue-modal')">&times;</button>
            </div>
            <form id="report-issue-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="computer_serial_no" class="form-label">Computer Serial Number *</label>
                        <input type="text" id="computer_serial_no" name="computer_serial_no" 
                               class="form-control" required 
                               list="computers-list"
                               placeholder="e.g., LAB01-PC08">
                        <datalist id="computers-list">
                            <?php foreach ($computers as $computer): ?>
                                <option value="<?php echo htmlspecialchars($computer['serial_no']); ?>">
                                    <?php echo htmlspecialchars($computer['lab_name']); ?> - <?php echo htmlspecialchars($computer['computer_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                        <small class="form-text">Start typing to see available computers</small>
                    </div>

                    <div class="form-group">
                        <label for="lab_id" class="form-label">Lab *</label>
                        <select id="lab_id" name="lab_id" class="form-control form-select" required>
                            <option value="">Select Lab</option>
                            <?php if (empty($labs)): ?>
                                <option value="" disabled>No labs available - Please contact administrator</option>
                            <?php else: ?>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?php echo $lab['id']; ?>">
                                        <?php echo htmlspecialchars($lab['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($labs)): ?>
                            <small class="form-text text-danger">⚠️ No labs found in the system. Please contact the administrator to set up labs.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="issue_category" class="form-label">Issue Category *</label>
                        <select id="issue_category" name="issue_category" class="form-control form-select" required>
                            <option value="">Select Category</option>
                            <option value="hardware">🖥️ Hardware</option>
                            <option value="software">💾 Software</option>
                            <option value="network">🌐 Network</option>
                            <option value="other">📋 Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required
                                  placeholder="Please provide detailed information about the issue..."></textarea>
                        <small class="form-text">Be specific about the problem to help with faster resolution</small>
                    </div>

                    <div class="form-group">
                        <label for="file_upload" class="form-label">Upload Screenshot/Photo (Optional)</label>
                        <input type="file" id="file_upload" name="file_upload" class="form-control" 
                               accept="image/*,.pdf">
                        <small class="form-text">Accepted formats: Images (JPG, PNG) or PDF. Max size: 5MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideModal('report-issue-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Report</button>
                </div>
            </form>
        </div>
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

    <script src="../js/script.js"></script>
    <script>
        // Report issue form submission
        document.getElementById('report-issue-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Disable submit button
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
            
            try {
                const response = await fetch('php/submit_issue.php', {
                    method: 'POST',
                    body: formData
                });
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned invalid response');
                }
                
                const result = await response.json();
                console.log('Submit result:', result);
                
                if (result.success) {
                    // Show success notification
                    if (typeof showNotification === 'function') {
                        showNotification('Issue reported successfully! Report ID: ' + result.report_id, 'success');
                    } else {
                        alert('Issue reported successfully! Report ID: ' + result.report_id);
                    }
                    
                    // Close modal
                    if (typeof hideModal === 'function') {
                        hideModal('report-issue-modal');
                    }
                    
                    // Reset form
                    this.reset();
                    
                    // Reload page after short delay to show notification
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Submit Report';
                    
                    if (typeof showNotification === 'function') {
                        showNotification(result.message || 'Failed to submit report', 'error');
                    } else {
                        alert('Error: ' + (result.message || 'Failed to submit report'));
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                submitButton.disabled = false;
                submitButton.textContent = 'Submit Report';
                
                if (typeof showNotification === 'function') {
                    showNotification('An error occurred while submitting the report: ' + error.message, 'error');
                } else {
                    alert('An error occurred while submitting the report: ' + error.message);
                }
            }
        });

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

        // Filter and search functionality
        document.getElementById('status-filter').addEventListener('change', filterReports);
        document.getElementById('report-search').addEventListener('input', filterReports);

        function filterReports() {
            const statusFilter = document.getElementById('status-filter').value.toLowerCase();
            const searchTerm = document.getElementById('report-search').value.toLowerCase();
            const rows = document.querySelectorAll('#reports-table tbody tr');

            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                
                const status = row.dataset.status;
                const text = row.textContent.toLowerCase();
                
                const statusMatch = !statusFilter || status === statusFilter;
                const searchMatch = !searchTerm || text.includes(searchTerm);
                
                row.style.display = (statusMatch && searchMatch) ? '' : 'none';
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
?>
