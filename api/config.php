<?php
/**
 * DegreeDrishti - Database Configuration
 * 
 * IMPORTANT: Update these values with your Hostinger database credentials
 * 
 * To find your database credentials in Hostinger:
 * 1. Log in to Hostinger hPanel
 * 2. Go to Databases > MySQL Databases
 * 3. Create a new database or use an existing one
 * 4. Note down the database name, username, and password
 */

// Database Configuration
define('DB_HOST', 'localhost');  // Usually 'localhost' for Hostinger
define('DB_NAME', 'degreedr_main_site');  // Your database name (e.g., u123456789_dbname)
define('DB_USER', 'degreedr_samarth');  // Your database username
define('DB_PASS', '1!2@3#QwE');  // Your database password

// Site Configuration
define('SITE_NAME', 'DegreeDrishti');
define('ADMIN_EMAIL', 'info@degreedrishti.com');

// Error Reporting (set to false in production)
define('DEBUG_MODE', true);

/**
 * Get database connection
 * @return PDO Database connection object
 */
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Database connection failed: " . $e->getMessage());
        } else {
            die("Database connection failed. Please try again later.");
        }
    }
}
?>
