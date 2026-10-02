-- =====================================================
-- DegreeDrishti - Expert Career Counselors Database Setup
-- Run this SQL in Hostinger phpMyAdmin to create the table
-- =====================================================

-- Create the counselors table
CREATE TABLE IF NOT EXISTS `counselors` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `qualification` VARCHAR(200) DEFAULT NULL,
    `students_counselled` VARCHAR(50) DEFAULT NULL,
    `experience_years` VARCHAR(50) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `image_alt` VARCHAR(255) DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_active` (`active`),
    INDEX `idx_sort_order` (`sort_order`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Migrate existing counselors from index.html
-- =====================================================

INSERT INTO `counselors` (`name`, `qualification`, `students_counselled`, `experience_years`, `image`, `image_alt`, `active`, `sort_order`) VALUES
('Mr. Vidya Sagar', 'MBA, Career Counselor', '1500+', '5 Years', 'images/counselor2.jpeg', 'Mr. Vidya Sagar - Career Counselor', 1, 1),
('Md Faiz', 'M.Ed, Educational Consultant', '4200+', '6 Years', 'images/counselor6.png', 'Md Faiz - Educational Consultant', 1, 2),
('Mr. Mohit Gupta', 'MBA, Career Counselor', '4000+', '6 Years', 'images/counselor4.jpeg', 'Mr. Mohit Gupta - Career Counselor', 1, 3),
('Mr. Kunal Chauhan', 'Academic Advisor', '1500+', '5.5 Years', 'images/counselor7.jpeg', 'Mr. Kunal Chauhan - Academic Advisor', 1, 4),
('Mr. Divyanshu Kashyap', 'Career Counselor', '1600+', '4.5 Years', 'images/counselor8.jpeg', 'Mr. Divyanshu Kashyap - Career Counselor', 1, 5);

-- =====================================================
-- Useful Queries
-- =====================================================

-- View all active counselors ordered by sort order
-- SELECT * FROM counselors WHERE active = 1 ORDER BY sort_order ASC, created_at DESC;

-- Update counselor details
-- UPDATE counselors SET name = 'New Name', qualification = 'New Qualification' WHERE id = 1;

-- Deactivate a counselor (hide from frontend)
-- UPDATE counselors SET active = 0 WHERE id = 1;

-- Reorder counselors
-- UPDATE counselors SET sort_order = 1 WHERE id = 5;
