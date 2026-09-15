<?php

function uploadImageToCloudinary(string $tmpFilePath, string $originalName, string $folderName): array
{
    if (!is_uploaded_file($tmpFilePath) && !file_exists($tmpFilePath)) {
        throw new RuntimeException('Uploaded file not found.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpFilePath);
    finfo_close($finfo);

    $tempFiles = [];
    $sourcePath = $tmpFilePath;

    // Cap at 1200px wide — narrower originals are left untouched (no upscale,
    // no re-encode/quality loss for images that are already small enough).
    $resizedPath = resize_image_to_max_width($sourcePath, $mime, 1200);
    if ($resizedPath) {
        $tempFiles[] = $resizedPath;
        $sourcePath = $resizedPath;
    }

    // Clean (unwatermarked) — doubles as the manual-crop source. Thumb/crop URLs
    // are generated live via Cloudinary's transform URLs (w_N,c_limit), so no
    // separate thumb upload is needed like the ImgBB pipeline required.
    $clean = cloudinary_upload_raw($sourcePath, $mime, $folderName);

    // Watermarked copy — used for the full-size image_url (e.g. lightbox preview).
    $watermarkedPath = create_watermarked_copy($sourcePath, $mime);
    if ($watermarkedPath) {
        $tempFiles[] = $watermarkedPath;
    }
    $full = cloudinary_upload_raw($watermarkedPath ?? $sourcePath, $mime, $folderName);

    foreach ($tempFiles as $tempFile) {
        @unlink($tempFile);
    }

    return [
        'url' => $full['secure_url'],
        'thumb_url' => cloudinary_transform_url($clean['public_id'], 100),
        'path' => $clean['public_id'],
        'bytes' => ($clean['bytes'] ?? 0) + ($full['bytes'] ?? 0),
        'full_bytes' => $full['bytes'] ?? 0,
    ];
}

function uploadCroppedThumb(string $tmpFilePath, string $folderName): array
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpFilePath);
    finfo_close($finfo);

    // The crop tool only ever replaces the thumbnail/list-preview representation
    // (imgbb_id + image_thumb_url) — the full watermarked lightbox image is left
    // untouched, matching the "crop for main/list image only" design.
    $clean = cloudinary_upload_raw($tmpFilePath, $mime, $folderName);

    return [
        'path' => $clean['public_id'],
        'thumb_url' => cloudinary_transform_url($clean['public_id'], 100),
    ];
}

function cloudinary_upload_raw(string $filePath, string $mime, string $folder): array
{
    if (!CLOUDINARY_CLOUD_NAME || !CLOUDINARY_API_KEY || !CLOUDINARY_API_SECRET) {
        throw new RuntimeException('Cloudinary is not configured.');
    }

    $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $extension = $extensionMap[$mime] ?? 'jpg';

    $timestamp = (string) time();
    // Force the stored asset itself to WebP (not just the on-the-fly f_auto
    // delivery transform used for thumb/crop-source URLs) — smaller storage,
    // and the full/watermarked object's URL is served as-is with no transform.
    $format = 'webp';
    // Signature covers every non-file param, sorted by key, as key=value pairs
    // joined with '&' (raw, not URL-encoded) — per Cloudinary's signing spec.
    $signParams = ['folder' => $folder, 'format' => $format, 'timestamp' => $timestamp];
    ksort($signParams);
    $toSign = '';
    foreach ($signParams as $key => $value) {
        $toSign .= ($toSign === '' ? '' : '&') . $key . '=' . $value;
    }
    $signature = sha1($toSign . CLOUDINARY_API_SECRET);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($filePath, $mime, 'upload.' . $extension),
            'api_key' => CLOUDINARY_API_KEY,
            'timestamp' => $timestamp,
            'folder' => $folder,
            'format' => $format,
            'signature' => $signature,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Cloudinary upload failed: ' . $curlError);
    }

    $decoded = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300 || !isset($decoded['public_id'])) {
        $message = $decoded['error']['message'] ?? $response;
        throw new RuntimeException('Cloudinary upload failed: ' . $message);
    }

    return $decoded;
}

function cloudinary_transform_url(string $publicId, int $width): string
{
    $encodedPublicId = implode('/', array_map('rawurlencode', explode('/', $publicId)));

    return 'https://res.cloudinary.com/' . CLOUDINARY_CLOUD_NAME
        . '/image/upload/w_' . $width . ',c_limit,f_auto,q_auto/' . $encodedPublicId;
}

function cloudinary_folder_name(string $talentName): string
{
    $name = trim(str_replace(['/', '\\'], '-', $talentName));

    return 'talents/' . ($name !== '' ? $name : 'unknown');
}

function resize_image_to_max_width(string $sourcePath, string $mime, int $maxWidth): ?string
{
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

    $width = imagesx($image);
    $height = imagesy($image);

    if ($width <= $maxWidth) {
        imagedestroy($image);
        return null;
    }

    $newWidth = $maxWidth;
    $newHeight = (int) round($height * ($maxWidth / $width));

    $resized = imagecreatetruecolor($newWidth, $newHeight);

    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
    }

    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagedestroy($image);

    $outputPath = tempnam(sys_get_temp_dir(), 'resize_');

    switch ($mime) {
        case 'image/png':
            imagepng($resized, $outputPath);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                imagewebp($resized, $outputPath);
            } else {
                imagepng($resized, $outputPath);
            }
            break;
        default:
            imagejpeg($resized, $outputPath, 90);
    }

    imagedestroy($resized);

    return $outputPath;
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
