<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$message = null;
if (is_post()) {
    verify_csrf();
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $message = ['error', 'Upload failed.'];
    } else {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file);
        $count = 0;
        if ($header !== false) {
            while (($row = fgetcsv($file)) !== false) {
                $data = array_combine($header, array_pad($row, count($header), ''));
                if (!$data || trim($data['title'] ?? '') === '') {
                    continue;
                }
                $categoryId = null;
                $categoryName = trim($data['category'] ?? '');
                if ($categoryName !== '') {
                    $find = db()->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
                    $find->execute([$categoryName]);
                    $existing = $find->fetchColumn();
                    if ($existing) {
                        $categoryId = (int)$existing;
                    } else {
                        $catSlug = unique_slug('categories', $categoryName);
                        $insertCat = db()->prepare('INSERT INTO categories (name, slug, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
                        $insertCat->execute([$categoryName, $catSlug]);
                        $categoryId = (int)db()->lastInsertId();
                    }
                }
                $slug = unique_slug('posts', trim($data['slug'] ?? '') !== '' ? $data['slug'] : $data['title']);
                $stmt = db()->prepare('INSERT INTO posts (category_id, title, slug, excerpt, content, status, meta_title, meta_description, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
                $stmt->execute([
                    $categoryId,
                    trim($data['title']),
                    $slug,
                    $data['excerpt'] ?? create_excerpt($data['content'] ?? ''),
                    $data['content'] ?? '',
                    in_array($data['status'] ?? '', ['draft', 'published'], true) ? $data['status'] : 'draft',
                    $data['meta_title'] ?? '',
                    $data['meta_description'] ?? '',
                    trim($data['published_at'] ?? '') !== '' ? $data['published_at'] : date('Y-m-d H:i:s'),
                ]);
                $count++;
            }
        }
        fclose($file);
        $message = ['success', $count . ' posts imported.'];
    }
}
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
    <h2>Import Posts from CSV</h2>
    <p class="muted">CSV columns required: <code>title,slug,category,excerpt,content,status,meta_title,meta_description,published_at</code></p>
    <?php if ($message): ?><div class="flash <?= e($message[0]) ?>"><?= e($message[1]) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="file" name="csv_file" accept=".csv" required>
        <br><br>
        <button class="btn" type="submit">Import Posts</button>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
