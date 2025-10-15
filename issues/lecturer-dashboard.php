<?php
require_once '../php/config.php';

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'];

// Only allow lecturers
if ($user_role !== 'lecturer') {
    redirectTo('index.php');
}

// Get lecturer's issue reports
try {
    $stmt = $pdo->prepare("
        SELECT ir.*, l.name as lab_name, u.name as assigned_to_name,
               GROUP_CONCAT(iac.computer_serial_no SEPARATOR ', ') as affected_computers
        FROM issue_reports ir
        LEFT JOIN labs l ON ir.lab_id = l.id
        LEFT JOIN users u ON ir.assigned_to = u.id
        LEFT JOIN issue_affected_computers iac ON ir.id = iac.issue_id
        WHERE ir.reported_by = ?
        GROUP BY ir.id
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
        ORDER BY l.name, c.serial_no ASC
    ");
    $stmt->execute();
    $computers = $stmt->fetchAll();
    
    // Group computers by lab
    $computers_by_lab = [];
    foreach ($computers as $computer) {
        $lab_name = $computer['lab_name'];
        if (!isset($computers_by_lab[$lab_name])) {
            $computers_by_lab[$lab_name] = [];
        }
        $computers_by_lab[$lab_name][] = $computer;
    }
    
} catch (PDOException $e) {
    error_log("Lecturer issue error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $my_reports = [];
    $report_stats = ['total_reports' => 0, 'pending_reports' => 0, 'in_progress_reports' => 0, 'resolved_reports' => 0];
    $computers = [];
    $computers_by_lab = [];
}

// Get labs for dropdown - separate try/catch to ensure it always runs
$labs = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM labs ORDER BY name ASC");
    $labs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Check if labs were fetched
    if (empty($labs)) {
        error_log("WARNING: No labs found in database for lecturer issue reporting");
    } else {
        error_log("SUCCESS: Fetched " . count($labs) . " labs for lecturer issue reporting");
    }
} catch (PDOException $e) {
    error_log("ERROR fetching labs: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
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
                        <h1>🚨 Issue Reporting</h1>
                        <p>Report classroom or lab-level issues affecting teaching</p>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="showModal('report-issue-modal')">
                            ➕ Report Lab Issue
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
                                <input type="text" id="report-search" class="form-control" placeholder="Search reports...">
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table" id="reports-table">
                            <thead>
                                <tr>
                                    <th>Report ID</th>
                                    <th>Lab</th>
                                    <th>Issue Category</th>
                                    <th>Affected Computers</th>
                                    <th>Status</th>
                                    <th>Date</th>
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
                                                <p>You haven't reported any issues. Click "Report Lab Issue" to submit your first report.</p>
                                                <button class="btn btn-primary" onclick="showModal('report-issue-modal')">Report Your First Issue</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($my_reports as $report): ?>
                                        <tr data-status="<?php echo $report['status']; ?>" data-category="<?php echo $report['issue_category']; ?>">
                                            <td><strong><?php echo htmlspecialchars($report['report_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($report['lab_name']); ?></td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo getCategoryIcon($report['issue_category']); ?> <?php echo ucfirst($report['issue_category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($report['affected_computers']): ?>
                                                    <div class="affected-computers-preview" title="<?php echo htmlspecialchars($report['affected_computers']); ?>">
                                                        <?php 
                                                        $computers_array = explode(', ', $report['affected_computers']);
                                                        $count = count($computers_array);
                                                        if ($count <= 2) {
                                                            echo htmlspecialchars($report['affected_computers']);
                                                        } else {
                                                            echo htmlspecialchars($computers_array[0]) . ' and ' . ($count - 1) . ' more';
                                                        }
                                                        ?>
                                                    </div>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($report['computer_serial_no']); ?>
                                                <?php endif; ?>
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
                <h3>Report Lab Issue</h3>
                <button onclick="hideModal('report-issue-modal')">&times;</button>
            </div>
            <form id="report-issue-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
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
                        <label for="affected_computers" class="form-label">Affected Computer Serial Numbers (Multi-select)</label>
                        <div class="computer-selection-container" id="computer-selection-container">
                            <p class="text-muted">Please select a lab first</p>
                        </div>
                        <small class="form-text">Select multiple computers by holding Ctrl (Windows) or Cmd (Mac)</small>
                    </div>

                    <div class="form-group">
                        <label for="issue_category" class="form-label">Issue Category *</label>
                        <select id="issue_category" name="issue_category" class="form-control form-select" required>
                            <option value="">Select Category</option>
                            <option value="hardware">🖥️ Hardware</option>
                            <option value="software">💾 Software</option>
                            <option value="network">🌐 Network</option>
                            <option value="projector">📽️ Projector</option>
                            <option value="other">📋 Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Detailed Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required
                                  placeholder="Please provide detailed information about the issue and how it affects teaching or lab use..."></textarea>
                        <small class="form-text">Include specific details to help with faster resolution</small>
                    </div>

                    <div class="form-group">
                        <label for="file_upload" class="form-label">Upload Evidence (Optional)</label>
                        <input type="file" id="file_upload" name="file_upload" class="form-control" 
                               accept="image/*,.pdf">
                        <small class="form-text">Upload screenshots, photos, or documents. Max size: 5MB</small>
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
        // Computers by lab data
        const computersByLab = <?php echo json_encode($computers_by_lab); ?>;

        // Lab selection handler
        document.getElementById('lab_id').addEventListener('change', function() {
            const labName = this.options[this.selectedIndex].text;
            const container = document.getElementById('computer-selection-container');
            
            if (this.value && computersByLab[labName]) {
                const computers = computersByLab[labName];
                let html = '<div class="checkbox-group">';
                
                computers.forEach(computer => {
                    html += `
                        <label class="checkbox-label">
                            <input type="checkbox" name="affected_computers[]" value="${computer.serial_no}">
                            ${computer.serial_no} - ${computer.computer_name}
                        </label>
                    `;
                });
                
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-muted">Please select a lab first</p>';
            }
        });

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
        document.getElementById('category-filter').addEventListener('change', filterReports);
        document.getElementById('report-search').addEventListener('input', filterReports);

        function filterReports() {
            const statusFilter = document.getElementById('status-filter').value.toLowerCase();
            const categoryFilter = document.getElementById('category-filter').value.toLowerCase();
            const searchTerm = document.getElementById('report-search').value.toLowerCase();
            const rows = document.querySelectorAll('#reports-table tbody tr');

            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                
                const status = row.dataset.status;
                const category = row.dataset.category;
                const text = row.textContent.toLowerCase();
                
                const statusMatch = !statusFilter || status === statusFilter;
                const categoryMatch = !categoryFilter || category === categoryFilter;
                const searchMatch = !searchTerm || text.includes(searchTerm);
                
                row.style.display = (statusMatch && categoryMatch && searchMatch) ? '' : 'none';
            });
        }
    </script>
    <style>
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
            max-height: 200px;
            overflow-y: auto;
            padding: var(--space-3);
            background: var(--gray-50);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            cursor: pointer;
            padding: var(--space-2);
            border-radius: var(--radius);
            transition: background 0.2s ease;
        }

        .checkbox-label:hover {
            background: var(--gray-100);
        }

        .checkbox-label input[type="checkbox"] {
            cursor: pointer;
        }

        .affected-computers-preview {
            font-size: 0.875rem;
            color: var(--gray-700);
        }
    </style>
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
