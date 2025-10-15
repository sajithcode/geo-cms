<?php
require_once '../php/config.php';
requireLogin();

echo "<h1>Report Fetch Test</h1>";
echo "<pre>";

$user_id = $_SESSION['user_id'];
echo "Current User ID: " . $user_id . "\n\n";

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
    
    echo "Number of reports: " . count($my_reports) . "\n\n";
    echo "empty() check: " . (empty($my_reports) ? 'TRUE (empty)' : 'FALSE (not empty)') . "\n\n";
    
    if (!empty($my_reports)) {
        echo "First 3 reports:\n";
        for ($i = 0; $i < min(3, count($my_reports)); $i++) {
            print_r($my_reports[$i]);
            echo "\n";
        }
    } else {
        echo "NO REPORTS FOUND!\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
