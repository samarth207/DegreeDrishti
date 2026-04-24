-- =====================================================
-- DegreeDrishti - Database Setup for Counselling Requests
-- Run this SQL in Hostinger phpMyAdmin to create the table
-- =====================================================

-- Create the counselling_requests table
CREATE TABLE IF NOT EXISTS `counselling_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(15) NOT NULL,
    `course` VARCHAR(100) NOT NULL,
    `preferred_time` VARCHAR(50) DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('pending', 'contacted', 'completed', 'cancelled') DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Create an admin user table for managing requests
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Popup Enquiry Leads (Amity / Manipal / NMIMS pages)
-- =====================================================
CREATE TABLE IF NOT EXISTS `popup_enquiries` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `country_code` VARCHAR(10) DEFAULT '+91',
    `phone` VARCHAR(15) NOT NULL,
    `full_phone` VARCHAR(20) DEFAULT NULL,
    `course_interest` VARCHAR(100) DEFAULT NULL,
    `university` VARCHAR(100) DEFAULT NULL,
    `source` VARCHAR(200) DEFAULT NULL,
    `form_type` ENUM('enquiry', 'counselling') DEFAULT 'enquiry',
    `page_url` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('pending', 'contacted', 'completed', 'cancelled') DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_university` (`university`),
    INDEX `idx_form_type` (`form_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Useful Queries
-- =====================================================

-- View all pending counselling requests
-- SELECT * FROM counselling_requests WHERE status = 'pending' ORDER BY created_at DESC;

-- Update status after contacting
-- UPDATE counselling_requests SET status = 'contacted', notes = 'Called on date' WHERE id = 1;

-- View all popup enquiries by university
-- SELECT * FROM popup_enquiries WHERE university = 'Amity University Online' ORDER BY created_at DESC;

-- View all timed counselling popup leads
-- SELECT * FROM popup_enquiries WHERE form_type = 'counselling' ORDER BY created_at DESC;

-- Count leads per university
-- SELECT university, COUNT(*) as total FROM popup_enquiries GROUP BY university;
