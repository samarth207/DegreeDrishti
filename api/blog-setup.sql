-- =====================================================
-- DegreeDrishti - Blog CMS Database Setup
-- Run this entire file in Hostinger phpMyAdmin
-- =====================================================

-- Create admin_users table (includes all fields needed by the Blog CMS)
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(50)  NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `email`      VARCHAR(100) NOT NULL,
    `full_name`  VARCHAR(100) DEFAULT NULL,
    `role`       ENUM('admin','editor') DEFAULT 'admin',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Blog Categories
-- =====================================================
CREATE TABLE IF NOT EXISTS `blog_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default categories
INSERT IGNORE INTO `blog_categories` (`name`, `slug`) VALUES
    ('Career Tips', 'career-tips'),
    ('Education News', 'education-news'),
    ('Study Guides', 'study-guides'),
    ('University Updates', 'university-updates'),
    ('MBA Insights', 'mba-insights'),
    ('Technology & MCA', 'technology-mca'),
    ('Finance & Commerce', 'finance-commerce');

-- =====================================================
-- Blog Tags
-- =====================================================
CREATE TABLE IF NOT EXISTS `blog_tags` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Main Blogs Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `blogs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,

    -- Core Content
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `excerpt` VARCHAR(500) DEFAULT NULL,
    `content` LONGTEXT DEFAULT NULL,

    -- Feature Image
    `feature_image` VARCHAR(500) DEFAULT NULL,
    `feature_image_alt` VARCHAR(255) DEFAULT NULL,
    `feature_image_title` VARCHAR(255) DEFAULT NULL,

    -- SEO Meta
    `meta_title` VARCHAR(60) DEFAULT NULL,
    `meta_description` VARCHAR(250) DEFAULT NULL,
    `focus_keyword` VARCHAR(100) DEFAULT NULL,
    `primary_keyword` VARCHAR(100) DEFAULT NULL,

    -- Author
    `author_name` VARCHAR(100) DEFAULT 'DegreeDrishti Team',
    `author_bio` TEXT DEFAULT NULL,
    `author_image` VARCHAR(500) DEFAULT NULL,
    `author_page` VARCHAR(500) DEFAULT NULL,

    -- Publishing
    `status` ENUM('draft','pending','published','scheduled') DEFAULT 'draft',
    `publish_date` DATE DEFAULT NULL,
    `publish_time` TIME DEFAULT NULL,
    `scheduled_at` DATETIME DEFAULT NULL,

    -- Stats
    `read_time` INT(5) DEFAULT 5,
    `views` INT(11) DEFAULT 0,

    -- Audit
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT(11) DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    INDEX `idx_status` (`status`),
    INDEX `idx_publish_date` (`publish_date`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_scheduled_at` (`scheduled_at`),
    CONSTRAINT `fk_blog_author` FOREIGN KEY (`created_by`)
        REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Blog-Category Relations (Many-to-Many)
-- =====================================================
CREATE TABLE IF NOT EXISTS `blog_category_relations` (
    `blog_id` INT(11) NOT NULL,
    `category_id` INT(11) NOT NULL,
    PRIMARY KEY (`blog_id`, `category_id`),
    CONSTRAINT `fk_bcr_blog` FOREIGN KEY (`blog_id`)
        REFERENCES `blogs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bcr_cat` FOREIGN KEY (`category_id`)
        REFERENCES `blog_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Blog-Tag Relations (Many-to-Many)
-- =====================================================
CREATE TABLE IF NOT EXISTS `blog_tag_relations` (
    `blog_id` INT(11) NOT NULL,
    `tag_id` INT(11) NOT NULL,
    PRIMARY KEY (`blog_id`, `tag_id`),
    CONSTRAINT `fk_btr_blog` FOREIGN KEY (`blog_id`)
        REFERENCES `blogs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_btr_tag` FOREIGN KEY (`tag_id`)
        REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Blog Media Uploads
-- =====================================================
CREATE TABLE IF NOT EXISTS `blog_media` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `blog_id` INT(11) DEFAULT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) DEFAULT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `web_path` VARCHAR(500) NOT NULL,
    `file_size` INT(11) DEFAULT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `alt_text` VARCHAR(255) DEFAULT NULL,
    `caption` VARCHAR(500) DEFAULT NULL,
    `image_title` VARCHAR(255) DEFAULT NULL,
    `uploaded_by` INT(11) DEFAULT NULL,
    `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_blog_id` (`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SETUP: Create Initial Admin User
-- Default password: Admin@DD2026
-- CHANGE THIS PASSWORD IMMEDIATELY after first login
-- Run: php admin/setup.php  OR  visit /admin/setup.php
-- =====================================================
