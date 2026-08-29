<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (!is_post()) {
    redirect(admin_url('/comments/list.php'));
}
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$do = $_POST['do'] ?? '';
if ($id <= 0) {
    flash('error', 'Invalid comment.');
    redirect(admin_url('/comments/list.php'));
}

try {
    if ($do === 'approve') {
        $stmt = db()->prepare("UPDATE comments SET status = 'approved', approved_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Comment approved.');
    } elseif ($do === 'spam') {
        $stmt = db()->prepare("UPDATE comments SET status = 'spam' WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Comment marked as spam.');
    } elseif ($do === 'delete') {
        $stmt = db()->prepare("UPDATE comments SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Comment deleted.');
    } else {
        flash('error', 'Unknown action.');
    }
} catch (Throwable $e) {
    flash('error', $e->getMessage());
}

redirect(admin_url('/comments/list.php'));
