<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    flash('success', 'Post deleted.');
    redirect(admin_url('/posts/list.php'));
}

$posts = db()->query('SELECT p.*, c.name AS category_name FROM posts p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.updated_at DESC')->fetchAll();
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
    <div class="toolbar">
        <h2>Posts</h2>
        <div class="form-actions">
            <a class="btn secondary" href="<?= admin_url('/posts/import.php') ?>">Import Posts CSV</a>
            <a class="btn" href="<?= admin_url('/posts/add.php') ?>">Add Post</a>
        </div>
    </div>
    <table>
        <tr><th>Title</th><th>Category</th><th>Slug</th><th>Status</th><th>Updated</th><th>Actions</th></tr>
        <?php foreach ($posts as $post): ?>
            <tr>
                <td><?= e($post['title']) ?></td>
                <td><?= e($post['category_name'] ?? '—') ?></td>
                <td class="mono"><?= e($post['slug']) ?></td>
                <td><span class="status-badge <?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
                <td><?= e($post['updated_at']) ?></td>
                <td>
                    <a href="<?= admin_url('/posts/edit.php?id=' . (int)$post['id']) ?>">Edit</a> |
                    <a href="<?= public_post_url($post['slug']) ?>" target="_blank">View</a> |
                    <form method="post" class="inline-form" onsubmit="return confirm('Delete this post?')">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
                        <button type="submit" style="background:none;border:none;color:#b42318;padding:0;cursor:pointer;">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
