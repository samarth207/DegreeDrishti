<?php
/**
 * DegreeDrishti - Popup Enquiry Form Handler
 * Handles submissions from:
 *   1. "Enquire Now" popup (university pages)
 *   2. "Book 1-to-1 Counselling" timed popup (university pages)
 * 
 * Stores data in MySQL via the same DB config as save-counselling.php
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

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get POST data (JSON body)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (DEBUG_MODE) {
    error_log("Popup enquiry received: " . print_r($data, true));
}

// Fallback to regular POST
if ($data === null) {
    $data = $_POST;
}

// Determine form type
$formType = isset($data['formType']) ? trim($data['formType']) : 'enquiry';

// Validate required fields based on form type
if ($formType === 'counselling') {
    $required = ['fullName', 'phone', 'email'];
} else {
    $required = ['fullName', 'email', 'phone', 'courseInterest'];
}

$errors = [];
foreach ($required as $field) {
    if (empty($data[$field])) {
        $errors[] = ucfirst($field) . ' is required';
    }
}

// Validate email
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Validate phone (10 digits)
if (!empty($data['phone']) && !preg_match('/^[0-9]{10}$/', $data['phone'])) {
    $errors[] = 'Phone number must be 10 digits';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Sanitize input
$fullName       = htmlspecialchars(strip_tags(trim($data['fullName'])));
$email          = htmlspecialchars(strip_tags(trim($data['email'])));
$phone          = htmlspecialchars(strip_tags(trim($data['phone'])));
$countryCode    = isset($data['countryCode']) ? htmlspecialchars(strip_tags(trim($data['countryCode']))) : '+91';
$fullPhone      = $countryCode . $phone;
$courseInterest  = isset($data['courseInterest']) ? htmlspecialchars(strip_tags(trim($data['courseInterest']))) : '';
$university     = isset($data['university']) ? htmlspecialchars(strip_tags(trim($data['university']))) : '';
$source         = isset($data['source']) ? htmlspecialchars(strip_tags(trim($data['source']))) : '';
$pageUrl        = isset($data['pageUrl']) ? htmlspecialchars(strip_tags(trim($data['pageUrl']))) : '';

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        INSERT INTO popup_enquiries 
            (full_name, email, country_code, phone, full_phone, course_interest, university, source, form_type, page_url, created_at, status)
        VALUES 
            (:full_name, :email, :country_code, :phone, :full_phone, :course_interest, :university, :source, :form_type, :page_url, NOW(), 'pending')
    ");

    $stmt->execute([
        ':full_name'       => $fullName,
        ':email'           => $email,
        ':country_code'    => $countryCode,
        ':phone'           => $phone,
        ':full_phone'      => $fullPhone,
        ':course_interest' => $courseInterest,
        ':university'      => $university,
        ':source'          => $source,
        ':form_type'       => $formType,
        ':page_url'        => $pageUrl
    ]);

    $insertedId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Your enquiry has been submitted successfully! Our team will contact you shortly.',
        'lead_id' => $insertedId
    ]);

    // Email notification to admin
    $admin_email = ADMIN_EMAIL;
    $subject = "New " . ucfirst($formType) . " Lead - $fullName ($university)";
    $email_body = "
New Popup Enquiry Received:

Form Type: $formType
Name: $fullName
Email: $email
Phone: $fullPhone
Course: $courseInterest
University: $university
Source: $source
Page URL: $pageUrl

Lead ID: $insertedId
Submitted at: " . date('Y-m-d H:i:s') . "
    ";

    $headers = "From: noreply@degreedrishti.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($admin_email, $subject, $email_body, $headers);

} catch (PDOException $e) {
    http_response_code(500);
    if (DEBUG_MODE) {
        error_log("Popup enquiry DB error: " . $e->getMessage());
    }
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>
