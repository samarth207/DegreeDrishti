<?php
/**
 * DegreeDrishti - 1 to 1 Counselling Form Handler
 * This file handles the form submission and stores data in MySQL database
 * 
 * For Hostinger: Upload this file to your public_html/api/ folder
 */

// Include configuration
require_once 'config.php';

// Enable error logging for debugging
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/error_log.txt');
}

// Set headers for CORS and JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get the POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log incoming data for debugging
if (DEBUG_MODE) {
    error_log("Received data: " . print_r($data, true));
}

// If JSON parsing failed, try regular POST data
if ($data === null) {
    $data = $_POST;
    if (DEBUG_MODE) {
        error_log("Using POST data: " . print_r($data, true));
    }
}

// Validate required fields
$required_fields = ['name', 'email', 'phone', 'course'];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        $errors[] = ucfirst($field) . ' is required';
    }
}

// Validate email format
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Validate phone number (10 digits)
if (!empty($data['phone']) && !preg_match('/^[0-9]{10}$/', $data['phone'])) {
    $errors[] = 'Phone number must be 10 digits';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Sanitize input data
$name = htmlspecialchars(strip_tags(trim($data['name'])));
$email = htmlspecialchars(strip_tags(trim($data['email'])));
$phone = htmlspecialchars(strip_tags(trim($data['phone'])));
$course = htmlspecialchars(strip_tags(trim($data['course'])));
$preferred_time = isset($data['preferred_time']) ? htmlspecialchars(strip_tags(trim($data['preferred_time']))) : '';
$message = isset($data['message']) ? htmlspecialchars(strip_tags(trim($data['message']))) : '';

try {
    // Create database connection using config
    $pdo = getDBConnection();
    
    // Prepare and execute the insert statement
    $stmt = $pdo->prepare("
        INSERT INTO counselling_requests (name, email, phone, course, preferred_time, message, created_at, status)
        VALUES (:name, :email, :phone, :course, :preferred_time, :message, NOW(), 'pending')
    ");
    
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':course' => $course,
        ':preferred_time' => $preferred_time,
        ':message' => $message
    ]);
    
    // Get the inserted ID
    $inserted_id = $pdo->lastInsertId();
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Your counselling request has been submitted successfully! Our team will contact you soon.',
        'request_id' => $inserted_id
    ]);
    
    // Optional: Send email notification to admin
    $admin_email = 'info@degreedrishti.com'; // Change this to your email
    $subject = "New Counselling Request - $name";
    $email_body = "
        New 1-to-1 Counselling Request Received:
        
        Name: $name
        Email: $email
        Phone: $phone
        Course: $course
        Preferred Time: $preferred_time
        Message: $message
        
        Request ID: $inserted_id
        Submitted at: " . date('Y-m-d H:i:s') . "
    ";
    
    $headers = "From: noreply@degreedrishti.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    @mail($admin_email, $subject, $email_body, $headers);
    
} catch (PDOException $e) {
    http_response_code(500);
    $error_message = DEBUG_MODE ? $e->getMessage() : 'Database error. Please try again later.';
    if (DEBUG_MODE) {
        error_log("Database error: " . $e->getMessage());
    }
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.',
        'error' => $error_message,
        'debug' => DEBUG_MODE ? [
            'trace' => $e->getTraceAsString()
        ] : null
    ]);
}
?>
