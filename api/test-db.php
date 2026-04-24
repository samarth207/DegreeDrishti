<?php
/**
 * Database Connection Test
 * Access this file to test if your database is properly configured
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    // Test database connection
    $pdo = getDBConnection();
    
    $response = [
        'success' => true,
        'message' => 'Database connection successful!',
        'database' => DB_NAME,
        'host' => DB_HOST
    ];
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'counselling_requests'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        $response['table_status'] = 'Table exists';
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE counselling_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $response['columns'] = $columns;
        
        // Get row count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM counselling_requests");
        $count = $stmt->fetch();
        $response['total_records'] = $count['count'];
    } else {
        $response['table_status'] = 'Table does NOT exist - please run database_setup.sql';
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
