<?php

// CLOUDINARY_URL format: cloudinary://API_KEY:API_SECRET@CLOUD_NAME
$cloudinaryUrl = getenv_value('CLOUDINARY_URL', '');
$parsed = $cloudinaryUrl ? parse_url($cloudinaryUrl) : false;

define('CLOUDINARY_API_KEY', $parsed['user'] ?? '');
define('CLOUDINARY_API_SECRET', $parsed['pass'] ?? '');
define('CLOUDINARY_CLOUD_NAME', $parsed['host'] ?? '');

unset($cloudinaryUrl, $parsed);
