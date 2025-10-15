<?php
require_once '../php/config.php';
requireLogin();

echo "<h2>Current Session Info</h2>";
echo "<pre>";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Name: " . $_SESSION['user_name'] . "\n";
echo "User Role: " . $_SESSION['role'] . "\n";
echo "</pre>";

echo "<h2>Issues for this user</h2>";
$stmt = $pdo->prepare("SELECT * FROM issue_reports WHERE reported_by = ?");
$stmt->execute([$_SESSION['user_id']]);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($issues);
echo "</pre>";
