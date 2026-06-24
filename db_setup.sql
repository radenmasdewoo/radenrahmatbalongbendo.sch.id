-- SQL Schema for CMS PRO Authentication and RBAC
-- Database Name: `cms_pro_db` (or whatever DB you choose)

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin', 'editor') NOT NULL DEFAULT 'editor',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create logs table for brute force protection
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `username_attempt` VARCHAR(50) NOT NULL,
  `status` ENUM('success', 'failed') NOT NULL,
  `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default Super Admin (Password: admin123)
-- hash generated via php password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `users` (`username`, `password_hash`, `role`) VALUES
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');
