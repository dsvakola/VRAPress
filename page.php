<?php
require_once __DIR__ . '/includes/functions.php';
$slug = trim($_GET['slug'] ?? '');
$stmt = db()->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();
if (!$page) {
    http_response_code(404);
    exit('Page not found.');
}
?>
<?php $pageTitle = ($page['meta_title'] ?: $page['title']); require_once __DIR__ . '/includes/public_header.php'; ?>
<article class="vrp-article">
    <span class="vrp-kicker">Page</span>
    <h1><?= e($page['title']) ?></h1>
    <div class="content-html"><?= $page['content'] ?></div>
</article>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
