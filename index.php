<?php
require_once __DIR__ . '/includes/functions.php';
$pages = db()->query("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY updated_at DESC LIMIT 20")->fetchAll();
$posts = db()->query("SELECT title, slug, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 10")->fetchAll();
$categories = db()->query('SELECT name, slug FROM categories ORDER BY name ASC LIMIT 12')->fetchAll();
?>
<?php $pageTitle = site_title(); require_once __DIR__ . '/includes/public_header.php'; ?>

    <div class="front-grid">
        <section class="front-card">
            <span class="vrp-kicker">Latest publishing</span>
            <h1>Recent Posts</h1>
            <div class="vrp-post-list">
                <?php foreach ($posts as $post): ?>
                    <article class="vrp-entry-card">
                        <div class="vrp-meta"><span><?= e(date('d M Y', strtotime($post['published_at']))) ?></span></div>
                        <h2><a href="<?= public_post_url($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <aside class="front-card">
            <span class="vrp-kicker">Explore</span>
            <h2>Pages</h2>
            <ul class="vrp-link-list">
                <?php foreach ($pages as $page): ?>
                    <li><a href="<?= public_page_url($page['slug']) ?>"><?= e($page['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <h2>Categories</h2>
            <ul class="vrp-link-list">
                <?php foreach ($categories as $category): ?>
                    <li><a href="<?= public_category_url($category['slug']) ?>"><?= e($category['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </aside>
    </div>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
