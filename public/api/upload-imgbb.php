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

$photoUrl = trim($_POST['photo_url'] ?? '');
$hasFiles = !empty($_FILES['photos']) && !empty($_FILES['photos']['name'][0]);

if (!$hasFiles && $photoUrl === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No photos provided.']);
    exit;
}

$uploaded = [];
$errors = [];
$hasExistingPrimary = talent_has_any_photo($talentId);
$fileCount = $hasFiles ? count($_FILES['photos']['name']) : 0;

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

if ($photoUrl !== '') {
    try {
        $downloaded = fetch_remote_image_to_temp($photoUrl);
        $uploadData = uploadImageToSupabase($downloaded['tmp_path'], $downloaded['original_name']);
        $isPrimary = !$hasExistingPrimary && empty($uploaded);
        $photoId = insert_talent_photo($talentId, $uploadData, $downloaded['original_name'], $isPrimary);

        $uploaded[] = [
            'id' => $photoId,
            'image_url' => $uploadData['url'] ?? '',
            'thumb_url' => $uploadData['thumb_url'] ?? $uploadData['url'] ?? '',
            'is_primary' => $isPrimary ? 1 : 0,
        ];
    } catch (Throwable $e) {
        $errors[] = 'URL: ' . $e->getMessage();
    } finally {
        if (isset($downloaded['tmp_path'])) {
            @unlink($downloaded['tmp_path']);
        }
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
