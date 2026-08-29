<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin_header.php';

$pageCount = (int)db()->query('SELECT COUNT(*) FROM pages')->fetchColumn();
$postCount = (int)db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$catCount = (int)db()->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$draftCount = (int)db()->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn();
$mediaCount = (int)db()->query('SELECT COUNT(*) FROM media')->fetchColumn();

$recentPages = db()->query('SELECT id, title, slug, updated_at FROM pages ORDER BY updated_at DESC LIMIT 5')->fetchAll();
$recentPosts = db()->query('SELECT id, title, slug, updated_at FROM posts ORDER BY updated_at DESC LIMIT 5')->fetchAll();
?>
<div class="cards">
    <div class="card"><h3><?= $pageCount ?></h3><div class="muted">Pages</div></div>
    <div class="card"><h3><?= $postCount ?></h3><div class="muted">Posts</div></div>
    <div class="card"><h3><?= $catCount ?></h3><div class="muted">Categories</div></div>
    <div class="card"><h3><?= $draftCount ?></h3><div class="muted">Draft Posts</div></div>
    <div class="card"><h3><?= $mediaCount ?></h3><div class="muted">Media Files</div></div>
</div>

<div class="card notice">
    <strong>Foundation Pack v2</strong><br>
    Clean URLs, media upload, change password, and a built-in editor are now included.
</div>

<div class="grid-2">
    <div class="card">
        <div class="toolbar"><h2>Recent Pages</h2><a class="btn" href="<?= admin_url('/pages/add.php') ?>">Add Page</a></div>
        <table>
            <tr><th>Title</th><th>Slug</th><th>Updated</th></tr>
            <?php foreach ($recentPages as $row): ?>
                <tr>
                    <td><a href="<?= admin_url('/pages/edit.php?id=' . (int)$row['id']) ?>"><?= e($row['title']) ?></a></td>
                    <td class="mono"><?= e($row['slug']) ?></td>
                    <td><?= e($row['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <div class="toolbar"><h2>Recent Posts</h2><a class="btn" href="<?= admin_url('/posts/add.php') ?>">Add Post</a></div>
        <table>
            <tr><th>Title</th><th>Slug</th><th>Updated</th></tr>
            <?php foreach ($recentPosts as $row): ?>
                <tr>
                    <td><a href="<?= admin_url('/posts/edit.php?id=' . (int)$row['id']) ?>"><?= e($row['title']) ?></a></td>
                    <td class="mono"><?= e($row['slug']) ?></td>
                    <td><?= e($row['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
