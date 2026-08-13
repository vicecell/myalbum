<?php

function get_all_cities(): array
{
    $stmt = db()->query('SELECT * FROM cities WHERE deleted_at IS NULL ORDER BY LOWER(city_name) ASC');

    return $stmt->fetchAll();
}

function count_cities(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM cities WHERE deleted_at IS NULL')->fetchColumn();
}

function get_active_cities(): array
{
    $stmt = db()->query("SELECT * FROM cities WHERE status = 'active' AND deleted_at IS NULL ORDER BY LOWER(city_name) ASC");

    return $stmt->fetchAll();
}

function get_city(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM cities WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$id]);
    $city = $stmt->fetch();

    return $city ?: null;
}

function city_name_exists(string $name, ?int $excludeId = null): bool
{
    if ($excludeId !== null) {
        $stmt = db()->prepare('SELECT id FROM cities WHERE city_name = ? AND id != ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$name, $excludeId]);
    } else {
        $stmt = db()->prepare('SELECT id FROM cities WHERE city_name = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$name]);
    }

    return (bool) $stmt->fetch();
}

function create_city(string $name, string $status): int
{
    $stmt = db()->prepare('INSERT INTO cities (city_name, status) VALUES (?, ?)');
    $stmt->execute([$name, $status]);

    return (int) db()->lastInsertId();
}

function update_city(int $id, string $name, string $status): void
{
    $stmt = db()->prepare('UPDATE cities SET city_name = ?, status = ? WHERE id = ?');
    $stmt->execute([$name, $status, $id]);
}

function city_has_talents(int $id): bool
{
    $stmt = db()->prepare('SELECT id FROM talents WHERE city_id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$id]);

    return (bool) $stmt->fetch();
}

function delete_city(int $id): bool
{
    if (city_has_talents($id)) {
        return false;
    }

    $stmt = db()->prepare('UPDATE cities SET deleted_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);

    return true;
}
