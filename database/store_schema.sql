-- Store Management System Database Schema
-- Add this to your existing database.sql file or run separately

-- Create store categories table
CREATE TABLE IF NOT EXISTS `store_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create store items table
CREATE TABLE IF NOT EXISTS `store_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category_id` int(11) DEFAULT NULL,
  `quantity_total` int(11) NOT NULL DEFAULT 0,
  `quantity_available` int(11) NOT NULL DEFAULT 0,
  `quantity_borrowed` int(11) NOT NULL DEFAULT 0,
  `quantity_maintenance` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `status` (`status`),
  FOREIGN KEY (`category_id`) REFERENCES `store_categories` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create borrow requests table
CREATE TABLE IF NOT EXISTS `borrow_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `reason` text NOT NULL,
  `notes` text,
  `status` enum('pending','approved','rejected','cancelled','returned') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_date` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`),
  KEY `approved_by` (`approved_by`),
  KEY `status` (`status`),
  KEY `request_date` (`request_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `store_items` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create activity log table
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample store categories
INSERT INTO `store_categories` (`name`, `description`) VALUES
('Electronics', 'Electronic devices and equipment'),
('Laboratory Equipment', 'Lab instruments and tools'),
('Computers & Laptops', 'Computing devices'),
('Audio/Visual', 'Projectors, speakers, cameras'),
('Furniture', 'Tables, chairs, storage'),
('Books & Materials', 'Reference books and learning materials'),
('Sports Equipment', 'Sports and recreational equipment'),
('Tools & Hardware', 'Hand tools and hardware items')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- Insert sample store items
INSERT INTO `store_items` (`name`, `description`, `category_id`, `quantity_total`, `quantity_available`, `quantity_borrowed`, `quantity_maintenance`) VALUES
('HP EliteBook Laptop', 'HP EliteBook 840 G8 with i7 processor, 16GB RAM, 512GB SSD', 3, 10, 8, 2, 0),
('Digital Microscope', 'High-resolution digital microscope for laboratory use', 2, 5, 4, 1, 0),
('Epson Projector', 'Epson PowerLite 1781W WXGA 3LCD Projector', 4, 8, 6, 2, 0),
('Arduino Uno Kit', 'Arduino Uno R3 development board with sensors and components', 1, 15, 12, 3, 0),
('Whiteboard', 'Mobile whiteboard with markers and eraser', 5, 20, 18, 2, 0),
('Canon DSLR Camera', 'Canon EOS 2000D DSLR Camera with 18-55mm lens', 4, 3, 2, 1, 0),
('Scientific Calculator', 'Texas Instruments TI-84 Plus CE Graphing Calculator', 1, 25, 20, 5, 0),
('Lab Safety Goggles', 'Chemical splash safety goggles', 2, 30, 25, 3, 2),
('Portable Speaker', 'JBL Flip 5 Portable Bluetooth Speaker', 4, 6, 5, 1, 0),
('Study Table', 'Adjustable height study table', 5, 12, 10, 2, 0)
ON DUPLICATE KEY UPDATE 
  `description` = VALUES(`description`),
  `quantity_total` = VALUES(`quantity_total`),
  `quantity_available` = VALUES(`quantity_available`);

-- Insert sample borrow requests (for testing)
INSERT INTO `borrow_requests` (`user_id`, `item_id`, `quantity`, `expected_return_date`, `reason`, `status`) VALUES
(1, 1, 1, '2025-11-15', 'Need laptop for final year project development and presentation', 'pending'),
(2, 3, 1, '2025-11-10', 'Required for classroom presentation on geographic information systems', 'approved'),
(3, 4, 2, '2025-11-20', 'Building IoT project for computer science course assignment', 'pending'),
(1, 7, 1, '2025-11-05', 'Need graphing calculator for advanced mathematics exam preparation', 'approved'),
(4, 2, 1, '2025-11-25', 'Laboratory research on soil composition analysis', 'pending')
ON DUPLICATE KEY UPDATE `reason` = VALUES(`reason`);