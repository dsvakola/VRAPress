<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $slug = trim($_POST['slug'] ?? '');
        $slug = unique_slug('categories', $slug !== '' ? $slug : $name);
        $stmt = db()->prepare('INSERT INTO categories (name, slug, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
        $stmt->execute([$name, $slug]);
        flash('success', 'Category added.');
    }
    redirect(admin_url('/categories/list.php'));
}

$categories = db()->query('SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id) AS post_count FROM categories c ORDER BY c.name ASC')->fetchAll();
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="grid-2">
    <div class="card">
        <h2>Add Category</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Name</label>
            <input type="text" name="name" required>
            <label>Slug (optional)</label>
            <input type="text" name="slug">
            <br><br>
            <button class="btn" type="submit">Save Category</button>
        </form>
    </div>
    <div class="card">
        <h2>Existing Categories</h2>
        <table>
            <tr><th>Name</th><th>Slug</th><th>Posts</th><th>View</th></tr>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= e($category['name']) ?></td>
                    <td class="mono"><?= e($category['slug']) ?></td>
                    <td><?= (int)$category['post_count'] ?></td>
                    <td><a href="<?= public_category_url($category['slug']) ?>" target="_blank">Open</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
