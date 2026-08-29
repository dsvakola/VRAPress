<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');

$items = db()->query('SELECT id, original_name, file_url, mime_type, file_size, created_at FROM media ORDER BY created_at DESC LIMIT 200')->fetchAll();
echo json_encode(['ok' => true, 'items' => $items]);
