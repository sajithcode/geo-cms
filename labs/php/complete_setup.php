<?php
require_once '../../php/config.php';

try {
    // Check if lab_issues table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'lab_issues'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "Creating lab_issues table...\n";
        
        $createIssuesTable = "
        CREATE TABLE `lab_issues` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `lab_id` int(11) NOT NULL,
            `title` varchar(255) NOT NULL,
            `description` text NOT NULL,
            `issue_type` enum('maintenance', 'equipment_fault', 'safety_concern', 'facility_issue', 'other') NOT NULL,
            `priority` enum('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            `status` enum('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            `reported_by` int(11) NOT NULL,
            `assigned_to` int(11) DEFAULT NULL,
            `resolved_by` int(11) DEFAULT NULL,
            `resolution_notes` text,
            `estimated_fix_time` datetime DEFAULT NULL,
            `actual_fix_time` datetime DEFAULT NULL,
            `reported_date` timestamp DEFAULT CURRENT_TIMESTAMP,
            `resolved_date` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `fk_lab_issues_lab` (`lab_id`),
            KEY `fk_lab_issues_reported_by` (`reported_by`),
            KEY `fk_lab_issues_assigned_to` (`assigned_to`),
            KEY `fk_lab_issues_resolved_by` (`resolved_by`),
            CONSTRAINT `fk_lab_issues_lab` FOREIGN KEY (`lab_id`) REFERENCES `labs` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_lab_issues_reported_by` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_lab_issues_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_lab_issues_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($createIssuesTable);
        echo "✓ lab_issues table created successfully\n";
    } else {
        echo "✓ lab_issues table already exists\n";
    }
    
    // Check if lab_timetables table has sample data
    $stmt = $pdo->query("SELECT COUNT(*) FROM lab_timetables");
    $timetableCount = $stmt->fetchColumn();
    
    if ($timetableCount == 0) {
        echo "Adding sample timetable data...\n";
        
        $insertTimetables = "
        INSERT INTO `lab_timetables` (`lab_id`, `day_of_week`, `start_time`, `end_time`, `subject`, `semester`, `batch`) VALUES
        (1, 'monday', '09:00:00', '11:00:00', 'Programming Fundamentals (CS101)', 'Semester 1', '2024-2025'),
        (1, 'wednesday', '14:00:00', '16:00:00', 'Database Systems (CS201)', 'Semester 2', '2024-2025'),
        (2, 'tuesday', '10:00:00', '12:00:00', 'Circuit Analysis (EE101)', 'Semester 1', '2024-2025'),
        (2, 'thursday', '13:00:00', '15:00:00', 'Digital Electronics (EE201)', 'Semester 2', '2024-2025'),
        (3, 'monday', '14:00:00', '17:00:00', 'Analytical Chemistry (CH201)', 'Semester 2', '2024-2025'),
        (3, 'friday', '09:00:00', '12:00:00', 'Organic Chemistry Lab (CH301)', 'Semester 3', '2024-2025')";
        
        $pdo->exec($insertTimetables);
        echo "✓ Sample timetable data added successfully\n";
    } else {
        echo "✓ Timetable data already exists ($timetableCount records)\n";
    }
    
    // Final verification
    echo "\nFinal verification:\n";
    $tables = ['labs', 'lab_reservations', 'lab_timetables', 'lab_issues'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "✓ Table '$table': $count records\n";
    }
    
    echo "\n🎉 Labs management system is fully ready!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>