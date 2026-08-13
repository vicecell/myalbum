<?php

function validate_required(string $value): bool
{
    return trim($value) !== '';
}

function validate_max_length(string $value, int $max): bool
{
    return mb_strlen(trim($value)) <= $max;
}

function validate_in_list(string $value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}

function validate_url_or_empty(?string $value): bool
{
    $value = trim((string) $value);

    if ($value === '') {
        return true;
    }

    return sanitize_video_url($value) !== null;
}
