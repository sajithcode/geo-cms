<?php
require_once '../../php/config.php';
header('Content-Type: text/plain');

try {
    $stmt = $pdo->query("
        SELECT ir.*, l.name as lab_name, u.name as reporter_name
        FROM issue_reports ir
        LEFT JOIN labs l ON ir.lab_id = l.id
        LEFT JOIN users u ON ir.reported_by = u.id
        ORDER BY ir.reported_date DESC
        LIMIT 10
    ");
    
    $issues = $stmt->fetchAll();
    
    echo "Recent Issues in Database:\n";
    echo str_repeat("=", 80) . "\n\n";
    
    if (empty($issues)) {
        echo "No issues found in database.\n";
    } else {
        foreach ($issues as $issue) {
            echo "ID: " . $issue['id'] . "\n";
            echo "Report ID: " . ($issue['report_id'] ?? 'N/A') . "\n";
            echo "Computer: " . ($issue['computer_serial_no'] ?? 'N/A') . "\n";
            echo "Lab: " . ($issue['lab_name'] ?? 'N/A') . "\n";
            echo "Category: " . ($issue['issue_category'] ?? 'N/A') . "\n";
            echo "Description: " . substr($issue['description'], 0, 100) . "...\n";
            echo "Reporter: " . ($issue['reporter_name'] ?? 'N/A') . "\n";
            echo "Status: " . $issue['status'] . "\n";
            echo "Date: " . $issue['reported_date'] . "\n";
            echo str_repeat("-", 80) . "\n";
        }
        
        echo "\nTotal issues: " . count($issues) . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
