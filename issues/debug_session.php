<?php
require_once '../php/config.php';

echo "<h1>Session Debug</h1>";
echo "<h2>Raw Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Session Status:</h2>";
echo "Logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "<h2>Database Check:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    echo "<h2>Issues for this user:</h2>";
    $stmt = $pdo->prepare("
        SELECT ir.*, l.name as lab_name
        FROM issue_reports ir
        LEFT JOIN labs l ON ir.lab_id = l.id
        WHERE ir.reported_by = ?
        ORDER BY ir.reported_date DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $issues = $stmt->fetchAll();
    echo "Found " . count($issues) . " issues<br>";
    echo "<pre>";
    print_r($issues);
    echo "</pre>";
}
?>
