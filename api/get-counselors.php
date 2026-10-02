<?php
/**
 * Get Expert Career Counselors API
 * Returns JSON array of active counselors for frontend display
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes

require_once __DIR__ . '/config.php';

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare(
        "SELECT id, name, qualification, students_counselled, experience_years, bio, image, image_alt
           FROM counselors
          WHERE active = 1
          ORDER BY sort_order ASC, created_at DESC"
    );
    $stmt->execute();
    $counselors = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $counselors,
        'count' => count($counselors)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch counselors',
        'message' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>
