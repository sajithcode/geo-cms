-- =====================================================
-- Geo CMS Database - Complete Setup
-- Faculty of Geomatics - Sabaragamuwa University of Sri Lanka
-- Version: Final Complete
-- Date: October 26, 2025
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS geo_cms;
USE geo_cms;

-- =====================================================
-- USERS SYSTEM
-- =====================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    user_id VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'lecturer', 'staff', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(255) NULL
);

-- =====================================================
-- STORE MANAGEMENT SYSTEM
-- =====================================================

-- Store categories
CREATE TABLE IF NOT EXISTS store_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Store items
CREATE TABLE IF NOT EXISTS store_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    quantity_total INT NOT NULL DEFAULT 0,
    quantity_available INT NOT NULL DEFAULT 0,
    quantity_borrowed INT NOT NULL DEFAULT 0,
    quantity_maintenance INT NOT NULL DEFAULT 0,
    status ENUM('active','inactive','archived') DEFAULT 'active',
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    updated_by INT NULL,
    FOREIGN KEY (category_id) REFERENCES store_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Borrow requests
CREATE TABLE IF NOT EXISTS borrow_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    reason TEXT NOT NULL,
    expected_return_date DATE NULL,
    actual_return_date DATE NULL,
    borrow_start_date DATE NULL,
    borrow_end_date DATE NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','approved','rejected','cancelled','returned') DEFAULT 'pending',
    approved_date TIMESTAMP NULL,
    approved_by INT NULL,
    returned_date TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_items(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- LABS MANAGEMENT SYSTEM
-- =====================================================

-- Labs
CREATE TABLE IF NOT EXISTS labs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    capacity INT DEFAULT 30,
    status ENUM('available', 'in_use', 'maintenance') DEFAULT 'available',
    location VARCHAR(100) NULL,
    code VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lab reservations
CREATE TABLE IF NOT EXISTS lab_reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lab_id INT NOT NULL,
    user_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    approved_by INT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_date TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Lab timetables
CREATE TABLE IF NOT EXISTS lab_timetables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lab_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    lecturer_id INT,
    subject VARCHAR(100),
    semester VARCHAR(20),
    batch VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- ISSUE REPORTING SYSTEM
-- =====================================================

-- Issue reports (updated schema)
CREATE TABLE IF NOT EXISTS issue_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id VARCHAR(50) UNIQUE NULL,
    user_id INT NOT NULL,
    lab_id INT NULL,
    computer_number VARCHAR(10) NULL,
    computer_serial_no VARCHAR(100) NULL,
    issue_category ENUM('hardware', 'software', 'network', 'projector', 'other') DEFAULT 'other',
    description TEXT NOT NULL,
    file_path VARCHAR(255) NULL,
    screenshot VARCHAR(255) NULL,
    status ENUM('pending', 'in_progress', 'resolved') DEFAULT 'pending',
    reported_by INT NOT NULL,
    assigned_to INT NULL,
    resolved_by INT NULL,
    remarks TEXT NULL,
    reported_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_date DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Issue affected computers (for multiple computer selection)
CREATE TABLE IF NOT EXISTS issue_affected_computers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    issue_id INT NOT NULL,
    computer_serial_no VARCHAR(100) NOT NULL,
    FOREIGN KEY (issue_id) REFERENCES issue_reports(id) ON DELETE CASCADE
);

-- Issue history (for tracking status changes)
CREATE TABLE IF NOT EXISTS issue_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    issue_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    performed_by INT NOT NULL,
    action_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issue_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Computers table (for lab computers)
CREATE TABLE IF NOT EXISTS computers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    serial_no VARCHAR(100) UNIQUE NOT NULL,
    lab_id INT NOT NULL,
    computer_name VARCHAR(100),
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE
);

-- =====================================================
-- SYSTEM TABLES
-- =====================================================

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System logs
CREATE TABLE IF NOT EXISTS system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Activity log
CREATE TABLE IF NOT EXISTS activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Store system indexes
CREATE INDEX IF NOT EXISTS idx_store_items_category ON store_items(category_id);
CREATE INDEX IF NOT EXISTS idx_store_items_status ON store_items(status);
CREATE INDEX IF NOT EXISTS idx_borrow_requests_user ON borrow_requests(user_id);
CREATE INDEX IF NOT EXISTS idx_borrow_requests_item ON borrow_requests(item_id);
CREATE INDEX IF NOT EXISTS idx_borrow_requests_status ON borrow_requests(status);
CREATE INDEX IF NOT EXISTS idx_borrow_requests_date ON borrow_requests(request_date);

