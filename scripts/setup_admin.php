<?php
// One-time CLI setup script: creates or resets the single admin account.
// Usage: php scripts/setup_admin.php <username> <password>

require_once __DIR__ . '/../app/config/env.php';
require_once __DIR__ . '/../app/config/database.php';

load_env(dirname(__DIR__) . '/.env');

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

$stmt = db()->prepare(
    'INSERT INTO admins (username, password_hash) VALUES (:username, :hash)
     ON CONFLICT (username) DO UPDATE SET password_hash = EXCLUDED.password_hash'
);
$stmt->execute(['username' => $username, 'hash' => $hash]);

echo "Admin '{$username}' saved.\n";
