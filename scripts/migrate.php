<?php
// Applies any pending SQL files in database/migrations/ that haven't been run
// yet, tracked via a schema_migrations table. Safe to run repeatedly.
// Usage: php scripts/migrate.php

require_once __DIR__ . '/../app/config/env.php';

load_env(dirname(__DIR__) . '/.env');

require_once __DIR__ . '/../app/config/database.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$pdo = db();

$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_migration (migration)
) ENGINE=InnoDB');

$applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(dirname(__DIR__) . '/database/migrations/*.sql');
sort($files);

$ranAny = false;

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        continue;
    }

    echo "Applying {$name}...\n";
    $sql = trim(file_get_contents($file));

    // No explicit transaction here: DDL (ALTER TABLE etc.) auto-commits in
    // MySQL/MariaDB regardless, so wrapping it in BEGIN/COMMIT just crashes on
    // commit()/rollBack() ("no active transaction") once the DDL has already
    // implicitly ended it.
    try {
        $pdo->exec($sql);

        $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
        $stmt->execute([$name]);

        echo "  OK\n";
        $ranAny = true;
    } catch (Throwable $e) {
        fwrite(STDERR, "  FAILED: {$e->getMessage()}\n");
        exit(1);
    }
}

echo $ranAny ? "Done.\n" : "Nothing to migrate.\n";
