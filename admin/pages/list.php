<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare('DELETE FROM pages WHERE id = ?');
    $stmt->execute([$id]);
    flash('success', 'Page deleted.');
    redirect(admin_url('/pages/list.php'));
}

$pages = db()->query('SELECT * FROM pages ORDER BY updated_at DESC')->fetchAll();
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
    <div class="toolbar">
        <h2>Pages</h2>
        <div class="form-actions">
            <a class="btn secondary" href="<?= admin_url('/pages/import.php') ?>">Import Pages CSV</a>
            <a class="btn" href="<?= admin_url('/pages/add.php') ?>">Add Page</a>
        </div>
    </div>
    <table>
        <tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th>Actions</th></tr>
        <?php foreach ($pages as $page): ?>
            <tr>
                <td><?= e($page['title']) ?></td>
                <td class="mono"><?= e($page['slug']) ?></td>
                <td><span class="status-badge <?= e($page['status']) ?>"><?= e(ucfirst($page['status'])) ?></span></td>
                <td><?= e($page['updated_at']) ?></td>
                <td>
                    <a href="<?= admin_url('/pages/edit.php?id=' . (int)$page['id']) ?>">Edit</a> |
                    <a href="<?= public_page_url($page['slug']) ?>" target="_blank">View</a> |
                    <form method="post" class="inline-form" onsubmit="return confirm('Delete this page?')">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$page['id'] ?>">
                        <button type="submit" style="background:none;border:none;color:#b42318;padding:0;cursor:pointer;">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
