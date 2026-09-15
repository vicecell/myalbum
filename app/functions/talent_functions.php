<?php

function count_talents(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM talents WHERE deleted_at IS NULL')->fetchColumn();
}

function count_active_talents(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM talents WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn();
}

function get_talents(?string $search = null, ?int $cityId = null): array
{
    $sql = 'SELECT t.*, c.city_name, p.image_thumb_url AS primary_photo
            FROM talents t
            JOIN cities c ON c.id = t.city_id
            LEFT JOIN talent_photos p ON p.talent_id = t.id AND p.is_primary = 1 AND p.deleted_at IS NULL
            WHERE t.deleted_at IS NULL';
    $params = [];

    if ($cityId) {
        $sql .= ' AND t.city_id = ?';
        $params[] = $cityId;
    }

    if ($search !== null && $search !== '') {
        $sql .= ' AND (t.name LIKE ? OR t.description LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY LOWER(t.name) ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['links'] = json_decode($row['links'] ?? '[]', true) ?: [];
    }
    unset($row);

    return $rows;
}

function get_talent(int $id): ?array
{
    $stmt = db()->prepare('SELECT t.*, c.city_name FROM talents t JOIN cities c ON c.id = t.city_id WHERE t.id = ? AND t.deleted_at IS NULL LIMIT 1');
    $stmt->execute([$id]);
    $talent = $stmt->fetch();

    if (!$talent) {
        return null;
    }

    $talent['links'] = json_decode($talent['links'] ?? '[]', true) ?: [];

    return $talent;
}

function get_talent_photos(int $id): array
{
    $stmt = db()->prepare('SELECT * FROM talent_photos WHERE talent_id = ? AND deleted_at IS NULL ORDER BY is_primary DESC, sort_order ASC, id ASC');
    $stmt->execute([$id]);

    return $stmt->fetchAll();
}

function city_exists(int $id): bool
{
    $stmt = db()->prepare('SELECT id FROM cities WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$id]);

    return (bool) $stmt->fetch();
}

function create_talent(array $data): int
{
    $stmt = db()->prepare('INSERT INTO talents (city_id, name, description, video_url, rate, links, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['city_id'],
        $data['name'],
        $data['description'],
        $data['video_url'],
        $data['rate'],
        json_encode($data['links'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $data['status'],
    ]);

    return (int) db()->lastInsertId();
}

function update_talent(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE talents SET city_id = ?, name = ?, description = ?, video_url = ?, rate = ?, links = ?, status = ? WHERE id = ?');
    $stmt->execute([
        $data['city_id'],
        $data['name'],
        $data['description'],
        $data['video_url'],
        $data['rate'],
        json_encode($data['links'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $data['status'],
        $id,
    ]);
}

function delete_talent(int $id): void
{
    $stmt = db()->prepare('UPDATE talents SET deleted_at = ? WHERE id = ?');
    $stmt->execute([now_ts(), $id]);
}
