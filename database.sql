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

-- =====================================================
-- Table: promotions
-- =====================================================
CREATE TABLE IF NOT EXISTS `promotions` (
  `promo_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `code` varchar(50) NOT NULL UNIQUE,
  `discount_type` enum('percent','fixed') DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0,
  `min_amount` decimal(10,2) DEFAULT 0,
  `max_uses` int(11) DEFAULT 0,
  `used_count` int(11) DEFAULT 0,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`promo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: complaints
-- =====================================================
CREATE TABLE IF NOT EXISTS `complaints` (
  `complaint_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `admin_response` text DEFAULT NULL,
  `status` enum('pending','in_progress','resolved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`complaint_id`),
  KEY `request_id` (`request_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: chat_messages
-- =====================================================
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('customer','technician') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `request_id` (`request_id`),
  KEY `idx_request_created` (`request_id`, `created_at`),
  CONSTRAINT `fk_chat_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample repair types
INSERT INTO `repair_types` (`name`, `base_price`) VALUES
('ยางแตก / ยางรั่ว', 300),
('แบตหมด / จั๊มแบต', 200),
('รถสตาร์ทไม่ติด', 500),
('น้ำมันหมด', 150),
('รถเสียกลางทาง', 800),
('ลากรถ / รถสไลด์', 1500);

-- =====================================================
-- Sample Users (password: 123456 for all)
-- =====================================================

-- Customers
INSERT INTO `users` (`fullname`, `phone`, `email`, `password`, `role`, `status`) VALUES
('มาดามไก่', '0811111111', 'madam@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active'),
('สมชาย ใจดี', '0822222222', 'somchai@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active'),
('สมศรี รักดี', '0833333333', 'somsri@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active');

-- Technicians
INSERT INTO `users` (`fullname`, `phone`, `email`, `password`, `role`, `status`) VALUES
('ช่างสมปอง', '0844444444', 'tech1@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technician', 'active'),
('ช่างมานะ', '0855555555', 'tech2@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technician', 'active'),
('ช่างวิชัย', '0866666666', 'tech3@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technician', 'active');

-- Technician Profiles
INSERT INTO `technician_profiles` (`user_id`, `expertise`, `vehicle_plate`, `vehicle_model`, `is_online`, `is_available`, `current_lat`, `current_lng`, `avg_rating`, `total_jobs`, `status`) VALUES
(5, 'ยางรถยนต์, แบตเตอรี่', 'กข 1234', 'Toyota Hilux', 1, 1, 13.7563, 100.5018, 4.80, 25, 'approved'),
(6, 'เครื่องยนต์, ระบบไฟฟ้า', 'ขค 5678', 'Isuzu D-Max', 1, 1, 13.7500, 100.4900, 4.50, 18, 'approved'),
(7, 'ลากรถ, ซ่อมทั่วไป', 'คง 9012', 'Ford Ranger', 0, 1, 13.7600, 100.5100, 4.20, 12, 'approved');

-- =====================================================
-- Sample Service Requests
-- =====================================================
INSERT INTO `service_requests` (`customer_id`, `technician_id`, `problem_type`, `problem_details`, `location_lat`, `location_lng`, `location_address`, `status`, `price`, `request_time`, `completed_time`) VALUES
(2, 5, 'ยางแตก / ยางรั่ว', 'ยางหลังขวาแตก', 13.7563, 100.5018, 'ถนนสุขุมวิท ใกล้ BTS อโศก', 'completed', 350, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 5, 'แบตหมด / จั๊มแบต', 'รถจอดนาน แบตหมด', 13.7450, 100.5350, 'ห้างสรรพสินค้า เซ็นทรัลเวิลด์', 'completed', 250, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 6, 'รถสตาร์ทไม่ติด', 'สตาร์ทไม่ติด ไม่ทราบสาเหตุ', 13.7300, 100.5200, 'ซอยสุขุมวิท 55 ทองหล่อ', 'completed', 600, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, NULL, 'น้ำมันหมด', 'น้ำมันหมดกลางทาง', 13.7400, 100.5000, 'ถนนพระราม 4', 'pending', 0, NOW(), NULL);

-- =====================================================
-- Sample Reviews
-- =====================================================
INSERT INTO `reviews` (`request_id`, `customer_id`, `technician_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 5, 5, 'ช่างมาเร็วมาก ซ่อมเสร็จไว ราคาเป็นธรรม ประทับใจครับ', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 3, 5, 5, 'บริการดีมาก ช่างใจดี แนะนำเลย', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 4, 6, 4, 'บริการดี แต่มาช้านิดหน่อย', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- =====================================================
-- Sample Promotions
-- =====================================================
INSERT INTO `promotions` (`title`, `description`, `code`, `discount_type`, `discount_value`, `min_amount`, `start_date`, `end_date`, `status`) VALUES
('ลด 20% สำหรับลูกค้าใหม่', 'ใช้ได้ทุกบริการ สำหรับการใช้งานครั้งแรก', 'WELCOME20', 'percent', 20, 0, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active'),
('ฟรีค่าเดินทาง', 'เมื่อใช้บริการเปลี่ยนยาง', 'FREETIRE', 'fixed', 100, 300, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'active'),
('ลด 100 บาท', 'เมื่อใช้บริการครบ 500 บาทขึ้นไป', 'SAVE100', 'fixed', 100, 500, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'active');

-- =====================================================
-- Sample Chat Messages
-- =====================================================
INSERT INTO `chat_messages` (`request_id`, `sender_id`, `sender_role`, `message`, `created_at`) VALUES
(1, 2, 'customer', 'ช่างมาถึงแล้วหรือยังคะ?', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 5, 'technician', 'กำลังเดินทางไปครับ อีกประมาณ 10 นาที', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 2, 'customer', 'ขอบคุณค่ะ', DATE_SUB(NOW(), INTERVAL 2 DAY));
