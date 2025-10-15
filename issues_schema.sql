-- Issue Reporting System Database Schema

-- Create issue_reports table
CREATE TABLE IF NOT EXISTS issue_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id VARCHAR(50) UNIQUE NOT NULL,
    computer_serial_no VARCHAR(100),
    lab_id INT,
    issue_category ENUM('hardware', 'software', 'network', 'projector', 'other') NOT NULL,
    description TEXT NOT NULL,
    file_path VARCHAR(255),
    status ENUM('pending', 'in_progress', 'resolved') DEFAULT 'pending',
    reported_by INT NOT NULL,
    assigned_to INT,
    resolved_by INT,
    remarks TEXT,
    reported_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_date DATETIME,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_reported_by (reported_by),
    INDEX idx_lab_id (lab_id),
    INDEX idx_computer_serial (computer_serial_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create issue_affected_computers table (for multiple computer selection by lecturers)
CREATE TABLE IF NOT EXISTS issue_affected_computers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    computer_serial_no VARCHAR(100) NOT NULL,
    FOREIGN KEY (issue_id) REFERENCES issue_reports(id) ON DELETE CASCADE,
    INDEX idx_issue_id (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create issue_history table (for tracking status changes)
CREATE TABLE IF NOT EXISTS issue_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    performed_by INT NOT NULL,
    action_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issue_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_issue_id (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample computer data (if computers table doesn't exist)
CREATE TABLE IF NOT EXISTS computers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serial_no VARCHAR(100) UNIQUE NOT NULL,
    lab_id INT NOT NULL,
    computer_name VARCHAR(100),
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE,
    INDEX idx_lab_id (lab_id),
    INDEX idx_serial_no (serial_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for computers (optional)
INSERT INTO computers (serial_no, lab_id, computer_name, status) VALUES
('LAB01-PC01', 1, 'Computer 01', 'active'),
('LAB01-PC02', 1, 'Computer 02', 'active'),
('LAB01-PC03', 1, 'Computer 03', 'active'),
('LAB01-PC04', 1, 'Computer 04', 'active'),
('LAB01-PC05', 1, 'Computer 05', 'active'),
('LAB02-PC01', 2, 'Computer 01', 'active'),
('LAB02-PC02', 2, 'Computer 02', 'active'),
('LAB02-PC03', 2, 'Computer 03', 'active'),
('LAB02-PC04', 2, 'Computer 04', 'active'),
('LAB02-PC05', 2, 'Computer 05', 'active'),
('LAB03-PC01', 3, 'Computer 01', 'active'),
('LAB03-PC02', 3, 'Computer 02', 'active'),
('LAB03-PC03', 3, 'Computer 03', 'active'),
('LAB03-PC04', 3, 'Computer 04', 'active'),
('LAB03-PC05', 3, 'Computer 05', 'active'),
('LAB04-PC01', 4, 'Computer 01', 'active'),
('LAB04-PC02', 4, 'Computer 02', 'active'),
('LAB04-PC03', 4, 'Computer 03', 'active'),
('LAB04-PC04', 4, 'Computer 04', 'active'),
('LAB04-PC05', 4, 'Computer 05', 'active')
ON DUPLICATE KEY UPDATE serial_no=serial_no;
