<?php

function uploadImageToSupabase(string $tmpFilePath, string $originalName): array
{
    if (!SUPABASE_URL || !SUPABASE_SERVICE_KEY) {
        throw new RuntimeException('Supabase storage is not configured.');
    }

    if (!is_uploaded_file($tmpFilePath) && !file_exists($tmpFilePath)) {
        throw new RuntimeException('Uploaded file not found.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpFilePath);
    finfo_close($finfo);

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg');
    $baseName = bin2hex(random_bytes(12));
    $objectPath = 'talents/' . date('Y/m') . '/' . $baseName . '.' . $extension;
    $fullObjectPath = 'talents/' . date('Y/m') . '/' . $baseName . '-full.' . $extension;

    // Clean (unwatermarked) original — used only as the source for on-the-fly
    // thumb/medium resizing, never shown at full size.
    upload_raw_to_supabase($tmpFilePath, $objectPath, $mime);

    // Watermarked copy — used for the full-size image_url (e.g. lightbox preview).
    $watermarkedPath = create_watermarked_copy($tmpFilePath, $mime);
    upload_raw_to_supabase($watermarkedPath ?? $tmpFilePath, $fullObjectPath, $mime);

    if ($watermarkedPath) {
        @unlink($watermarkedPath);
    }

    $publicFullUrl = SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $fullObjectPath;

    return [
        'url' => $publicFullUrl,
        'thumb_url' => supabase_render_url($objectPath, 160),
        'medium_url' => supabase_render_url($objectPath, 640),
        'path' => $objectPath,
    ];
}

function upload_raw_to_supabase(string $filePath, string $objectPath, string $mime): void
{
    $fileContents = file_get_contents($filePath);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => SUPABASE_URL . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $objectPath,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $fileContents,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Content-Type: ' . $mime,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Supabase upload failed: ' . $curlError);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException('Supabase upload failed: ' . $response);
    }
}

function supabase_render_url(string $objectPath, int $width): string
{
    return SUPABASE_URL . '/storage/v1/render/image/public/' . SUPABASE_BUCKET . '/' . $objectPath
        . '?width=' . $width . '&quality=70';
}

function create_watermarked_copy(string $sourcePath, string $mime): ?string
{
    $text = trim((string) getenv_value('WATERMARK_TEXT', 'Dola AI'));

    if ($text === '') {
        return null;
    }

    switch ($mime) {
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            $image = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : null;
            break;
        default:
            $image = imagecreatefromjpeg($sourcePath);
    }

    if (!$image) {
        return null;
    }

    imagealphablending($image, true);
    imagesavealpha($image, true);

    $width = imagesx($image);
    $height = imagesy($image);
    $fontPath = ROOT_PATH . '/app/assets/fonts/watermark.ttf';
    $fontSize = 12;
    $margin = max(10, (int) round(min($width, $height) * 0.02));

    $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
    $textWidth = $bbox[2] - $bbox[0];

    $x = $width - $textWidth - $margin;
    $y = $height - $margin;

    $shadow = imagecolorallocatealpha($image, 0, 0, 0, 60);
    $white = imagecolorallocatealpha($image, 255, 255, 255, 15);

    imagettftext($image, $fontSize, 0, (int) $x + 1, (int) $y + 1, $shadow, $fontPath, $text);
    imagettftext($image, $fontSize, 0, (int) $x, (int) $y, $white, $fontPath, $text);

    $outputPath = tempnam(sys_get_temp_dir(), 'wm_');

    switch ($mime) {
        case 'image/png':
            imagepng($image, $outputPath);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                imagewebp($image, $outputPath);
            } else {
                imagepng($image, $outputPath);
            }
            break;
        default:
            imagejpeg($image, $outputPath, 90);
    }

    imagedestroy($image);

    return $outputPath;
}

function validate_upload_file(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'Upload error code ' . $file['error'] . '.';
    }

    $maxBytes = MAX_IMAGE_SIZE_MB * 1024 * 1024;

    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        return 'File exceeds maximum size of ' . MAX_IMAGE_SIZE_MB . 'MB.';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_MIME_TYPES, true)) {
        return 'Unsupported file type: ' . $mime . '.';
    }

    return null;
}
