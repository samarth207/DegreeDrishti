<?php
/**
 * Image Upload Handler
 * Used by TinyMCE content editor (images_upload_url)
 * Also used for direct AJAX uploads from the admin panel
 *
 * TinyMCE expects response: { "location": "URL_TO_IMAGE" }
 * Direct upload expects:    { "success": true, "url": "URL" }
 */

// Auth check: admin must be logged in
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
    session_start();
}
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

header('Content-Type: application/json');

// Determine upload type (tinymce content image vs other)
$isTinyMCE = isset($_FILES['file']);
$fileKey   = $isTinyMCE ? 'file' : ($_FILES ? array_key_first($_FILES) : '');

if (!$fileKey || empty($_FILES[$fileKey]['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES[$fileKey];

// -------------------------------------------------------
// Validate upload error
// -------------------------------------------------------
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
    echo json_encode(['error' => $uploadErrors[$file['error']] ?? 'Upload error.']);
    exit;
}

// -------------------------------------------------------
// Validate MIME type (do NOT trust $_FILES['type'])
// -------------------------------------------------------
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(415);
    echo json_encode(['error' => 'Only JPG, PNG and WebP images are allowed.']);
    exit;
}

// -------------------------------------------------------
// Validate size (max 5 MB)
// -------------------------------------------------------
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['error' => 'File size must be under 5 MB.']);
    exit;
}

// -------------------------------------------------------
// Build safe filename
// -------------------------------------------------------
$extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$ext      = $extMap[$mime];
$prefix   = $isTinyMCE ? 'content' : 'upload';
$filename = $prefix . '-' . bin2hex(random_bytes(8)) . '.' . $ext;

// -------------------------------------------------------
// Ensure upload directory exists
// -------------------------------------------------------
$docRoot   = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$uploadDir = $docRoot . '/images/blog/uploads/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot create upload directory.']);
        exit;
    }
}

$destPath = $uploadDir . $filename;
$webPath  = '/images/blog/uploads/' . $filename;

// -------------------------------------------------------
// Move file
// -------------------------------------------------------
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file.']);
    exit;
}

// -------------------------------------------------------
// Record in blog_media (optional, best-effort)
// -------------------------------------------------------
try {
    require_once __DIR__ . '/config.php';
    $pdo    = getDBConnection();
    $blogId = (int)($_POST['blog_id'] ?? 0);
    $alt    = trim($_POST['alt_text'] ?? '');
    $pdo->prepare(
        'INSERT INTO blog_media (blog_id, filename, original_name, file_path, web_path,
                                  file_size, mime_type, alt_text, uploaded_by)
         VALUES (?,?,?,?,?,?,?,?,?)'
    )->execute([
        $blogId ?: null, $filename, $file['name'],
        $destPath, $webPath,
        $file['size'], $mime, $alt,
        $_SESSION['admin_id'] ?? null,
    ]);
} catch (Exception $e) {
    // Non-critical: file is already saved, just log silently
}

// -------------------------------------------------------
// Return response (TinyMCE format: location key)
// -------------------------------------------------------
echo json_encode([
    'location' => $webPath,   // TinyMCE reads this
    'url'      => $webPath,   // our own JS reads this
    'success'  => true,
    'filename' => $filename,
]);
