<?php
// VRAPress Installer (standalone: does not require existing config)

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function guess_base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/install/';
    // remove /install or /install/index.php
    $basePath = preg_replace('#/install(/index\.php)?/?$#', '', $uri);
    $basePath = rtrim($basePath, '/');
    return $scheme . '://' . $host . $basePath;
}

function is_already_installed(): bool {
    $configPath = realpath(__DIR__ . '/../config/config.php');
    if (!$configPath || !is_file($configPath)) {
        return false;
    }
    // Load config in a safe scope
    $cfg = file_get_contents($configPath);
    if ($cfg === false) return false;
    if (strpos($cfg, "define('DB_NAME', 'your_database_name')") !== false) {
        return false;
    }
    // If an install lock exists, treat as installed
    if (is_file(__DIR__ . '/../config/installed.lock')) {
        return true;
    }
    // Try a lightweight DB check
    try {
        require_once __DIR__ . '/../config/config.php';
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->query('SELECT 1');
        // If users table exists, consider installed
        $pdo->query("SELECT 1 FROM users LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

$errors = [];
$success = null;
$step = (int)($_GET['step'] ?? 1);

if (is_already_installed()) {
    $success = 'VRAPress appears to be already installed. For security, please delete or rename the /install folder.';
    $step = 99;
}

$defaults = [
    'db_host' => 'localhost',
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',
    'base_url' => guess_base_url(),
    'admin_path' => '/admin',
    'timezone' => 'Asia/Kolkata',
    'upload_max_mb' => '5',
    'site_title' => 'My Website',
    'site_tagline' => 'Powered by VRAPress (backend only)',
    'admin_name' => 'Administrator',
    'admin_username' => 'admin',
    'admin_password' => '',
];

$data = $defaults;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($defaults as $k => $v) {
        $data[$k] = trim((string)($_POST[$k] ?? ''));
    }

    // Basic validation
    foreach (['db_name','db_user','base_url','admin_path','admin_username'] as $req) {
        if ($data[$req] === '') {
            $errors[] = 'Missing required field: ' . $req;
        }
    }
    if ($data['admin_password'] === '') {
        $errors[] = 'Please set an admin password.';
    }

    if (!$errors) {
        // Connect
        try {
            $dsn = 'mysql:host=' . $data['db_host'] . ';dbname=' . $data['db_name'] . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Import schema
            $schemaFile = __DIR__ . '/../sql/schema.sql';
            if (!is_file($schemaFile)) {
                throw new RuntimeException('Missing schema file: sql/schema.sql');
            }
            $sql = (string)file_get_contents($schemaFile);
            // Split on semicolons (schema is simple)
            $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
            $pdo->beginTransaction();
            foreach ($statements as $stmt) {
                if ($stmt === '') continue;
                $pdo->exec($stmt);
            }

            // Update settings
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES ('site_title', ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)");
            $stmt->execute([$data['site_title']]);

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES ('site_tagline', ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)");
            $stmt->execute([$data['site_tagline']]);

            // Create / update admin
            $passwordHash = password_hash($data['admin_password'], PASSWORD_DEFAULT);

            // If schema already created 'admin', update it; otherwise insert.
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$data['admin_username']]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, password_hash = ?, role = "admin", updated_at = NOW() WHERE id = ?');
                $stmt->execute([$data['admin_name'], $passwordHash, $existingId]);
            } else {
                // Update default admin row if it exists
                $stmt = $pdo->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
                $defaultId = $stmt->fetchColumn();
                if ($defaultId) {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, username = ?, password_hash = ?, role = "admin", updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$data['admin_name'], $data['admin_username'], $passwordHash, $defaultId]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO users (name, username, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, "admin", NOW(), NOW())');
                    $stmt->execute([$data['admin_name'], $data['admin_username'], $passwordHash]);
                }
            }

            $pdo->commit();

            // Write config/config.local.php
            $cfgPath = __DIR__ . '/../config/config.local.php';
            $cfg = "<?php\n";
            $cfg .= "define('DB_HOST', '" . addslashes($data['db_host']) . "');\n";
            $cfg .= "define('DB_NAME', '" . addslashes($data['db_name']) . "');\n";
            $cfg .= "define('DB_USER', '" . addslashes($data['db_user']) . "');\n";
            $cfg .= "define('DB_PASS', '" . addslashes($data['db_pass']) . "');\n";
            $cfg .= "define('SITE_NAME', '" . addslashes($data['site_title']) . "');\n";
            $cfg .= "define('BASE_URL', '" . addslashes(rtrim($data['base_url'], '/')) . "');\n";
            $cfg .= "define('ADMIN_PATH', '" . addslashes($data['admin_path']) . "');\n";
            $cfg .= "define('TIMEZONE', '" . addslashes($data['timezone']) . "');\n";
            $cfg .= "define('UPLOAD_MAX_MB', " . (int)$data['upload_max_mb'] . ");\n\n";
            $cfg .= "// VRAPress backend branding\n";
            $cfg .= "define('CMS_NAME', 'VRAPress');\n";
            $cfg .= "define('CMS_TAGLINE', 'Lightweight PHP CMS');\n";
            $cfg .= "// CMS_LOGO defaults to /assets/images/vrapress-logo.png\n";
            $cfg .= "// define('CMS_LOGO', '/assets/images/vrapress-logo.png');\n";
            $cfg .= "date_default_timezone_set(TIMEZONE);\n";
            $cfg .= "if (session_status() === PHP_SESSION_NONE) { session_start(); }\n";

            if (file_put_contents($cfgPath, $cfg) === false) {
                throw new RuntimeException('Could not write config/config.local.php (check folder permissions).');
            }

            // Create install lock
            file_put_contents(__DIR__ . '/../config/installed.lock', 'installed ' . date('c') . "\n");

            $adminLogin = rtrim($data['base_url'], '/') . rtrim($data['admin_path'], '/') . '/login.php';
            $success = 'Installation complete. Admin login: ' . $adminLogin;
            $step = 99;

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>VRAPress Installer</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="front-wrap">
  <header class="front-header" style="align-items:center;">
    <div style="display:flex; align-items:center; gap:12px;">
      <img src="../assets/images/vrapress-logo.png" alt="VRAPress" style="width:56px; height:56px; border-radius:50%;">
      <div>
        <div style="font-size:22px; font-weight:800;">VRAPress Installer</div>
        <div class="muted">Lightweight PHP CMS (backend)</div>
      </div>
    </div>
  </header>

  <div class="front-grid" style="grid-template-columns: 1fr;">
    <div class="front-card">

      <?php if ($errors): ?>
        <div class="flash error">
          <strong>Fix these issues:</strong>
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= h($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="flash success"><?= h($success) ?></div>
      <?php endif; ?>

      <?php if ($step === 99): ?>
        <p class="muted">For security: delete or rename the <code>/install</code> folder on production.</p>
        <p><a class="btn" href="../admin/login.php">Go to Admin Login</a> <a class="btn" href="../">Go to Website</a></p>
      <?php else: ?>

        <h2 style="margin-top:0;">Configuration</h2>
        <form method="post" action="">
          <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
            <div>
              <label>DB Host</label>
              <input type="text" name="db_host" value="<?= h($data['db_host']) ?>">
            </div>
            <div>
              <label>DB Name *</label>
              <input type="text" name="db_name" value="<?= h($data['db_name']) ?>" placeholder="e.g. vsa_cms">
            </div>
            <div>
              <label>DB User *</label>
              <input type="text" name="db_user" value="<?= h($data['db_user']) ?>" placeholder="e.g. root">
            </div>
            <div>
              <label>DB Password</label>
              <input type="password" name="db_pass" value="<?= h($data['db_pass']) ?>">
            </div>

            <div>
              <label>Base URL *</label>
              <input type="text" name="base_url" value="<?= h($data['base_url']) ?>" placeholder="http://localhost/vrapress_site">
              <div class="small muted">Example: <code>http://localhost/vrapress_site</code></div>
            </div>
            <div>
              <label>Admin Path *</label>
              <input type="text" name="admin_path" value="<?= h($data['admin_path']) ?>" placeholder="/admin">
              <div class="small muted">If installed in a folder, include it: <code>/vrapress_site/admin</code></div>
            </div>

            <div>
              <label>Timezone</label>
              <input type="text" name="timezone" value="<?= h($data['timezone']) ?>">
            </div>
            <div>
              <label>Upload max (MB)</label>
              <input type="number" name="upload_max_mb" value="<?= h($data['upload_max_mb']) ?>" min="1" max="50">
            </div>

            <div>
              <label>Site Title</label>
              <input type="text" name="site_title" value="<?= h($data['site_title']) ?>">
            </div>
            <div>
              <label>Site Tagline</label>
              <input type="text" name="site_tagline" value="<?= h($data['site_tagline']) ?>">
            </div>

            <div>
              <label>Admin Name</label>
              <input type="text" name="admin_name" value="<?= h($data['admin_name']) ?>">
            </div>
            <div>
              <label>Admin Username *</label>
              <input type="text" name="admin_username" value="<?= h($data['admin_username']) ?>">
            </div>

            <div style="grid-column: 1 / -1;">
              <label>Admin Password *</label>
              <input type="password" name="admin_password" value="">
              <div class="small muted">Choose a strong password. This is the VRAPress dashboard login.</div>
            </div>

          </div>

          <div style="margin-top:16px; display:flex; gap:10px; align-items:center;">
            <button class="btn" type="submit">Install VRAPress</button>
            <span class="small muted">This will create tables in the database you provide.</span>
          </div>
        </form>

      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
