<?php
/**
 * Admin Authentication & Helper Functions
 * Include this at the top of every admin page via: require_once __DIR__ . '/auth.php';
 */

// Configure secure session before starting
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Root path constants
define('ADMIN_ROOT', dirname(__DIR__));
define('SITE_ROOT',  dirname(ADMIN_ROOT));

// Include database config
require_once SITE_ROOT . '/api/config.php';

// -------------------------------------------------------
// Auth helpers
// -------------------------------------------------------

function requireLogin(): void {
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: /admin/index.php');
        exit;
    }
    // Idle-timeout: 8 hours
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 28800) {
        session_unset();
        session_destroy();
        header('Location: /admin/index.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function getCurrentAdmin(): array {
    return [
        'id'        => $_SESSION['admin_id']       ?? 0,
        'username'  => $_SESSION['admin_username'] ?? '',
        'full_name' => $_SESSION['admin_fullname'] ?? 'Admin',
        'email'     => $_SESSION['admin_email']    ?? '',
        'role'      => $_SESSION['admin_role']     ?? 'admin',
    ];
}

// -------------------------------------------------------
// CSRF helpers
// -------------------------------------------------------

function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf(?string $token): bool {
    return !empty($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// -------------------------------------------------------
// Output helpers
// -------------------------------------------------------

function esc(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// -------------------------------------------------------
// Flash messages
// -------------------------------------------------------

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// -------------------------------------------------------
// Slug helper
// -------------------------------------------------------

function makeSlug(string $text): string {
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// -------------------------------------------------------
// Estimate read time (words / 200 wpm)
// -------------------------------------------------------

function estimateReadTime(string $content): int {
    $text  = strip_tags($content);
    $words = str_word_count($text);
    $time  = max(1, (int)round($words / 200));
    return $time;
}
