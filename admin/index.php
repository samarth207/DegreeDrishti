<?php
/**
 * Admin Login Page
 * DegreeDrishti Blog CMS
 */

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

// Already logged in → redirect to dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/../api/config.php';

$error   = '';
$timeout = isset($_GET['timeout']);
$logout  = isset($_GET['logout']);

// CSRF token for login form
if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

// -------------------------------------------------------
// Handle POST (login attempt)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate-limit: max 5 attempts per 10 minutes
    if (!isset($_SESSION['login_attempts']))       $_SESSION['login_attempts'] = 0;
    if (!isset($_SESSION['login_attempt_time']))   $_SESSION['login_attempt_time'] = time();

    if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['login_attempt_time']) < 600) {
        $error = 'Too many failed attempts. Please wait 10 minutes.';
    } else {
        // Validate CSRF
        $csrfOk = !empty($_POST['_token']) &&
                  hash_equals($_SESSION['login_csrf'], $_POST['_token']);

        if (!$csrfOk) {
            $error = 'Invalid request. Please refresh and try again.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password']      ?? '';

            if ($username === '' || $password === '') {
                $error = 'Please enter username and password.';
            } else {
                try {
                    $pdo  = getDBConnection();
                    $stmt = $pdo->prepare(
                        'SELECT id, username, password, email, full_name, role
                           FROM admin_users
                          WHERE username = ?
                          LIMIT 1'
                    );
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        // Successful login
                        session_regenerate_id(true);
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id']        = $user['id'];
                        $_SESSION['admin_username']  = $user['username'];
                        $_SESSION['admin_fullname']  = $user['full_name'] ?: $user['username'];
                        $_SESSION['admin_email']     = $user['email'];
                        $_SESSION['admin_role']      = $user['role'];
                        $_SESSION['last_activity']   = time();
                        $_SESSION['login_attempts']  = 0;
                        unset($_SESSION['login_csrf']);
                        header('Location: /admin/dashboard.php');
                        exit;
                    } else {
                        $_SESSION['login_attempts']++;
                        $_SESSION['login_attempt_time'] = time();
                        $error = 'Invalid username or password.';
                    }
                } catch (Exception $e) {
                    $error = 'Database error. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — DegreeDrishti</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <span>DegreeDrishti</span>
      <small>Blog Admin Panel</small>
    </div>
    <h2>Sign In</h2>

    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php elseif ($timeout): ?>
      <div class="alert alert-warning"><i class="fas fa-clock"></i> Session expired. Please sign in again.</div>
    <?php elseif ($logout): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> You have been signed out.</div>
    <?php endif; ?>

    <form method="POST" action="/admin/index.php" autocomplete="off">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['login_csrf']) ?>">

      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control"
               placeholder="Enter username" autocomplete="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="Enter password" autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px;">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <p style="text-align:center;margin-top:20px;font-size:12px;color:#9ca3af;">
      First time? <a href="/admin/setup.php" style="color:#2C3E50;font-weight:600;">Run Setup</a>
    </p>
  </div>
</div>
</body>
</html>
