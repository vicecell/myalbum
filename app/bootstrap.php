<?php

define('ROOT_PATH', dirname(__DIR__));

require_once __DIR__ . '/config/env.php';

try {
    load_env(ROOT_PATH . '/.env');
} catch (RuntimeException $e) {
    http_response_code(500);
    echo '<h1>Configuration error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

require_once __DIR__ . '/config/app.php';

if (APP_ENV === 'local') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_COOKIE_NAME);
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/flash.php';
require_once __DIR__ . '/helpers/sanitize.php';
require_once __DIR__ . '/helpers/validation.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/functions/city_functions.php';
require_once __DIR__ . '/functions/talent_functions.php';
require_once __DIR__ . '/functions/photo_functions.php';
require_once __DIR__ . '/config/upload.php';
require_once __DIR__ . '/config/supabase.php';
require_once __DIR__ . '/helpers/upload.php';
