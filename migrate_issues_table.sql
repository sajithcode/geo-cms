-- Migration script to update issue_reports table for new Issue Reporting System
-- This will preserve your existing data while adding/renaming columns

USE geo_cms;

-- Step 1: Add new columns
ALTER TABLE issue_reports 
ADD COLUMN report_id VARCHAR(50) UNIQUE AFTER id,
ADD COLUMN remarks TEXT AFTER resolved_by;

-- Step 2: Rename columns to match new schema
ALTER TABLE issue_reports 
CHANGE COLUMN user_id reported_by INT(11) NOT NULL;

ALTER TABLE issue_reports 
CHANGE COLUMN computer_number computer_serial_no VARCHAR(100);

ALTER TABLE issue_reports 
CHANGE COLUMN category issue_category ENUM('hardware','software','network','projector','other') DEFAULT 'other';

ALTER TABLE issue_reports 
CHANGE COLUMN created_at reported_date DATETIME DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE issue_reports 
CHANGE COLUMN updated_at updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE issue_reports 
CHANGE COLUMN screenshot file_path VARCHAR(255);

-- Step 3: Update enum values for status (add 'resolved')
ALTER TABLE issue_reports 
MODIFY COLUMN status ENUM('pending','in_progress','resolved') DEFAULT 'pending';

-- Step 4: Update existing 'fixed' status to 'resolved'
UPDATE issue_reports SET status = 'resolved' WHERE status = 'fixed';

-- Step 5: Generate report_id for existing records
UPDATE issue_reports 
SET report_id = CONCAT('ISS-', DATE_FORMAT(reported_date, '%Y%m%d'), '-', LPAD(id, 4, '0'))
WHERE report_id IS NULL;

-- Step 6: Drop old columns that are not needed
ALTER TABLE issue_reports 
DROP COLUMN priority,
DROP COLUMN contact_info;

-- Step 7: Add index on report_id
CREATE INDEX idx_report_id ON issue_reports(report_id);

-- Step 8: Create issue_affected_computers table if it doesn't exist
CREATE TABLE IF NOT EXISTS issue_affected_computers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    computer_serial_no VARCHAR(100) NOT NULL,
    FOREIGN KEY (issue_id) REFERENCES issue_reports(id) ON DELETE CASCADE,
    INDEX idx_issue_id (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 9: Create issue_history table if it doesn't exist
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

-- Step 10: Create history entries for existing issue reports
INSERT INTO issue_history (issue_id, action, description, performed_by, action_date)
SELECT id, 'created', 'Issue report created (migrated)', reported_by, reported_date
FROM issue_reports
WHERE NOT EXISTS (
    SELECT 1 FROM issue_history WHERE issue_id = issue_reports.id AND action = 'created'
);

-- Verification queries
SELECT 'Migration completed successfully!' as message;
SELECT COUNT(*) as total_issues FROM issue_reports;
SELECT status, COUNT(*) as count FROM issue_reports GROUP BY status;
SELECT 'issue_reports table structure:' as message;
DESCRIBE issue_reports;