-- Labs system indexes
CREATE INDEX IF NOT EXISTS idx_lab_reservations_date ON lab_reservations(reservation_date);
CREATE INDEX IF NOT EXISTS idx_lab_reservations_status ON lab_reservations(status);
CREATE INDEX IF NOT EXISTS idx_lab_reservations_user ON lab_reservations(user_id);
CREATE INDEX IF NOT EXISTS idx_lab_timetables_lab ON lab_timetables(lab_id);
CREATE INDEX IF NOT EXISTS idx_lab_timetables_lecturer ON lab_timetables(lecturer_id);

-- Issues system indexes
CREATE INDEX IF NOT EXISTS idx_issue_reports_status ON issue_reports(status);
CREATE INDEX IF NOT EXISTS idx_issue_reports_lab ON issue_reports(lab_id);
CREATE INDEX IF NOT EXISTS idx_issue_reports_reported_by ON issue_reports(reported_by);
CREATE INDEX IF NOT EXISTS idx_issue_reports_report_id ON issue_reports(report_id);
CREATE INDEX IF NOT EXISTS idx_issue_reports_computer_serial ON issue_reports(computer_serial_no);
CREATE INDEX IF NOT EXISTS idx_computers_lab ON computers(lab_id);
CREATE INDEX IF NOT EXISTS idx_computers_serial ON computers(serial_no);

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================


-- Insert store categories
INSERT INTO store_categories (name, description) VALUES
('Computers', 'Desktop computers and workstations'),
('Surveying Equipment', 'Total stations, GPS devices, levels'),
('Software', 'Licensed software and applications'),
('Accessories', 'Cables, adapters, and other accessories'),
('Electronics', 'Electronic devices and equipment'),
('Laboratory Equipment', 'Lab instruments and tools'),
('Audio/Visual', 'Projectors, speakers, cameras'),
('Furniture', 'Tables, chairs, storage'),
('Books & Materials', 'Reference books and learning materials'),
('Sports Equipment', 'Sports and recreational equipment')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert store items (original + lab computers)
INSERT INTO store_items (name, category_id, description, quantity_total, quantity_available) VALUES
-- Original items
('Desktop Computer', 1, 'HP EliteDesk 800 G6 workstation', 50, 45),
('Total Station', 2, 'Leica TS16 Total Station', 5, 4),
('GPS Device', 2, 'Trimble R10 GNSS Receiver', 8, 6),
('ArcGIS License', 3, 'ArcGIS Desktop Professional License', 30, 25),
('USB Cable', 4, 'USB-A to USB-C Cable', 20, 18),

-- Lab computers (added later)
('Remote Sensing Computer Lab', 1, 'Computers for Remote Sensing laboratory work', 12, 12),
('GIS Lab Computer', 1, 'Computers for Geographic Information Systems laboratory', 28, 28),
('Photogrammetry Computer Lab', 1, 'Computers for Photogrammetry and image processing work', 5, 5),
('Lab 1 Computer', 1, 'General purpose computers for Lab 1', 50, 50),
('Hydrography Computer Lab', 1, 'Computers for Hydrography and water mapping studies', 8, 8),
('Main Computer Lab', 1, 'Main computer laboratory computers', 100, 100),

-- Additional sample items
('HP EliteBook Laptop', 1, 'HP EliteBook 840 G8 with i7 processor, 16GB RAM, 512GB SSD', 10, 8),
('Digital Microscope', 6, 'High-resolution digital microscope for laboratory use', 5, 4),
('Epson Projector', 7, 'Epson PowerLite 1781W WXGA 3LCD Projector', 8, 6),
('Arduino Uno Kit', 5, 'Arduino Uno R3 development board with sensors and components', 15, 12),
('Whiteboard', 8, 'Mobile whiteboard with markers and eraser', 20, 18),
('Canon DSLR Camera', 7, 'Canon EOS 2000D DSLR Camera with 18-55mm lens', 3, 2),
('Scientific Calculator', 5, 'Texas Instruments TI-84 Plus CE Graphing Calculator', 25, 20),
('Lab Safety Goggles', 6, 'Chemical splash safety goggles', 30, 25),
('Portable Speaker', 7, 'JBL Flip 5 Portable Bluetooth Speaker', 6, 5),
('Study Table', 8, 'Adjustable height study table', 12, 10)
ON DUPLICATE KEY UPDATE
  description = VALUES(description),
  quantity_total = VALUES(quantity_total),
  quantity_available = VALUES(quantity_available);

