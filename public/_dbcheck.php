<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (APP_ENV !== 'local') {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain');

try {
    db()->query('SELECT id FROM cities LIMIT 1');
    echo 'Database connection OK';
} catch (Throwable $e) {
    echo 'Database connection FAILED: ' . $e->getMessage();
}
