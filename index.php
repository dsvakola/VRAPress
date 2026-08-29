<?php
require_once __DIR__ . '/includes/functions.php';
$pages = db()->query("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY updated_at DESC LIMIT 20")->fetchAll();
$posts = db()->query("SELECT title, slug, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 10")->fetchAll();
$categories = db()->query('SELECT name, slug FROM categories ORDER BY name ASC LIMIT 12')->fetchAll();
?>
<?php $pageTitle = site_title(); require_once __DIR__ . '/includes/public_header.php'; ?>

    <div class="front-grid">
        <div class="front-card">
            <h2>Recent Posts</h2>
            <ul>
                <?php foreach ($posts as $post): ?>
                    <li style="margin-bottom:10px;"><a href="<?= public_post_url($post['slug']) ?>"><?= e($post['title']) ?></a> <small class="muted">(<?= e(date('d M Y', strtotime($post['published_at']))) ?>)</small></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="front-card">
            <h2>Pages</h2>
            <ul>
                <?php foreach ($pages as $page): ?>
                    <li><a href="<?= public_page_url($page['slug']) ?>"><?= e($page['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <h2 style="margin-top:24px;">Categories</h2>
            <ul>
                <?php foreach ($categories as $category): ?>
                    <li><a href="<?= public_category_url($category['slug']) ?>"><?= e($category['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
