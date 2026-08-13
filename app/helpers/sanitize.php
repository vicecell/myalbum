<?php

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function sanitize_video_url(?string $url): ?string
{
    $url = trim((string) $url);

    if ($url === '') {
        return null;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);

    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }

    return $url;
}