-- Insert labs
INSERT INTO labs (name, description, capacity, status) VALUES
('Lab 01', 'GIS Software Lab - Equipped with specialized GIS software and high-performance computers', 30, 'available'),
('Lab 02', 'Programming Lab - Modern development environment with latest programming tools', 25, 'available'),
('Lab 03', 'Surveying Software Lab - Advanced surveying and mapping software workstations', 30, 'available'),
('Lab 04', 'Research Lab - General research and academic computing facility', 20, 'available')
ON DUPLICATE KEY UPDATE
  description = VALUES(description),
  capacity = VALUES(capacity),
  status = VALUES(status);

-- Insert computers for labs
INSERT INTO computers (serial_no, lab_id, computer_name, status) VALUES
-- Lab 01 computers
('LAB01-PC01', 1, 'GIS-Workstation-01', 'active'),
('LAB01-PC02', 1, 'GIS-Workstation-02', 'active'),
('LAB01-PC03', 1, 'GIS-Workstation-03', 'active'),
('LAB01-PC04', 1, 'GIS-Workstation-04', 'active'),
('LAB01-PC05', 1, 'GIS-Workstation-05', 'active'),
('LAB01-PC06', 1, 'GIS-Workstation-06', 'active'),
('LAB01-PC07', 1, 'GIS-Workstation-07', 'active'),
('LAB01-PC08', 1, 'GIS-Workstation-08', 'active'),
('LAB01-PC09', 1, 'GIS-Workstation-09', 'active'),
('LAB01-PC10', 1, 'GIS-Workstation-10', 'active'),

-- Lab 02 computers
('LAB02-PC01', 2, 'Dev-Workstation-01', 'active'),
('LAB02-PC02', 2, 'Dev-Workstation-02', 'active'),
('LAB02-PC03', 2, 'Dev-Workstation-03', 'active'),
('LAB02-PC04', 2, 'Dev-Workstation-04', 'active'),
('LAB02-PC05', 2, 'Dev-Workstation-05', 'active'),

-- Lab 03 computers
('LAB03-PC01', 3, 'Survey-Workstation-01', 'active'),
('LAB03-PC02', 3, 'Survey-Workstation-02', 'active'),
('LAB03-PC03', 3, 'Survey-Workstation-03', 'active'),
('LAB03-PC04', 3, 'Survey-Workstation-04', 'active'),
('LAB03-PC05', 3, 'Survey-Workstation-05', 'active'),
('LAB03-PC06', 3, 'Survey-Workstation-06', 'active'),

-- Lab 04 computers
('LAB04-PC01', 4, 'Research-Workstation-01', 'active'),
('LAB04-PC02', 4, 'Research-Workstation-02', 'active'),
('LAB04-PC03', 4, 'Research-Workstation-03', 'active'),
('LAB04-PC04', 4, 'Research-Workstation-04', 'active')
ON DUPLICATE KEY UPDATE computer_name = VALUES(computer_name), status = VALUES(status);

-- Insert sample lab timetable (for lecturer LEC001)
INSERT INTO lab_timetables (lab_id, day_of_week, start_time, end_time, lecturer_id, subject, semester, batch) VALUES
(1, 'monday', '08:00:00', '10:00:00', 2, 'Geographic Information Systems', 'Semester 1', 'Batch 2024A'),
(1, 'wednesday', '10:00:00', '12:00:00', 2, 'Remote Sensing', 'Semester 1', 'Batch 2024A'),
(2, 'tuesday', '08:00:00', '10:00:00', 3, 'Computer Programming', 'Semester 1', 'Batch 2024B'),
(2, 'thursday', '14:00:00', '16:00:00', 3, 'Data Structures', 'Semester 1', 'Batch 2024B'),
(3, 'monday', '14:00:00', '16:00:00', 2, 'Surveying Techniques', 'Semester 2', 'Batch 2024A'),
(3, 'wednesday', '08:00:00', '10:00:00', 2, 'Photogrammetry', 'Semester 2', 'Batch 2024A'),
(4, 'friday', '10:00:00', '12:00:00', 3, 'Research Methodology', 'Semester 2', 'Batch 2024C')
ON DUPLICATE KEY UPDATE subject = VALUES(subject), semester = VALUES(semester), batch = VALUES(batch);

