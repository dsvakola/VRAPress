<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$categories = fetch_categories();

$title = '';
$slugInput = '';
$excerpt = '';
$contentValue = '';
$status = 'draft';
$meta_title = '';
$meta_description = '';
$category_id = '';
$published_at = date('Y-m-d\\TH:i');

if (is_post()) {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    $slugInput = trim($_POST['slug'] ?? '');
    $slug = unique_slug('posts', $slugInput !== '' ? $slugInput : $title);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $contentValue = $_POST['content'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
    $published_at = trim($_POST['published_at'] ?? '');
    $published_atSql = $published_at !== '' ? str_replace('T', ' ', $published_at) . ':00' : date('Y-m-d H:i:s');

    if ($excerpt === '') {
        $excerpt = create_excerpt($contentValue);
    }

    $stmt = db()->prepare('INSERT INTO posts (category_id, title, slug, excerpt, content, status, meta_title, meta_description, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute([$category_id, $title, $slug, $excerpt, $contentValue, $status, $meta_title, $meta_description, $published_atSql]);
    flash('success', 'Post created.');
    redirect(admin_url('/posts/list.php'));
}
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
    <h2>Add Post</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="grid-3">
            <div>
                <label>Title</label>
                <input type="text" name="title" value="<?= e($title) ?>" required>
            </div>
            <div>
                <label>Slug</label>
                <input type="text" name="slug" value="<?= e($slugInput) ?>">
            </div>
            <div>
                <label>Category</label>
                <select name="category_id">
                    <option value="">Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['id'] ?>" <?= (int)$category_id === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <label>Excerpt</label>
        <textarea name="excerpt" style="min-height:100px"><?= e($excerpt) ?></textarea>
        <label>Content</label>
        <div class="js-vsa-editor vsa-editor" data-upload-url="<?= admin_url('/media/upload_ajax.php') ?>" data-library-url="<?= admin_url('/media/list_json.php') ?>" data-csrf="<?= e(csrf_token()) ?>">
            <div class="vsa-editor-toolbar">
                <label class="vsa-style-control"><span>Styles</span><select class="vsa-editor-style-select" data-style-menu aria-label="Apply content style"><option value="">Choose style…</option><option value="paragraph">Paragraph</option><option value="lead">Lead paragraph</option><option value="note">Note box</option><option value="tip">Tip box</option><option value="warning">Warning box</option><option value="success">Success box</option><option value="quote">Quotation</option><option value="code">Code block</option></select></label>
                <button type="button" class="js-open-style-guide">Style Guide</button>
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
        <div class="grid-3">
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
            <div>
                <label>Publish Date</label>
                <input type="datetime-local" name="published_at" value="<?= e($published_at) ?>">
            </div>
        </div>
        <label>Meta Description</label>
        <textarea name="meta_description" style="min-height:100px"><?= e($meta_description) ?></textarea>
        <br><br>
        <button class="btn" type="submit">Save Post</button>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
