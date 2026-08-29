<?php
require_once __DIR__ . '/includes/functions.php';
$slug = trim($_GET['slug'] ?? '');
$stmt = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();
if (!$post) {
    http_response_code(404);
    exit('Post not found.');
}
// Handle visitor comment submission
$commentNotice = '';
if (is_post() && isset($_POST['comment_submit'])) {
    try {
        verify_csrf();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $text = trim($_POST['comment_text'] ?? '');
        $honeypot = trim($_POST['website'] ?? '');
        $ts = (int)($_POST['ts'] ?? 0);
        $now = time();

        if ($honeypot !== '') {
            throw new RuntimeException('Spam detected.');
        }
        if ($ts <= 0 || ($now - $ts) < 3) {
            throw new RuntimeException('Please wait a moment and submit again.');
        }
        if ($name === '' || mb_strlen($name) < 2) {
            throw new RuntimeException('Please enter your name.');
        }
        if ($text === '' || mb_strlen($text) < 5) {
            throw new RuntimeException('Please enter a comment.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email (or leave it blank).');
        }

        // Basic rate limit: max 5 comments per IP per 10 minutes
        $ip = client_ip();
        $stmt = db()->prepare("SELECT COUNT(*) FROM comments WHERE ip_address = ? AND created_at >= (NOW() - INTERVAL 10 MINUTE)");
        $stmt->execute([$ip]);
        if ((int)$stmt->fetchColumn() >= 5) {
            throw new RuntimeException('Too many comments from your network. Please try later.');
        }

        $ins = db()->prepare("INSERT INTO comments (post_id, parent_id, is_admin_reply, name, email, comment_text, status, ip_address, user_agent, created_at) VALUES (?, NULL, 0, ?, ?, ?, 'pending', ?, ?, NOW())");
        $ins->execute([
            (int)$post['id'],
            $name,
            $email !== '' ? $email : null,
            $text,
            $ip,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
        $commentNotice = 'Thanks! Your comment is submitted for approval.';
    } catch (Throwable $e) {
        $commentNotice = $e->getMessage();
    }
}

// Fetch approved comments + admin replies
$comments = [];
$repliesByParent = [];
try {
    $cStmt = db()->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' AND parent_id IS NULL ORDER BY created_at ASC");
    $cStmt->execute([(int)$post['id']]);
    $comments = $cStmt->fetchAll();
    if ($comments) {
        $ids = array_map(fn($c) => (int)$c['id'], $comments);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $rStmt = db()->prepare("SELECT * FROM comments WHERE status = 'approved' AND parent_id IN ($in) ORDER BY created_at ASC");
        $rStmt->execute($ids);
        $replies = $rStmt->fetchAll();
        foreach ($replies as $r) {
            $pid = (int)$r['parent_id'];
            $repliesByParent[$pid] = $repliesByParent[$pid] ?? [];
            $repliesByParent[$pid][] = $r;
        }
    }
} catch (Throwable $e) {
    // If comments table not installed yet, ignore.
}

?>
<?php $pageTitle = ($post['meta_title'] ?: $post['title']); require_once __DIR__ . '/includes/public_header.php'; ?>
<article class="vrp-article">
    <span class="vrp-kicker">Article</span>
    <h1><?= e($post['title']) ?></h1>
    <div class="vrp-meta"><span>Category:
        <?php if (!empty($post['category_slug'])): ?>
            <a href="<?= public_category_url($post['category_slug']) ?>"><?= e($post['category_name']) ?></a>
        <?php else: ?>
            General
        <?php endif; ?>
        </span><span>Published: <?= e(date('d M Y, h:i A', strtotime($post['published_at']))) ?></span></div>
    <div class="content-html"><?= $post['content'] ?></div>

    <section style="margin-top:42px;">
        <hr>
        <h2>Comments</h2>
        <?php if ($commentNotice): ?>
            <div class="flash info"><?= e($commentNotice) ?></div>
        <?php endif; ?>

        <?php if (!$comments): ?>
            <p class="muted">No comments yet.</p>
        <?php else: ?>
            <div class="vrp-post-list">
                <?php foreach ($comments as $c): ?>
                    <div class="vrp-comment">
                        <div><strong><?= e($c['name']) ?></strong> <span class="muted small">· <?= e(date('d M Y, h:i A', strtotime($c['created_at']))) ?></span></div>
                        <div style="margin-top:6px; white-space:pre-wrap;"><?= e($c['comment_text']) ?></div>

                        <?php foreach (($repliesByParent[(int)$c['id']] ?? []) as $r): ?>
                            <div class="vrp-comment-reply">
                                <div><strong><?= e($r['name']) ?></strong> <span class="muted small">· Reply</span></div>
                                <div style="margin-top:6px; white-space:pre-wrap;"><?= e($r['comment_text']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <hr>
        <h3>Leave a comment</h3>
        <form method="post" class="grid-2">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="ts" value="<?= (int)time() ?>">
            <div style="display:none;">
                <label>Website</label>
                <input type="text" name="website" value="">
            </div>
            <div>
                <label>Name</label>
                <input type="text" name="name" required>
            </div>
            <div>
                <label>Email (optional)</label>
                <input type="email" name="email">
            </div>
            <div style="grid-column:1/-1;">
                <label>Comment</label>
                <textarea name="comment_text" required style="min-height:110px;"></textarea>
            </div>
            <div style="grid-column:1/-1;">
                <button class="btn" type="submit" name="comment_submit" value="1">Submit Comment</button>
                <span class="muted small" style="margin-left:10px;">New comments are shown after approval.</span>
            </div>
        </form>
    </section>
</article>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
