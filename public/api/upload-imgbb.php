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

$talentId = (int) ($_POST['talent_id'] ?? 0);
$talent = get_talent($talentId);

if (!$talent) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Talent not found.']);
    exit;
}

if (empty($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No photos provided.']);
    exit;
}

$uploaded = [];
$errors = [];
$hasExistingPrimary = talent_has_any_photo($talentId);
$fileCount = count($_FILES['photos']['name']);

for ($i = 0; $i < $fileCount; $i++) {
    $file = [
        'name' => $_FILES['photos']['name'][$i],
        'type' => $_FILES['photos']['type'][$i],
        'tmp_name' => $_FILES['photos']['tmp_name'][$i],
        'error' => $_FILES['photos']['error'][$i],
        'size' => $_FILES['photos']['size'][$i],
    ];

    $validationError = validate_upload_file($file);

    if ($validationError) {
        $errors[] = $file['name'] . ': ' . $validationError;
        continue;
    }

    try {
        $uploadData = uploadImageToSupabase($file['tmp_name'], $file['name']);
        $isPrimary = !$hasExistingPrimary && empty($uploaded);
        $photoId = insert_talent_photo($talentId, $uploadData, $file['name'], $isPrimary);

        $uploaded[] = [
            'id' => $photoId,
            'image_url' => $uploadData['url'] ?? '',
            'thumb_url' => $uploadData['thumb_url'] ?? $uploadData['url'] ?? '',
            'is_primary' => $isPrimary ? 1 : 0,
        ];
    } catch (Throwable $e) {
        $errors[] = $file['name'] . ': upload failed.';
    }
}

if (empty($uploaded)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors) ?: 'Upload failed.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Photos uploaded successfully.',
    'photos' => $uploaded,
    'errors' => $errors,
]);
