<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$status = $_GET['status'] ?? 'pending';
$allowed = ['pending','approved','spam','deleted'];
if (!in_array($status, $allowed, true)) $status = 'pending';

$counts = ['pending'=>0,'approved'=>0,'spam'=>0,'deleted'=>0];
try {
    $rows = db()->query('SELECT status, COUNT(*) AS c FROM comments WHERE parent_id IS NULL GROUP BY status')->fetchAll();
    foreach ($rows as $r) {
        $counts[$r['status']] = (int)$r['c'];
    }
} catch (Throwable $e) {
    // comments table might not exist yet
}

$comments = [];
try {
    $stmt = db()->prepare("SELECT c.*, p.title AS post_title, p.slug AS post_slug FROM comments c JOIN posts p ON p.id = c.post_id WHERE c.parent_id IS NULL AND c.status = ? ORDER BY c.created_at DESC LIMIT 200");
    $stmt->execute([$status]);
    $comments = $stmt->fetchAll();
} catch (Throwable $e) {
    $comments = [];
}

require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
  <div class="toolbar">
    <h2>Comments</h2>
    <span class="muted">Moderate visitor comments and reply as admin.</span>
  </div>

  <div class="form-actions" style="gap:8px; flex-wrap:wrap;">
    <?php foreach ($counts as $k => $c): ?>
      <a class="btn light <?= $status === $k ? 'active' : '' ?>" href="<?= admin_url('/comments/list.php?status=' . $k) ?>">
        <?= ucfirst($k) ?> (<?= (int)$c ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$comments): ?>
    <p class="muted" style="margin-top:12px;">No comments in this queue.</p>
  <?php else: ?>
    <table class="table" style="margin-top:12px;">
      <thead>
        <tr>
          <th>Post</th>
          <th>Name</th>
          <th>Comment</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($comments as $c): ?>
        <tr>
          <td>
            <strong><?= e($c['post_title']) ?></strong><br>
            <a class="small" href="<?= public_post_url($c['post_slug']) ?>" target="_blank">View Post</a>
          </td>
          <td>
            <?= e($c['name']) ?>
            <?php if (!empty($c['email'])): ?><div class="small muted"><?= e($c['email']) ?></div><?php endif; ?>
          </td>
          <td style="max-width:420px;">
            <div style="white-space:pre-wrap;"><?= e($c['comment_text']) ?></div>
            <div class="small muted">IP: <?= e($c['ip_address'] ?? '') ?></div>
          </td>
          <td class="muted small"><?= e(date('d M Y, h:i A', strtotime($c['created_at']))) ?></td>
          <td>
            <div class="form-actions" style="gap:6px;">
              <?php if ($c['status'] !== 'approved'): ?>
                <form method="post" action="<?= admin_url('/comments/action.php') ?>" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <input type="hidden" name="do" value="approve">
                  <button class="btn" type="submit">Approve</button>
                </form>
              <?php endif; ?>
              <a class="btn light" href="<?= admin_url('/comments/reply.php?id=' . (int)$c['id']) ?>">Reply</a>
              <form method="post" action="<?= admin_url('/comments/action.php') ?>" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="do" value="spam">
                <button class="btn light" type="submit">Spam</button>
              </form>
              <form method="post" action="<?= admin_url('/comments/action.php') ?>" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="do" value="delete">
                <button class="btn light" type="submit" onclick="return confirm('Delete this comment?');">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
