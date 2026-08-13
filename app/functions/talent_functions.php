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
    $sql = "SELECT t.*, c.city_name,
                (SELECT image_thumb_url FROM talent_photos tp
                 WHERE tp.talent_id = t.id AND tp.is_primary = 1 AND tp.deleted_at IS NULL LIMIT 1) AS primary_photo
            FROM talents t
            JOIN cities c ON c.id = t.city_id
            WHERE t.deleted_at IS NULL";
    $params = [];

    if ($search !== null && $search !== '') {
        $sql .= ' AND (t.name ILIKE :search_name OR t.description ILIKE :search_desc)';
        $params['search_name'] = '%' . $search . '%';
        $params['search_desc'] = '%' . $search . '%';
    }

    if ($cityId) {
        $sql .= ' AND t.city_id = :city_id';
        $params['city_id'] = $cityId;
    }

    $sql .= ' ORDER BY LOWER(t.name) ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function get_talent(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT t.*, c.city_name FROM talents t JOIN cities c ON c.id = t.city_id
         WHERE t.id = ? AND t.deleted_at IS NULL LIMIT 1'
    );
    $stmt->execute([$id]);
    $talent = $stmt->fetch();

    return $talent ?: null;
}

function get_talent_photos(int $id): array
{
    $stmt = db()->prepare(
        'SELECT * FROM talent_photos WHERE talent_id = ? AND deleted_at IS NULL
         ORDER BY is_primary DESC, sort_order ASC, id ASC'
    );
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
    $stmt = db()->prepare(
        'INSERT INTO talents (city_id, name, description, video_url, rate, status)
         VALUES (:city_id, :name, :description, :video_url, :rate, :status)'
    );
    $stmt->execute([
        'city_id' => $data['city_id'],
        'name' => $data['name'],
        'description' => $data['description'],
        'video_url' => $data['video_url'],
        'rate' => $data['rate'],
        'status' => $data['status'],
    ]);

    return (int) db()->lastInsertId();
}

function update_talent(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE talents SET city_id = :city_id, name = :name, description = :description,
         video_url = :video_url, rate = :rate, status = :status WHERE id = :id'
    );
    $stmt->execute([
        'city_id' => $data['city_id'],
        'name' => $data['name'],
        'description' => $data['description'],
        'video_url' => $data['video_url'],
        'rate' => $data['rate'],
        'status' => $data['status'],
        'id' => $id,
    ]);
}

function delete_talent(int $id): void
{
    $stmt = db()->prepare('UPDATE talents SET deleted_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
}