-- Insert sample borrow requests
INSERT INTO borrow_requests (user_id, item_id, quantity, reason, expected_return_date, status) VALUES
(6, 1, 1, 'Need desktop computer for final year GIS project development', '2025-11-15', 'pending'),
(7, 3, 1, 'GPS device required for field surveying assignment', '2025-11-10', 'approved'),
(8, 4, 1, 'ArcGIS license needed for spatial analysis coursework', '2025-11-20', 'pending'),
(6, 7, 1, 'Laptop required for mobile computing project', '2025-11-05', 'approved'),
(7, 2, 1, 'Total station for land surveying practical session', '2025-11-25', 'pending')
ON DUPLICATE KEY UPDATE reason = VALUES(reason), expected_return_date = VALUES(expected_return_date);

-- Insert sample lab reservations
INSERT INTO lab_reservations (lab_id, user_id, reservation_date, start_time, end_time, purpose, status) VALUES
(1, 6, '2025-10-28', '08:00:00', '10:00:00', 'GIS project work', 'approved'),
(2, 7, '2025-10-29', '10:00:00', '12:00:00', 'Programming assignment', 'pending'),
(3, 8, '2025-10-30', '14:00:00', '16:00:00', 'Surveying software training', 'approved'),
(1, 6, '2025-11-01', '08:00:00', '12:00:00', 'Group project meeting', 'pending'),
(4, 7, '2025-11-02', '10:00:00', '12:00:00', 'Research data analysis', 'approved')
ON DUPLICATE KEY UPDATE purpose = VALUES(purpose), status = VALUES(status);

-- Insert sample issue reports
INSERT INTO issue_reports (user_id, lab_id, computer_number, issue_category, description, status, reported_by) VALUES
(6, 1, 'PC01', 'software', 'ArcGIS software not loading properly', 'pending', 6),
(7, 2, 'PC03', 'hardware', 'Keyboard not responding', 'in_progress', 7),
(8, 3, 'PC02', 'network', 'No internet connection', 'resolved', 8),
(6, 1, 'PC05', 'projector', 'Projector display not working', 'pending', 6),
(7, 4, 'PC01', 'other', 'Mouse cursor freezing intermittently', 'pending', 7)
ON DUPLICATE KEY UPDATE description = VALUES(description), status = VALUES(status);

-- Insert sample notifications
INSERT INTO notifications (user_id, title, message, type) VALUES
(6, 'Reservation Approved', 'Your lab reservation for Lab 01 on 2025-10-28 has been approved.', 'success'),
(7, 'Borrow Request Pending', 'Your request for GPS Device is pending approval.', 'info'),
(8, 'Issue Resolved', 'Your network connectivity issue in Lab 03 has been resolved.', 'success'),
(6, 'New Timetable', 'New timetable has been published for GIS Software Lab.', 'info'),
(7, 'Maintenance Notice', 'Lab 02 will be under maintenance on 2025-11-05.', 'warning')
ON DUPLICATE KEY UPDATE message = VALUES(message), type = VALUES(type);

-- =====================================================
-- SETUP COMPLETION MESSAGE
-- =====================================================

SELECT 'Geo CMS Database Setup Complete!' as status;
SELECT
  (SELECT COUNT(*) FROM users) as users_count,
  (SELECT COUNT(*) FROM store_items) as items_count,
  (SELECT COUNT(*) FROM labs) as labs_count,
  (SELECT COUNT(*) FROM computers) as computers_count,
  (SELECT COUNT(*) FROM borrow_requests) as requests_count,
  (SELECT COUNT(*) FROM lab_reservations) as reservations_count,
  (SELECT COUNT(*) FROM issue_reports) as issues_count;

-- =====================================================
-- USAGE INSTRUCTIONS
-- =====================================================
/*
This database_final.sql file contains:

1. Complete database schema for Geo CMS
2. All required tables with proper relationships
3. Sample data for testing and demonstration
4. Performance indexes
5. Foreign key constraints

To use this file:
1. Create a new MySQL database named 'geo_cms'
2. Run this SQL file in phpMyAdmin or MySQL command line
3. Update config/config.php with your database credentials
4. Access the application at http://localhost/geo-cms/

The database includes:
- User management system
- Store inventory management
- Lab reservation system
- Issue reporting system
- Notification system
- Activity logging

*/