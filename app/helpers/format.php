<?php

function format_bytes(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
    $value = $bytes / (1024 ** $power);

    return round($value, $power === 0 ? 0 : 1) . ' ' . $units[$power];
}
