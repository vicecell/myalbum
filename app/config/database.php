<?php

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = getenv_value('DB_HOST', '127.0.0.1');
        $port = getenv_value('DB_PORT', '3306');
        $name = getenv_value('DB_NAME', 'talent_database');
        $user = getenv_value('DB_USER', 'root');
        $pass = getenv_value('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function now_ts(): string
{
    return gmdate('Y-m-d H:i:s');
}
