<?php
require_once __DIR__ . '/../config/database.php';

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function site_url(string $path = ''): string {
    $base = rtrim(BASE_URL, '/');
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '' : $path);
}

function admin_url(string $path = ''): string {
    $base = rtrim(ADMIN_PATH, '/');
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '' : $path);
}

function current_admin(): ?array {
    return $_SESSION['admin_user'] ?? null;
}

function current_admin_id(): int {
    return (int)($_SESSION['admin_user']['id'] ?? 0);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'item-' . time();
}

function unique_slug(string $table, string $baseSlug, ?int $ignoreId = null): string {
    $slug = slugify($baseSlug);
    $candidate = $slug;
    $i = 2;
    while (true) {
        if ($ignoreId) {
            $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = ? AND id <> ?");
            $stmt->execute([$candidate, $ignoreId]);
        } else {
            $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = ?");
            $stmt->execute([$candidate]);
        }
        if ((int)$stmt->fetchColumn() === 0) {
            return $candidate;
        }
        $candidate = $slug . '-' . $i;
        $i++;
    }
}

function is_post(): bool {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function post_statuses(): array {
    return ['draft' => 'Draft', 'published' => 'Published'];
}

function fetch_categories(): array {
    $stmt = db()->query('SELECT id, name, slug FROM categories ORDER BY name ASC');
    return $stmt->fetchAll();
}

function create_excerpt(string $html, int $length = 180): string {
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - 3) . '...';
}

function public_page_url(string $slug): string {
    return site_url('/' . ltrim($slug, '/'));
}

function public_post_url(string $slug): string {
    return site_url('/post/' . ltrim($slug, '/'));
}

function public_category_url(string $slug): string {
    return site_url('/category/' . ltrim($slug, '/'));
}

function upload_dir_abs(): string {
    $dir = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function upload_subdir_rel(): string {
    $rel = date('Y') . '/' . date('m');
    $full = upload_dir_abs() . DIRECTORY_SEPARATOR . $rel;
    if (!is_dir($full)) {
        mkdir($full, 0755, true);
    }
    return $rel;
}

function allowed_upload_mimes(): array {
    return [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'application/pdf' => 'pdf',
    ];
}

/**
 * Normalize PHP's $_FILES structure into a flat list of file arrays.
 * Supports both single and multiple file inputs.
 */
function normalize_upload_files(array $files): array {
    if (!isset($files['name'])) return [];
    if (!is_array($files['name'])) {
        return [$files];
    }
    $out = [];
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $out[] = [
            'name' => $files['name'][$i] ?? null,
            'type' => $files['type'][$i] ?? null,
            'tmp_name' => $files['tmp_name'][$i] ?? null,
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $out;
}

function client_ip(): string {
    return (string)($_SERVER['REMOTE_ADDR'] ?? '');
}

function handle_media_upload(array $file, int $uploadedBy = 0): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }

    if (($file['size'] ?? 0) > (UPLOAD_MAX_MB * 1024 * 1024)) {
        throw new RuntimeException('File exceeds upload limit of ' . UPLOAD_MAX_MB . ' MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = allowed_upload_mimes();
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('File type not allowed.');
    }

    $ext = $allowed[$mime];
    $original = $file['name'] ?? 'upload.' . $ext;
    $basename = pathinfo($original, PATHINFO_FILENAME);
    $basename = slugify($basename);
    $filename = $basename . '-' . substr(bin2hex(random_bytes(8)), 0, 8) . '.' . $ext;
    $relDir = upload_subdir_rel();
    $relPath = $relDir . '/' . $filename;
    $dest = upload_dir_abs() . DIRECTORY_SEPARATOR . $relPath;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not move uploaded file.');
    }

    $url = site_url('/uploads/' . $relPath);

    $stmt = db()->prepare('INSERT INTO media (file_name, original_name, file_path, file_url, mime_type, file_size, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $filename,
        $original,
        $relPath,
        $url,
        $mime,
        (int)$file['size'],
        $uploadedBy ?: null,
    ]);

    return [
        'id' => (int)db()->lastInsertId(),
        'file_name' => $filename,
        'original_name' => $original,
        'file_path' => $relPath,
        'file_url' => $url,
        'mime_type' => $mime,
        'file_size' => (int)$file['size'],
    ];
}

function human_filesize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function request_path(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return $path;
}

function settings_cache(): array {
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    try {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        $settings = [];
    }
    return $settings;
}

function setting(string $key, ?string $default = null): ?string {
    $settings = settings_cache();
    return $settings[$key] ?? $default;
}

function refresh_settings_cache(): void {
    $ref = new ReflectionFunction('settings_cache');
    $staticVars = $ref->getStaticVariables();
    // no-op placeholder for compatibility
}

function save_setting(string $key, string $value): void {
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)');
    $stmt->execute([$key, $value]);
    unset($GLOBALS['__settings_cache']);
}

function site_title(): string {
    return setting('site_title', SITE_NAME) ?: SITE_NAME;
}

function site_tagline(): string {
    return setting('site_tagline', 'Lightweight custom PHP website') ?: 'Lightweight custom PHP website';
}


function cms_name(): string {
    return defined('CMS_NAME') ? (string)CMS_NAME : 'VRAPress';
}

function cms_tagline(): string {
    return defined('CMS_TAGLINE') ? (string)CMS_TAGLINE : 'Lightweight PHP CMS';
}

function cms_logo_url(): string {
    $path = defined('CMS_LOGO') ? (string)CMS_LOGO : '/assets/images/vrapress-logo.png';
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    return site_url($path);
}
function cms_version(): string {
    if (defined("CMS_VERSION")) {
        return (string)CMS_VERSION;
    }
    static $ver = null;
    if (is_string($ver)) {
        return $ver;
    }
    $file = realpath(__DIR__ . "/../VERSION");
    if ($file && is_file($file)) {
        $v = trim((string)file_get_contents($file));
        if ($v !== "") {
            $ver = $v;
            return $ver;
        }
    }
    $ver = "0.0.0";
    return $ver;
}


function content_image_class_options(): array {
    return ['small', 'medium', 'large', 'full'];
}

// -------------------------
// Menus
// -------------------------
function menu_items_by_location(string $location = 'primary'): array {
    try {
        $stmt = db()->prepare('SELECT mi.* FROM menus m JOIN menu_items mi ON mi.menu_id = m.id WHERE m.location = ? ORDER BY mi.sort_order ASC, mi.id ASC');
        $stmt->execute([$location]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function render_menu_html(string $location = 'primary'): string {
    $items = menu_items_by_location($location);
    if (!$items) return '';
    $html = '<ul class="vrp-menu">';
    foreach ($items as $it) {
        $url = $it['url'] ?? '#';
        $label = $it['label'] ?? 'Menu';
        $target = ((int)($it['target_blank'] ?? 0) === 1) ? ' target="_blank" rel="noopener"' : '';
        $html .= '<li><a href="' . e($url) . '"' . $target . '>' . e($label) . '</a></li>';
    }
    $html .= '</ul>';
    return $html;
}
