<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin_header.php';

$php = PHP_VERSION;
$server = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$pdoDriver = class_exists('PDO') ? 'PDO enabled' : 'PDO missing';
$uploadMax = defined('UPLOAD_MAX_MB') ? (int)UPLOAD_MAX_MB : 0;
$installedLock = file_exists(__DIR__ . '/../config/installed.lock') || (defined('DB_NAME') && DB_NAME !== 'your_database_name');
?>

<h1 style="margin-top:0;">About <?= e(cms_name()) ?></h1>

<div class="card" style="max-width:900px;">
  <div style="display:flex; gap:16px; align-items:center;">
    <img src="<?= cms_logo_url() ?>" alt="<?= e(cms_name()) ?>" style="width:84px; height:84px; border-radius:50%;">
    <div>
      <div style="font-size:22px; font-weight:700; line-height:1.2;">
        <?= e(cms_name()) ?> <span class="muted">v<?= e(cms_version()) ?></span>
      </div>
      <div class="muted"><?= e(cms_tagline()) ?></div>
      <div class="small muted" style="margin-top:6px;">Backend dashboard CMS. Public site branding is independent.</div>
    </div>
  </div>

  <hr style="border:none; border-top:1px solid rgba(0,0,0,.08); margin:16px 0;">

  <h3 style="margin:0 0 10px;">System</h3>
  <table class="table" style="width:100%;">
    <tr><td style="width:220px;"><strong>PHP</strong></td><td><?= e($php) ?></td></tr>
    <tr><td><strong>Server</strong></td><td><?= e($server) ?></td></tr>
    <tr><td><strong>Database</strong></td><td><?= e($pdoDriver) ?></td></tr>
    <tr><td><strong>Upload limit</strong></td><td><?= e((string)$uploadMax) ?> MB</td></tr>
    <tr><td><strong>Install lock</strong></td><td><?= $installedLock ? '<span class="status-badge">Present</span>' : '<span class="status-badge draft">Not found</span>' ?></td></tr>
  </table>

  <h3 style="margin:18px 0 10px;">Project packaging</h3>
  <ul>
    <li>Docs: see the <code>/docs</code> folder.</li>
    <li>Installer: <code>/install/</code> (delete after installation on production).</li>
    <li>License: <code>LICENSE</code> (MIT by default; change if needed).</li>
  </ul>

  <div class="small muted" style="margin-top:14px;">
    © <?= date('Y') ?> Vidyasagar Academy. Built for education and long-term maintainability.
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
