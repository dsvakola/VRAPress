<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$title = site_title();
$tagline = site_tagline();

if (is_post()) {
    verify_csrf();
    $title = trim($_POST['site_title'] ?? '');
    $tagline = trim($_POST['site_tagline'] ?? '');
    if ($title === '') {
        $title = SITE_NAME;
    }
    save_setting('site_title', $title);
    save_setting('site_tagline', $tagline);
    flash('success', 'Website title and tagline updated.');
    redirect(admin_url('/settings/general.php'));
}

require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card" style="max-width:760px;">
    <h2>General Settings</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Website Title</label>
        <input type="text" name="site_title" value="<?= e($title) ?>" required>

        <label>Website Tagline</label>
        <input type="text" name="site_tagline" value="<?= e($tagline) ?>">

        <div class="help-text">These values appear on the public website and in the admin top bar.</div>
        <br>
        <button class="btn" type="submit">Save Settings</button>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
