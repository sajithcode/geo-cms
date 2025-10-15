<?php
require_once '../../php/config.php';
header('Content-Type: application/json');

// Require user to be logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

try {
    $report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$report_id) {
        throw new Exception('Invalid report ID');
    }
    
    // Get report details
    $stmt = $pdo->prepare("
        SELECT ir.*, 
               l.name as lab_name,
               reporter.name as reporter_name,
               reporter.role as reporter_role,
               reporter.email as reporter_email,
               assigned.name as assigned_to_name,
               resolved.name as resolved_by_name,
               GROUP_CONCAT(iac.computer_serial_no SEPARATOR ', ') as affected_computers
        FROM issue_reports ir
        LEFT JOIN labs l ON ir.lab_id = l.id
        LEFT JOIN users reporter ON ir.reported_by = reporter.id
        LEFT JOIN users assigned ON ir.assigned_to = assigned.id
        LEFT JOIN users resolved ON ir.resolved_by = resolved.id
        LEFT JOIN issue_affected_computers iac ON ir.id = iac.issue_id
        WHERE ir.id = ?
        GROUP BY ir.id
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    if (!$report) {
        throw new Exception('Report not found');
    }
    
    // Check permissions
    if ($user_role === 'student' && $report['reported_by'] != $user_id) {
        throw new Exception('Access denied');
    }
    
    // Get issue history
    $stmt = $pdo->prepare("
        SELECT ih.*, u.name as performed_by_name
        FROM issue_history ih
        LEFT JOIN users u ON ih.performed_by = u.id
        WHERE ih.issue_id = ?
        ORDER BY ih.action_date DESC
    ");
    $stmt->execute([$report_id]);
    $history = $stmt->fetchAll();
    
    // Build HTML content
    $html = '
    <div class="report-details">
        <div class="detail-section">
            <h4>📋 Report Information</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Report ID:</label>
                    <span><strong>' . htmlspecialchars($report['report_id']) . '</strong></span>
                </div>
                <div class="detail-item">
                    <label>Status:</label>
                    <span>' . getIssueStatusBadge($report['status']) . '</span>
                </div>
                <div class="detail-item">
                    <label>Category:</label>
                    <span>' . getCategoryBadge($report['issue_category']) . '</span>
                </div>
                <div class="detail-item">
                    <label>Lab:</label>
                    <span>' . htmlspecialchars($report['lab_name']) . '</span>
                </div>';
    
    if ($report['computer_serial_no']) {
        $html .= '
                <div class="detail-item">
                    <label>Computer Serial No.:</label>
                    <span>' . htmlspecialchars($report['computer_serial_no']) . '</span>
                </div>';
    }
    
    if ($report['affected_computers']) {
        $html .= '
                <div class="detail-item full-width">
                    <label>Affected Computers:</label>
                    <span>' . htmlspecialchars($report['affected_computers']) . '</span>
                </div>';
    }
    
    $html .= '
            </div>
        </div>
        
        <div class="detail-section">
            <h4>📝 Description</h4>
            <div class="description-content">
                ' . nl2br(htmlspecialchars($report['description'])) . '
            </div>
        </div>';
    
    if ($report['file_path']) {
        $file_ext = strtolower(pathinfo($report['file_path'], PATHINFO_EXTENSION));
        $html .= '
        <div class="detail-section">
            <h4>📎 Attachment</h4>';
        
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $html .= '
            <div class="attachment-preview">
                <img src="../../' . htmlspecialchars($report['file_path']) . '" alt="Attachment" class="attachment-image">
            </div>';
        } else {
            $html .= '
            <div class="attachment-link">
                <a href="../../' . htmlspecialchars($report['file_path']) . '" target="_blank" class="btn btn-outline-primary">
                    📄 View Attachment
                </a>
            </div>';
        }
        
        $html .= '
        </div>';
    }
    
    $html .= '
        <div class="detail-section">
            <h4>👤 Reporter Information</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Name:</label>
                    <span>' . htmlspecialchars($report['reporter_name']) . '</span>
                </div>
                <div class="detail-item">
                    <label>Role:</label>
                    <span>' . ucfirst($report['reporter_role']) . '</span>
                </div>
                <div class="detail-item">
                    <label>Email:</label>
                    <span>' . htmlspecialchars($report['reporter_email']) . '</span>
                </div>
                <div class="detail-item">
                    <label>Reported Date:</label>
                    <span>' . formatDate($report['reported_date'], 'DD/MM/YYYY HH:mm') . '</span>
                </div>
            </div>
        </div>';
    
    if ($report['assigned_to_name'] || $report['resolved_by_name']) {
        $html .= '
        <div class="detail-section">
            <h4>🔧 Assignment Information</h4>
            <div class="detail-grid">';
        
        if ($report['assigned_to_name']) {
            $html .= '
                <div class="detail-item">
                    <label>Assigned To:</label>
                    <span>' . htmlspecialchars($report['assigned_to_name']) . '</span>
                </div>';
        }
        
        if ($report['resolved_by_name']) {
            $html .= '
                <div class="detail-item">
                    <label>Resolved By:</label>
                    <span>' . htmlspecialchars($report['resolved_by_name']) . '</span>
                </div>
                <div class="detail-item">
                    <label>Resolved Date:</label>
                    <span>' . formatDate($report['resolved_date'], 'DD/MM/YYYY HH:mm') . '</span>
                </div>';
        }
        
        $html .= '
            </div>
        </div>';
    }
    
    if ($report['remarks']) {
        $html .= '
        <div class="detail-section">
            <h4>💬 Remarks</h4>
            <div class="remarks-content">
                ' . nl2br(htmlspecialchars($report['remarks'])) . '
            </div>
        </div>';
    }
    
    if (!empty($history)) {
        $html .= '
        <div class="detail-section">
            <h4>📜 History</h4>
            <div class="history-timeline">';
        
        foreach ($history as $entry) {
            $html .= '
                <div class="history-item">
                    <div class="history-icon">●</div>
                    <div class="history-content">
                        <strong>' . htmlspecialchars($entry['action']) . '</strong>
                        ' . ($entry['description'] ? '<br><span class="text-muted">' . htmlspecialchars($entry['description']) . '</span>' : '') . '
                        <br><small class="text-muted">By ' . htmlspecialchars($entry['performed_by_name']) . ' on ' . formatDate($entry['action_date'], 'DD/MM/YYYY HH:mm') . '</small>
                    </div>
                </div>';
        }
        
        $html .= '
            </div>
        </div>';
    }
    
    $html .= '
    </div>
    
    <style>
        .report-details {
            display: flex;
            flex-direction: column;
            gap: var(--space-6);
        }
        
        .detail-section {
            background: var(--gray-50);
            padding: var(--space-4);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
        }
        
        .detail-section h4 {
            margin: 0 0 var(--space-4) 0;
            color: var(--gray-900);
            font-size: 1.1rem;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-3);
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: var(--space-1);
        }
        
        .detail-item.full-width {
            grid-column: 1 / -1;
        }
        
        .detail-item label {
            font-weight: 600;
            color: var(--gray-600);
            font-size: 0.875rem;
        }
        
        .detail-item span {
            color: var(--gray-900);
        }
        
        .description-content,
        .remarks-content {
            background: var(--white);
            padding: var(--space-4);
            border-radius: var(--radius);
            color: var(--gray-900);
            line-height: 1.6;
        }
        
        .attachment-preview {
            text-align: center;
        }
        
        .attachment-image {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
        }
        
        .history-timeline {
            display: flex;
            flex-direction: column;
            gap: var(--space-3);
        }
        
        .history-item {
            display: flex;
            gap: var(--space-3);
            background: var(--white);
            padding: var(--space-3);
            border-radius: var(--radius);
        }
        
        .history-icon {
            color: var(--primary);
            font-size: 1.5rem;
            line-height: 1;
        }
        
        .history-content {
            flex: 1;
        }
    </style>';
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    error_log("Get issue details error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getIssueStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">🟡 Pending</span>',
        'in_progress' => '<span class="badge badge-info">🟠 In Progress</span>',
        'resolved' => '<span class="badge badge-success">🟢 Resolved</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

function getCategoryBadge($category) {
    $icons = [
        'hardware' => '🖥️',
        'software' => '💾',
        'network' => '🌐',
        'projector' => '📽️',
        'other' => '📋'
    ];
    $icon = $icons[$category] ?? '📋';
    return '<span class="badge badge-secondary">' . $icon . ' ' . ucfirst($category) . '</span>';
}
?>
