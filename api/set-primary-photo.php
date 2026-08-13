<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (!csrf_verify($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$photoId = (int) ($input['photo_id'] ?? 0);
$photo = get_talent_photo($photoId);

if (!$photo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Photo not found.']);
    exit;
}

set_primary_photo((int) $photo['talent_id'], $photoId);

echo json_encode(['success' => true, 'message' => 'Primary photo updated.']);
