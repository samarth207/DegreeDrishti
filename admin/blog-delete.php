<?php
/**
 * Blog Delete Handler
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/blogs.php');
    exit;
}

if (!validateCsrf($_POST['_token'] ?? '')) {
    setFlash('danger', 'Invalid security token. Please try again.');
    header('Location: /admin/blogs.php');
    exit;
}

$blogId = (int)($_POST['blog_id'] ?? 0);
if ($blogId < 1) {
    header('Location: /admin/blogs.php');
    exit;
}

try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare('DELETE FROM blogs WHERE id = ?');
    $stmt->execute([$blogId]);
    setFlash('success', 'Blog post deleted successfully.');
} catch (Exception $e) {
    setFlash('danger', 'Failed to delete blog post.');
}

header('Location: /admin/blogs.php');
exit;
