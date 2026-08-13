<?php

function count_photos(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM talent_photos WHERE deleted_at IS NULL')->fetchColumn();
}

function get_talent_photo(int $photoId): ?array
{
    $stmt = db()->prepare('SELECT * FROM talent_photos WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch();

    return $photo ?: null;
}

function talent_has_any_photo(int $talentId): bool
{
    $stmt = db()->prepare('SELECT id FROM talent_photos WHERE talent_id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$talentId]);

    return (bool) $stmt->fetch();
}

function insert_talent_photo(int $talentId, array $uploadData, ?string $originalName, bool $isPrimary): int
{
    $stmt = db()->prepare(
        'INSERT INTO talent_photos
            (talent_id, image_url, image_display_url, image_thumb_url, image_medium_url, image_delete_url, imgbb_id, original_filename, is_primary)
         VALUES (:talent_id, :image_url, :display_url, :thumb_url, :medium_url, :delete_url, :storage_path, :original_filename, :is_primary)'
    );
    $stmt->execute([
        'talent_id' => $talentId,
        'image_url' => $uploadData['url'] ?? '',
        'display_url' => $uploadData['url'] ?? null,
        'thumb_url' => $uploadData['thumb_url'] ?? $uploadData['url'] ?? null,
        'medium_url' => $uploadData['medium_url'] ?? $uploadData['url'] ?? null,
        'delete_url' => null,
        'storage_path' => $uploadData['path'] ?? null,
        'original_filename' => $originalName,
        'is_primary' => $isPrimary ? 1 : 0,
    ]);

    return (int) db()->lastInsertId();
}

function delete_talent_photo_record(int $photoId): void
{
    $stmt = db()->prepare('UPDATE talent_photos SET deleted_at = NOW() WHERE id = ?');
    $stmt->execute([$photoId]);
}

function set_primary_photo(int $talentId, int $photoId): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('UPDATE talent_photos SET is_primary = 0 WHERE talent_id = ? AND deleted_at IS NULL');
        $stmt->execute([$talentId]);

        $stmt = $pdo->prepare('UPDATE talent_photos SET is_primary = 1 WHERE id = ? AND talent_id = ? AND deleted_at IS NULL');
        $stmt->execute([$photoId, $talentId]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
