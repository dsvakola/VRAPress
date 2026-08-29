<?php
require_once __DIR__ . '/../includes/functions.php';

if (current_admin()) {
    redirect(admin_url('/dashboard.php'));
}

$error = '';
if (is_post()) {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, name, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['admin_user'] = $user;
        redirect(admin_url('/dashboard.php'));
    }

    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= e(cms_name()) ?></title>
    <link rel="stylesheet" href="<?= site_url('/assets/css/admin.css') ?>">
    <style>
        body { display:grid; place-items:center; min-height:100vh; }
        .login-box { width:min(440px, 92vw); background:#fff; border:1px solid #d7dce5; border-radius:18px; padding:30px; }
.login-brand { text-align:center; margin-bottom:18px; }
        .login-logo { width:110px; height:110px; object-fit:contain; border-radius:50%; }
    </style>
</head>
<body>
    <form method="post" class="login-box">
        <div class="login-brand">
            <img class="login-logo" src="<?= cms_logo_url() ?>" alt="<?= e(cms_name()) ?>">
            <h2 style="margin:12px 0 0 0;"><?= e(cms_name()) ?> Admin Login</h2>
            <div class="muted small" style="margin-top:6px;">Dashboard for managing your website content</div>
        </div>
        <p class="muted">Default username: <strong>admin</strong> | password: <strong>ChangeMe123!</strong></p>
        <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <div class="form-actions" style="margin-top:12px;">
            <button type="submit" class="btn" style="width:100%;">Login</button>
        </div>
    </form>
</body>
</html>
