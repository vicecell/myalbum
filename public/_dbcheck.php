<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (APP_ENV !== 'local') {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain');

try {
    supabase_rest('GET', 'cities', ['select' => 'id', 'limit' => '1']);
    echo 'Database connection OK';
} catch (RuntimeException $e) {
    echo 'Database connection FAILED: ' . $e->getMessage();
}
