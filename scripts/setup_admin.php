<?php
// One-time CLI setup script: creates or resets the single admin account.
// Usage: php scripts/setup_admin.php <username> <password>

require_once __DIR__ . '/../app/config/env.php';

load_env(dirname(__DIR__) . '/.env');

require_once __DIR__ . '/../app/config/supabase.php';
require_once __DIR__ . '/../app/config/database.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$username || !$password) {
    fwrite(STDERR, "Usage: php scripts/setup_admin.php <username> <password>\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

supabase_rest('POST', 'admins', ['on_conflict' => 'username'], [
    'username' => $username,
    'password_hash' => $hash,
]);

echo "Admin '{$username}' saved.\n";
