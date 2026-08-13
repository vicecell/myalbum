<?php

function env_store(?array $set = null): array
{
    static $store = [];

    if ($set !== null) {
        $store = $set;
    }

    return $store;
}

function load_env(string $path): void
{
    if (!file_exists($path)) {
        throw new RuntimeException('.env file not found at ' . $path . '. Copy .env.example to .env.');
    }

    $data = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $data[$key] = $value;
    }

    env_store($data);
}

function getenv_value(string $key, $default = null)
{
    return env_store()[$key] ?? $default;
}
