<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$photoId = (int) ($_POST['photo_id'] ?? 0);
$photo = get_talent_photo($photoId);

if (!$photo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Photo not found.']);
    exit;
}

if (empty($_FILES['cropped_image']) || $_FILES['cropped_image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No cropped image received.']);
    exit;
}

$validationError = validate_upload_file($_FILES['cropped_image']);

if ($validationError) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $validationError]);
    exit;
}

try {
    $objectPath = uploadCroppedThumb($_FILES['cropped_image']['tmp_name']);
    update_photo_source($photoId, $objectPath);

    echo json_encode(['success' => true, 'message' => 'Crop saved.', 'thumb_url' => supabase_render_url($objectPath, 100)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Crop upload failed.']);
}
