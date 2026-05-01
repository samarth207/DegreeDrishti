<?php
/**
 * Admin First-Time Setup
 * Creates the initial admin user.
 * DELETE THIS FILE after running it once.
 */

require_once __DIR__ . '/../api/config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';
    $email    = trim($_POST['email']    ?? '');
    $fullname = trim($_POST['full_name']?? '');

    if (!$username || !$password || !$email) {
        $message = 'All fields are required.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address.';
    } else {
        try {
            $pdo  = getDBConnection();

            // Check if admin user already exists
            $check = $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
            if ($check > 0) {
                $message = 'An admin user already exists. Delete this file immediately.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    'INSERT INTO admin_users (username, password, email, full_name, role)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$username, $hash, $email, $fullname ?: $username, 'admin']);
                $success = true;
                $message = 'Admin user created! <strong>Delete this file (admin/setup.php) now.</strong>';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Setup — DegreeDrishti</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <span>DegreeDrishti</span>
      <small>First-Time Setup</small>
    </div>
    <h2>Create Admin Account</h2>

    <?php if ($message): ?>
      <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>"><?= $message ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" class="form-control" placeholder="e.g. Samarth Admin">
      </div>
      <div class="form-group">
        <label>Username <span class="req">*</span></label>
        <input type="text" name="username" class="form-control" placeholder="admin" required>
      </div>
      <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <input type="email" name="email" class="form-control" placeholder="info@degreedrishti.com" required>
      </div>
      <div class="form-group">
        <label>Password <span class="req">*</span> <small style="color:#9ca3af">(min 8 chars)</small></label>
        <input type="password" name="password" class="form-control" required minlength="8">
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px;">
        <i class="fas fa-user-plus"></i> Create Admin User
      </button>
    </form>
    <?php else: ?>
      <a href="/admin/index.php" class="btn btn-primary btn-full" style="margin-top:12px;">
        <i class="fas fa-sign-in-alt"></i> Go to Login
      </a>
    <?php endif; ?>

    <p style="margin-top:18px;font-size:12px;color:#e53e3e;text-align:center;">
      <i class="fas fa-exclamation-triangle"></i>
      Delete <code>admin/setup.php</code> after completing setup.
    </p>
  </div>
</div>
</body>
</html>
