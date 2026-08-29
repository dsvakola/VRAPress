<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$title = '';
$slugInput = '';
$contentValue = '';
$status = 'draft';
$meta_title = '';
$meta_description = '';

if (is_post()) {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    $slugInput = trim($_POST['slug'] ?? '');
    $slug = unique_slug('pages', $slugInput !== '' ? $slugInput : $title);
    $contentValue = $_POST['content'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');

    $stmt = db()->prepare('INSERT INTO pages (title, slug, content, status, meta_title, meta_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute([$title, $slug, $contentValue, $status, $meta_title, $meta_description]);
    flash('success', 'Page created.');
    redirect(admin_url('/pages/list.php'));
}
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
    <h2>Add Page</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="grid-2">
            <div>
                <label>Title</label>
                <input type="text" name="title" value="<?= e($title) ?>" required>
            </div>
            <div>
                <label>Slug</label>
                <input type="text" name="slug" placeholder="about-us" value="<?= e($slugInput) ?>">
            </div>
        </div>
        <label>Content</label>
        <div class="js-vsa-editor vsa-editor" data-upload-url="<?= admin_url('/media/upload_ajax.php') ?>" data-library-url="<?= admin_url('/media/list_json.php') ?>" data-csrf="<?= e(csrf_token()) ?>">
            <div class="vsa-editor-toolbar">
                <button type="button" data-cmd="bold"><strong>B</strong></button>
                <button type="button" data-cmd="italic"><em>I</em></button>
                <button type="button" data-cmd="underline"><u>U</u></button>
                <button type="button" data-cmd="formatBlock" data-value="h2">H2</button>
                <button type="button" data-cmd="formatBlock" data-value="h3">H3</button>
                <button type="button" data-cmd="insertUnorderedList">• List</button>
                <button type="button" data-cmd="insertOrderedList">1. List</button>
                <button type="button" data-cmd="createLink">Link</button>
                <button type="button" data-cmd="removeFormat">Clear</button>
                <button type="button" class="js-open-upload">Upload</button>
                <button type="button" class="js-open-library">Library</button>
                <button type="button" data-image-size="small">Small</button>
                <button type="button" data-image-size="medium">Medium</button>
                <button type="button" data-image-size="large">Large</button>
                <button type="button" data-image-size="full">Full</button>
                <button type="button" data-image-align="left">Left</button>
                <button type="button" data-image-align="center">Center</button>
                <button type="button" data-image-align="right">Right</button>
                <button type="button" data-image-alt="1">Alt</button>
                <input class="vsa-editor-upload" type="file" accept="image/*,.pdf" multiple hidden>
            </div>
            <div class="vsa-editor-area" contenteditable="true"></div>
            <textarea class="vsa-editor-source" name="content" style="display:none;"><?= e($contentValue) ?></textarea>
            <div class="vsa-media-modal">
                <div class="vsa-media-modal-card">
                    <div class="toolbar" style="margin-bottom:12px;">
                        <h3 style="margin:0;">Insert from Media Library</h3>
                        <button class="btn light js-close-media-modal" type="button">Close</button>
                    </div>
                    <div class="vsa-media-modal-body"></div>
                </div>
            </div>
        </div>
        <div class="help-text">Use the toolbar for clean formatting. Images/PDFs upload directly to your local media library.</div>
        <div class="grid-2">
            <div>
                <label>Meta Title</label>
                <input type="text" name="meta_title" value="<?= e($meta_title) ?>">
            </div>
            <div>
                <label>Status</label>
                <select name="status">
                    <?php foreach (post_statuses() as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <label>Meta Description</label>
        <textarea name="meta_description" style="min-height:100px"><?= e($meta_description) ?></textarea>
        <br><br>
        <button class="btn" type="submit">Save Page</button>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
