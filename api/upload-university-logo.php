<?php
/**
 * University Logo Upload Handler
 * Uploads university logos to /images/university-logos/ directory
 */

// Auth check: admin must be logged in
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
    session_start();
}
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorised']);
    exit;
}

header('Content-Type: application/json');

if (empty($_FILES['logo_upload']) || empty($_FILES['logo_upload']['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['logo_upload'];

// Validate upload error
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File too large.',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
        UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk.',
    ];
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $uploadErrors[$file['error']] ?? 'Upload error.']);
    exit;
}

// Validate MIME type
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(415);
    echo json_encode(['success' => false, 'error' => 'Only JPG, PNG and WebP images are allowed.']);
    exit;
}

// Validate size (max 2 MB for logos)
if ($file['size'] > 2 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'File size must be under 2 MB.']);
    exit;
}

// Build safe filename
$extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$ext      = $extMap[$mime];
$filename = 'uni-' . bin2hex(random_bytes(8)) . '.' . $ext;

// Ensure upload directory exists
$docRoot   = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$uploadDir = $docRoot . '/images/university-logos/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Cannot create upload directory.']);
        exit;
    }
}

$destPath = $uploadDir . $filename;
$webPath  = '/images/university-logos/' . $filename;

// Move file
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file.']);
    exit;
}

echo json_encode([
    'success' => true,
    'url' => $webPath,
    'filename' => $filename,
]);
