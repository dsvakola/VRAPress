<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(admin_url('/comments/list.php'));
}

$stmt = db()->prepare("SELECT c.*, p.title AS post_title, p.slug AS post_slug FROM comments c JOIN posts p ON p.id = c.post_id WHERE c.id = ? LIMIT 1");
$stmt->execute([$id]);
$comment = $stmt->fetch();
if (!$comment) {
    flash('error', 'Comment not found.');
    redirect(admin_url('/comments/list.php'));
}

if (is_post()) {
    verify_csrf();
    $reply = trim($_POST['reply_text'] ?? '');
    $approveOriginal = isset($_POST['approve_original']);
    if ($reply === '' || mb_strlen($reply) < 2) {
        flash('error', 'Reply text is required.');
        redirect(admin_url('/comments/reply.php?id=' . $id));
    }
    try {
        if ($approveOriginal && $comment['status'] !== 'approved') {
            $up = db()->prepare("UPDATE comments SET status = 'approved', approved_at = NOW() WHERE id = ?");
            $up->execute([$id]);
        }
        $ins = db()->prepare("INSERT INTO comments (post_id, parent_id, is_admin_reply, name, email, comment_text, status, ip_address, user_agent, created_at, approved_at) VALUES (?, ?, 1, ?, NULL, ?, 'approved', NULL, NULL, NOW(), NOW())");
        $ins->execute([
            (int)$comment['post_id'],
            $id,
            (string)(current_admin()['name'] ?? 'Admin'),
            $reply,
        ]);
        flash('success', 'Reply posted.');
        redirect(admin_url('/comments/list.php?status=approved'));
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect(admin_url('/comments/reply.php?id=' . $id));
    }
}

// Fetch existing replies
$replies = [];
try {
    $rst = db()->prepare("SELECT * FROM comments WHERE parent_id = ? ORDER BY created_at ASC");
    $rst->execute([$id]);
    $replies = $rst->fetchAll();
} catch (Throwable $e) {
    $replies = [];
}

require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
  <div class="toolbar">
    <h2>Reply to Comment</h2>
    <a class="btn light" href="<?= admin_url('/comments/list.php') ?>">Back</a>
  </div>

  <div class="front-card" style="margin-top:12px;">
    <div class="muted small">Post: <a href="<?= public_post_url($comment['post_slug']) ?>" target="_blank"><?= e($comment['post_title']) ?></a></div>
    <div><strong><?= e($comment['name']) ?></strong> <span class="muted small">· <?= e(date('d M Y, h:i A', strtotime($comment['created_at']))) ?></span></div>
    <div style="margin-top:6px; white-space:pre-wrap;"><?= e($comment['comment_text']) ?></div>
    <div class="small muted" style="margin-top:6px;">Status: <?= e($comment['status']) ?></div>
  </div>

  <?php if ($replies): ?>
    <h3 style="margin-top:16px;">Existing Replies</h3>
    <?php foreach ($replies as $r): ?>
      <div style="margin-top:10px; padding:10px 12px; border-left:4px solid #c7d2fe; background:#f8fafc; border-radius:10px;">
        <div><strong><?= e($r['name']) ?></strong> <span class="muted small">· <?= e(date('d M Y, h:i A', strtotime($r['created_at']))) ?></span></div>
        <div style="margin-top:6px; white-space:pre-wrap;"><?= e($r['comment_text']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <h3 style="margin-top:18px;">Write Reply</h3>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <textarea name="reply_text" style="min-height:140px" required></textarea>
    <label style="display:flex; gap:8px; align-items:center; margin-top:10px;">
      <input type="checkbox" name="approve_original" <?= $comment['status'] === 'approved' ? 'checked disabled' : '' ?>> Approve original comment (recommended)
    </label>
    <br>
    <button class="btn" type="submit">Post Reply</button>
  </form>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
