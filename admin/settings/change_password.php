<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$error = '';
if (is_post()) {
    verify_csrf();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([current_admin_id()]);
    $hash = (string)$stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $update = db()->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $update->execute([$newHash, current_admin_id()]);
        flash('success', 'Password changed successfully.');
        redirect(admin_url('/settings/change_password.php'));
    }
}
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card" style="max-width:720px;">
    <h2>Change Password</h2>
    <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Current Password</label>
        <input type="password" name="current_password" required>
        <label>New Password</label>
        <input type="password" name="new_password" required>
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>
        <div class="help-text">Use at least 8 characters. After changing it, use the new password for future logins.</div>
        <br>
        <button class="btn" type="submit">Update Password</button>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
