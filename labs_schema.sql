-- Labs Management System Schema
-- This schema supports the labs management functionality

-- Labs table - Stores information about each lab
CREATE TABLE `labs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `code` varchar(20) NOT NULL UNIQUE,
    `description` text,
    `capacity` int(11) DEFAULT 30,
    `location` varchar(255),
    `status` enum('available', 'in_use', 'maintenance', 'offline') DEFAULT 'available',
    `equipment_list` text,
    `safety_guidelines` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lab Reservations table - Stores lab booking requests
CREATE TABLE `lab_reservations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lab_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `purpose` text NOT NULL,
    `reservation_date` date NOT NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `expected_attendees` int(11) DEFAULT 1,
    `special_requirements` text,
    `status` enum('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
    `approved_by` int(11) DEFAULT NULL,
    `approval_date` timestamp NULL DEFAULT NULL,
    `approval_notes` text,
    `rejection_reason` text,
    `request_date` timestamp DEFAULT CURRENT_TIMESTAMP,
    `created_by` int(11) NOT NULL,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_lab_reservations_lab` (`lab_id`),
    KEY `fk_lab_reservations_user` (`user_id`),
    KEY `fk_lab_reservations_approved_by` (`approved_by`),
    KEY `fk_lab_reservations_created_by` (`created_by`),
    CONSTRAINT `fk_lab_reservations_lab` FOREIGN KEY (`lab_id`) REFERENCES `labs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lab_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lab_reservations_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_lab_reservations_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lab Timetables table - Stores scheduled classes/sessions
CREATE TABLE `lab_timetables` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lab_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text,
    `instructor_id` int(11),
    `day_of_week` enum('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `subject_code` varchar(20),
    `semester` varchar(20),
    `academic_year` varchar(20),
    `is_active` boolean DEFAULT true,
    `created_by` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_lab_timetables_lab` (`lab_id`),
    KEY `fk_lab_timetables_instructor` (`instructor_id`),
    KEY `fk_lab_timetables_created_by` (`created_by`),
    CONSTRAINT `fk_lab_timetables_lab` FOREIGN KEY (`lab_id`) REFERENCES `labs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lab_timetables_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_lab_timetables_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lab Issues table - Stores maintenance and issue reports
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample labs data
INSERT INTO `labs` (`name`, `code`, `description`, `capacity`, `location`, `status`, `equipment_list`, `safety_guidelines`) VALUES
('Computer Lab 01', 'LAB01', 'Main computer laboratory with 30 workstations for programming and software development courses.', 30, 'Building A, Ground Floor', 'available', 'HP Desktop PCs (30), Projector, Whiteboard, Air Conditioning, Network Switch, UPS System', 'No food or drinks allowed. Handle equipment with care. Report any malfunctions immediately. Emergency exits clearly marked.'),
('Physics Lab 02', 'LAB02', 'Physics laboratory equipped for experimental physics and research activities.', 25, 'Building B, First Floor', 'available', 'Oscilloscopes, Function Generators, Multimeters, Power Supplies, Component Kits, Breadboards', 'Wear safety goggles when required. Handle electrical equipment carefully. Ground yourself before touching sensitive components. No unauthorized experiments.'),
('Chemistry Lab 03', 'LAB03', 'Fully equipped chemistry laboratory for analytical and organic chemistry experiments.', 20, 'Building C, Second Floor', 'available', 'Fume Hoods, Analytical Balances, pH Meters, Spectrophotometers, Glassware Sets, Chemical Storage', 'Wear lab coats and safety goggles at all times. Proper disposal of chemicals required. No eating or drinking. Emergency shower and eyewash stations available.'),
('Engineering Lab 04', 'LAB04', 'Multidisciplinary engineering laboratory for mechanical and electrical projects.', 25, 'Building D, Ground Floor', 'maintenance', '3D Printers, CNC Machine, Soldering Stations, Power Tools, Measurement Equipment, CAD Workstations', 'Safety training required before using power tools. Proper PPE must be worn. No loose clothing near machinery. First aid kit available.');

-- Insert sample timetable data
INSERT INTO `lab_timetables` (`lab_id`, `title`, `description`, `day_of_week`, `start_time`, `end_time`, `subject_code`, `semester`, `academic_year`, `created_by`) VALUES
(1, 'Programming Fundamentals', 'Introduction to programming concepts using Python', 'Monday', '09:00:00', '11:00:00', 'CS101', 'Semester 1', '2024-2025', 1),
(1, 'Database Systems', 'Database design and SQL programming practical', 'Wednesday', '14:00:00', '16:00:00', 'CS201', 'Semester 2', '2024-2025', 1),
(2, 'Circuit Analysis', 'Basic electrical circuit analysis and measurements', 'Tuesday', '10:00:00', '12:00:00', 'EE101', 'Semester 1', '2024-2025', 1),
(2, 'Digital Electronics', 'Digital logic circuits and microprocessor programming', 'Thursday', '13:00:00', '15:00:00', 'EE201', 'Semester 2', '2024-2025', 1),
(3, 'Analytical Chemistry', 'Quantitative analysis techniques and instrumentation', 'Monday', '14:00:00', '17:00:00', 'CH201', 'Semester 2', '2024-2025', 1),
(3, 'Organic Chemistry Lab', 'Synthesis and characterization of organic compounds', 'Friday', '09:00:00', '12:00:00', 'CH301', 'Semester 3', '2024-2025', 1);