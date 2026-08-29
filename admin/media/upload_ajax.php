<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');

try {
    verify_csrf();
    if (!isset($_FILES['file'])) {
        throw new RuntimeException('No file uploaded.');
    }
    $media = handle_media_upload($_FILES['file'], current_admin_id());
    echo json_encode(['ok' => true] + $media);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
