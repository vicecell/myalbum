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
        'thumb_url' => supabase_render_url($objectPath, 100),
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

function uploadCroppedThumb(string $tmpFilePath): string
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpFilePath);
    finfo_close($finfo);

    $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $extension = $extensionMap[$mime] ?? 'jpg';
    $objectPath = 'talents/crops/' . date('Y/m') . '/' . bin2hex(random_bytes(12)) . '.' . $extension;

    upload_raw_to_supabase($tmpFilePath, $objectPath, $mime);

    return SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $objectPath;
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

function fetch_remote_image_to_temp(string $url): array
{
    $parts = parse_url($url);
    $scheme = strtolower($parts['scheme'] ?? '');
    $host = $parts['host'] ?? '';

    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        throw new RuntimeException('Only http:// or https:// image URLs are allowed.');
    }

    $maxBytes = MAX_IMAGE_SIZE_MB * 1024 * 1024;
    $tmpPath = tempnam(sys_get_temp_dir(), 'remote_img_');
    $fp = fopen($tmpPath, 'wb');

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_MAXFILESIZE_LARGE => $maxBytes,
        CURLOPT_USERAGENT => 'TalentDatabaseBot/1.0',
    ]);

    $ok = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $connectedIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
    curl_close($ch);
    fclose($fp);

    if (!$ok || $httpCode < 200 || $httpCode >= 300) {
        @unlink($tmpPath);
        throw new RuntimeException('Failed to download image URL: ' . ($curlError ?: "HTTP {$httpCode}"));
    }

    // Block SSRF: reject if curl actually connected to a private, loopback,
    // link-local, or otherwise reserved network address. Checked post-connect
    // (via curl's own resolution, which respects CURLOPT_CONNECTTIMEOUT) rather
    // than with a separate gethostbynamel() pre-check, which has no timeout of
    // its own and can hang the whole request indefinitely on a slow/broken
    // resolver.
    if (!$connectedIp || !filter_var($connectedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        @unlink($tmpPath);
        throw new RuntimeException('That image URL points to a disallowed network address.');
    }

    if (filesize($tmpPath) > $maxBytes) {
        @unlink($tmpPath);
        throw new RuntimeException('Downloaded image exceeds maximum size of ' . MAX_IMAGE_SIZE_MB . 'MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_MIME_TYPES, true)) {
        @unlink($tmpPath);
        throw new RuntimeException('Unsupported file type: ' . $mime . '.');
    }

    $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $originalName = 'url-image.' . ($extensionMap[$mime] ?? 'jpg');

    return ['tmp_path' => $tmpPath, 'original_name' => $originalName];
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
