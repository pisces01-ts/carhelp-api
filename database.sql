-- =====================================================
-- CarHelp Emergency Repair Database
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- =====================================================
-- Table: users
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('customer','technician','admin') NOT NULL DEFAULT 'customer',
  `status` enum('pending','active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `phone` (`phone`),
  KEY `idx_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: technician_profiles
-- =====================================================
CREATE TABLE IF NOT EXISTS `technician_profiles` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `expertise` text DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `current_lat` double DEFAULT NULL,
  `current_lng` double DEFAULT NULL,
  `last_location_update` timestamp NULL DEFAULT NULL,
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `total_jobs` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  PRIMARY KEY (`profile_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_online_available` (`is_online`, `is_available`),
  CONSTRAINT `fk_tech_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: repair_types
-- =====================================================
CREATE TABLE IF NOT EXISTS `repair_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: service_requests
-- =====================================================
CREATE TABLE IF NOT EXISTS `service_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `problem_type` varchar(100) NOT NULL,
  `problem_details` text DEFAULT NULL,
  `problem_image` text DEFAULT NULL,
  `location_lat` double NOT NULL,
  `location_lng` double NOT NULL,
  `location_address` text DEFAULT NULL,
  `status` enum('pending','accepted','traveling','arrived','working','completed','cancelled') DEFAULT 'pending',
  `price` decimal(10,2) DEFAULT 0.00,
  `request_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `customer_id` (`customer_id`),
  KEY `technician_id` (`technician_id`),
  KEY `idx_status` (`status`),
  KEY `idx_customer_status` (`customer_id`, `status`),
  CONSTRAINT `fk_request_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_request_tech` FOREIGN KEY (`technician_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: reviews
-- =====================================================
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `request_id` (`request_id`),
  KEY `technician_id` (`technician_id`),
  CONSTRAINT `fk_review_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: admin_logs
-- =====================================================
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample Data
-- =====================================================

-- Admin user (password: admin123)
INSERT INTO `users` (`fullname`, `phone`, `email`, `password`, `role`, `status`) VALUES
('Admin', '0999999999', 'admin@carhelp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Sample repair types
INSERT INTO `repair_types` (`name`) VALUES
('ยางแตก / ยางรั่ว'),
('แบตหมด / จั๊มแบต'),
('รถสตาร์ทไม่ติด'),
('น้ำมันหมด'),
('รถเสียกลางทาง'),
('ลากรถ / รถสไลด์');
