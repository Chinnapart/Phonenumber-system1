-- STREAMING_CHUNK:Creating database and selecting it...
-- ==========================================
-- Database Schema for ConnectPro
-- Internal Phone Directory & IP Status Tracker
-- ==========================================

-- สร้างฐานข้อมูลหากยังไม่มี และเลือกใช้งานทันที
CREATE DATABASE IF NOT EXISTS connectpro_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE connectpro_db;

-- STREAMING_CHUNK:Creating departments table...
-- --------------------------------------------------------
-- Table Structure for `departments`
-- --------------------------------------------------------
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,          -- e.g., IT Support, Human Resources
  `color_code` varchar(20) DEFAULT NULL, -- Hex code for UI badges (e.g., #4F46E5)
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STREAMING_CHUNK:Creating users table...
-- --------------------------------------------------------
-- Table Structure for `users` (For System Login: Admin/General User)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user', -- Access Level
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STREAMING_CHUNK:Creating contacts table...
-- --------------------------------------------------------
-- Table Structure for `contacts` (The Main Phone Directory)
-- --------------------------------------------------------
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `job_title` varchar(100) DEFAULT NULL, -- e.g., IT Manager, HR Officer
  `department_id` int(11) DEFAULT NULL,
  `extension` varchar(20) DEFAULT NULL,  -- Internal phone extension
  `mobile_number` varchar(20) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL, -- For pinging network status
  `avatar_url` varchar(255) DEFAULT NULL,
  `last_online` datetime DEFAULT NULL,   -- Timestamp of last successful ping
  `status` enum('online','offline','unknown') DEFAULT 'unknown', -- Cached status
  `created_by` int(11) DEFAULT NULL,     -- User ID who added this contact
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STREAMING_CHUNK:Creating activity_logs table...
-- --------------------------------------------------------
-- Table Structure for `activity_logs` (Recent Activities)
-- --------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,        -- User who performed the action
  `action_type` varchar(50) NOT NULL,    -- e.g., 'add_contact', 'update_ip', 'import_excel'
  `target_id` int(11) DEFAULT NULL,      -- ID of the affected record (e.g., contact_id)
  `description` text NOT NULL,           -- Detailed text for the UI (e.g., "Admin added new contact...")
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STREAMING_CHUNK:Inserting dummy data for departments...
-- ==========================================
-- Insert Initial Dummy Data (For Testing)
-- ==========================================

-- Insert Departments
INSERT INTO `departments` (`name`, `color_code`) VALUES
('IT Support', '#3b82f6'),   -- blue-500
('IT Department', '#2563eb'), -- blue-600
('Management', '#8b5cf6'),    -- violet-500
('Human Resources', '#ec4899'), -- pink-500
('Maintenance', '#f97316'),   -- orange-500
('Accounting', '#10b981'),    -- emerald-500
('Safety', '#eab308');        -- yellow-500

-- STREAMING_CHUNK:Inserting admin user data...
-- Insert Default Admin User (Password is 'password123')
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin');

-- STREAMING_CHUNK:Inserting dummy contacts data...
-- Insert Sample Contacts based on Design
INSERT INTO `contacts` (`first_name`, `last_name`, `job_title`, `department_id`, `extension`, `mobile_number`, `ip_address`, `status`) VALUES
('Poonkasem', 'Pahamanat', 'IT Support Specialist', 1, '2456', '081-234-5678', '192.168.10.25', 'online'),
('Sillapatong', 'Chinnapart', 'Network Engineer', 2, '2201', '082-345-6789', '192.168.10.45', 'online'),
('Hirota', 'Kensuke', 'IT Manager', 3, '1102', '083-456-7890', '192.168.10.88', 'online'),
('Nattaporn', 'Phromsri', 'HR Officer', 4, '3305', '084-567-8901', '192.168.20.15', 'offline'),
('Anucha', 'Intachai', 'Maintenance Tech', 5, '4408', '085-678-9012', '192.168.30.22', 'online');