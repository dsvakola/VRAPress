<?php
// Load config
$local = __DIR__ . '/config.local.php';
$primary = __DIR__ . '/config.php';

if (is_file($local)) {
    require_once $local;
} else {
    require_once $primary;
}

// If not configured, redirect to installer for web requests.
if (defined('DB_NAME') && DB_NAME === 'your_database_name') {
    if (PHP_SAPI !== 'cli') {
        $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
        $base = $dir;
        if (preg_match('#/admin$#', $base)) {
            $base = rtrim(dirname($base), '/');
        }
        if ($base === '' || $base === '.') {
            $base = '';
        }
        $installUrl = $base . '/install/';
        header('Location: ' . $installUrl);
        exit;
    }
    throw new RuntimeException('VRAPress is not configured. Run /install/.');
}

function db(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}
