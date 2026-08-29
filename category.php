<?php
require_once __DIR__ . '/includes/functions.php';
$slug = trim($_GET['slug'] ?? '');
$stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
$category = $stmt->fetch();
if (!$category) {
    http_response_code(404);
    exit('Category not found.');
}
$postsStmt = db()->prepare("SELECT title, slug, excerpt, published_at FROM posts WHERE category_id = ? AND status = 'published' ORDER BY published_at DESC");
$postsStmt->execute([(int)$category['id']]);
$posts = $postsStmt->fetchAll();
?>
<?php $pageTitle = $category['name'] . ' - ' . site_title(); require_once __DIR__ . '/includes/public_header.php'; ?>
    <h1>Category: <?= e($category['name']) ?></h1>
    <?php if (!$posts): ?>
        <p class="muted">No published posts in this category yet.</p>
    <?php endif; ?>
    <?php foreach ($posts as $post): ?>
        <div class="front-card" style="margin-bottom:16px;">
            <h3 style="margin-bottom:6px;"><a href="<?= public_post_url($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
            <div class="muted small"><?= e(date('d M Y', strtotime($post['published_at']))) ?></div>
            <?php if (!empty($post['excerpt'])): ?><p><?= e($post['excerpt']) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
