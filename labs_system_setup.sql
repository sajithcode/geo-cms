-- Labs System Database Verification and Sample Data
-- Run this script after setting up the main database.sql

-- Verify labs table exists
CREATE TABLE IF NOT EXISTS labs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    capacity INT DEFAULT 30,
    status ENUM('available', 'in_use', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Verify lab_reservations table exists
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

-- Verify lab_timetables table exists
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

-- Verify issue_reports table exists
CREATE TABLE IF NOT EXISTS issue_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    lab_id INT,
    computer_number VARCHAR(10),
    description TEXT NOT NULL,
    screenshot VARCHAR(255),
    status ENUM('pending', 'in_progress', 'fixed') DEFAULT 'pending',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample labs (only if table is empty)
INSERT INTO labs (name, description, capacity, status) 
SELECT * FROM (
    SELECT 'Lab 01' as name, 'Computer Science Lab - Equipped with 30 high-performance computers' as description, 30 as capacity, 'available' as status
    UNION ALL
    SELECT 'Lab 02', 'Network Lab - Network equipment and 25 workstations', 25, 'available'
    UNION ALL
    SELECT 'Lab 03', 'Database Lab - Specialized for database management courses', 30, 'available'
    UNION ALL
    SELECT 'Lab 04', 'Multimedia Lab - Graphics and multimedia workstations', 20, 'available'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM labs);

-- Insert sample timetable entries (only if a lecturer user exists)
-- This is a sample - adjust based on your actual user IDs
INSERT INTO lab_timetables (lab_id, day_of_week, start_time, end_time, lecturer_id, subject, batch)
SELECT * FROM (
    SELECT 1 as lab_id, 'monday' as day_of_week, '08:00:00' as start_time, '10:00:00' as end_time, 
           (SELECT id FROM users WHERE role = 'lecturer' LIMIT 1) as lecturer_id, 
           'Data Structures' as subject, 'Batch 2023A' as batch
    WHERE EXISTS (SELECT 1 FROM users WHERE role = 'lecturer')
    
    UNION ALL
    
    SELECT 1, 'wednesday', '10:00:00', '12:00:00',
           (SELECT id FROM users WHERE role = 'lecturer' LIMIT 1),
           'Object Oriented Programming', 'Batch 2023B'
    WHERE EXISTS (SELECT 1 FROM users WHERE role = 'lecturer')
    
    UNION ALL
    
    SELECT 2, 'tuesday', '08:00:00', '10:00:00',
           (SELECT id FROM users WHERE role = 'lecturer' LIMIT 1),
           'Computer Networks', 'Batch 2023A'
    WHERE EXISTS (SELECT 1 FROM users WHERE role = 'lecturer')
    
    UNION ALL
    
    SELECT 3, 'thursday', '14:00:00', '16:00:00',
           (SELECT id FROM users WHERE role = 'lecturer' LIMIT 1),
           'Database Management Systems', 'Batch 2023C'
    WHERE EXISTS (SELECT 1 FROM users WHERE role = 'lecturer')
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM lab_timetables);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_reservation_date ON lab_reservations(reservation_date);
CREATE INDEX IF NOT EXISTS idx_reservation_status ON lab_reservations(status);
CREATE INDEX IF NOT EXISTS idx_reservation_user ON lab_reservations(user_id);
CREATE INDEX IF NOT EXISTS idx_timetable_lab ON lab_timetables(lab_id);
CREATE INDEX IF NOT EXISTS idx_issue_status ON issue_reports(status);
CREATE INDEX IF NOT EXISTS idx_issue_lab ON issue_reports(lab_id);

-- Display verification message
SELECT 'Labs system database tables verified and sample data inserted successfully!' as message;
