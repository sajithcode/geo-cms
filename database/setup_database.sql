-- Geo CMS Complete Database Setup
-- Faculty of Geomatics - Sabaragamuwa University of Sri Lanka
-- This file creates the entire database from scratch

-- Drop database if it exists (optional - uncomment if you want to recreate)
-- DROP DATABASE IF EXISTS geo_cms;

-- Create database
CREATE DATABASE IF NOT EXISTS geo_cms;
USE geo_cms;

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
    is_active BOOLEAN DEFAULT TRUE
);

-- Store categories
CREATE TABLE IF NOT EXISTS store_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Store items
CREATE TABLE IF NOT EXISTS store_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    description TEXT,
    quantity_total INT NOT NULL DEFAULT 0,
    quantity_available INT NOT NULL DEFAULT 0,
    quantity_borrowed INT NOT NULL DEFAULT 0,
    quantity_maintenance INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES store_categories(id)
);

-- Borrow requests
CREATE TABLE IF NOT EXISTS borrow_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    reason TEXT,
    expected_return_date DATE,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'returned') DEFAULT 'pending',
    approved_by INT,
    approved_date TIMESTAMP NULL,
    returned_date TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (item_id) REFERENCES store_items(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Labs
CREATE TABLE IF NOT EXISTS labs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    capacity INT DEFAULT 30,
    status ENUM('available', 'in_use', 'maintenance') DEFAULT 'available',
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
    FOREIGN KEY (lab_id) REFERENCES labs(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
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
    FOREIGN KEY (lab_id) REFERENCES labs(id),
    FOREIGN KEY (lecturer_id) REFERENCES users(id)
);

-- Issue reports
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
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (lab_id) REFERENCES labs(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- System logs
CREATE TABLE IF NOT EXISTS system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default users
INSERT INTO users (name, email, user_id, password, role) VALUES
('Admin User', 'admin@sab.ac.lk', 'ADMIN001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dr. John Lecturer', 'lecturer@sab.ac.lk', 'LEC001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('Staff Member', 'staff@sab.ac.lk', 'STAFF001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Student User', 'student@sab.ac.lk', 'STU001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Insert default labs
INSERT INTO labs (name, description, capacity) VALUES
('Lab 01', 'Computer Lab 01 - GIS Software Lab', 30),
('Lab 02', 'Computer Lab 02 - Programming Lab', 25),
('Lab 03', 'Computer Lab 03 - Surveying Software Lab', 30),
('Lab 04', 'Computer Lab 04 - Research Lab', 20);

-- Insert default store categories
INSERT INTO store_categories (name, description) VALUES
('Computers', 'Desktop computers and workstations'),
('Surveying Equipment', 'Total stations, GPS devices, levels'),
('Software', 'Licensed software and applications'),
('Accessories', 'Cables, adapters, and other accessories');

-- Insert default store items
INSERT INTO store_items (name, category_id, description, quantity_total, quantity_available) VALUES
('Desktop Computer', 1, 'HP EliteDesk 800 G6', 50, 45),
('Total Station', 2, 'Leica TS16 Total Station', 5, 4),
('GPS Device', 2, 'Trimble R10 GNSS Receiver', 8, 6),
('ArcGIS License', 3, 'ArcGIS Desktop Professional License', 30, 25),
('USB Cable', 4, 'USB-A to USB-C Cable', 20, 18);

-- Setup complete message
SELECT 'Geo CMS Database Setup Complete!' as Status;