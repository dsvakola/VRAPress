<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (is_post()) {
    verify_csrf();
    try {
        if (!isset($_FILES['file'])) {
            throw new RuntimeException('No file selected.');
        }
        $files = normalize_upload_files($_FILES['file']);
        if (!$files) {
            throw new RuntimeException('No file selected.');
        }
        $count = 0;
        foreach ($files as $f) {
            if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            handle_media_upload($f, current_admin_id());
            $count++;
        }
        flash('success', $count ? ('Uploaded ' . $count . ' file(s).') : 'No files uploaded.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect(admin_url('/media/index.php'));
}

$mediaItems = db()->query('SELECT * FROM media ORDER BY created_at DESC LIMIT 100')->fetchAll();
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
    <div class="toolbar">
        <h2>Media Library</h2>
        <span class="muted">Allowed: JPG, PNG, WEBP, GIF, PDF up to <?= (int)UPLOAD_MAX_MB ?> MB</span>
    </div>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-actions">
            <input type="file" name="file[]" accept="image/*,.pdf" multiple required>
            <button class="btn" type="submit">Upload</button>
        </div>
    </form>
    <div class="help-text">Inside the editor, use <strong>Upload</strong> to insert new media at the cursor or <strong>Library</strong> to insert existing media.</div>
</div>

<div class="media-grid">
    <?php foreach ($mediaItems as $item): ?>
        <div class="media-item">
            <div class="media-thumb">
                <?php if (str_starts_with($item['mime_type'], 'image/')): ?>
                    <img src="<?= e($item['file_url']) ?>" alt="">
                <?php else: ?>
                    <div class="muted">PDF</div>
                <?php endif; ?>
            </div>
            <div class="media-body">
                <div><strong><?= e($item['original_name']) ?></strong></div>
                <div class="small muted mono media-url" style="margin:6px 0;"><?= e($item['file_url']) ?></div>
                <div class="small muted"><?= e($item['mime_type']) ?> · <?= e(human_filesize((int)$item['file_size'])) ?></div>
                <div class="form-actions" style="margin-top:10px;">
                    <a class="btn light" href="<?= e($item['file_url']) ?>" target="_blank">Open</a>
                    <button class="btn light js-copy-url" type="button" data-url="<?= e($item['file_url']) ?>">Copy URL</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<script>
document.querySelectorAll('.js-copy-url').forEach(btn => {
  btn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(btn.dataset.url || '');
      btn.textContent = 'Copied';
      setTimeout(() => btn.textContent = 'Copy URL', 1200);
    } catch (e) {
      alert('Could not copy URL.');
    }
  });
});
</script>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
