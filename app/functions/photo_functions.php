<?php

function count_photos(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM talent_photos WHERE deleted_at IS NULL')->fetchColumn();
}

function get_total_photo_size_bytes(): int
{
    return (int) db()->query('SELECT COALESCE(SUM(file_size_bytes), 0) FROM talent_photos WHERE deleted_at IS NULL')->fetchColumn();
}

function get_talent_photo(int $photoId): ?array
{
    $stmt = db()->prepare('SELECT * FROM talent_photos WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$photoId]);

    return $stmt->fetch() ?: null;
}

function talent_has_any_photo(int $talentId): bool
{
    $stmt = db()->prepare('SELECT id FROM talent_photos WHERE talent_id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$talentId]);

    return (bool) $stmt->fetch();
}

function insert_talent_photo(int $talentId, array $uploadData, ?string $originalName, bool $isPrimary): int
{
    $stmt = db()->prepare('INSERT INTO talent_photos
        (talent_id, image_url, image_display_url, image_thumb_url, image_delete_url, imgbb_id, original_filename, file_size_bytes, image_bytes, is_primary)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $talentId,
        $uploadData['url'] ?? '',
        $uploadData['url'] ?? null,
        $uploadData['thumb_url'] ?? $uploadData['url'] ?? null,
        null,
        $uploadData['path'] ?? null,
        $originalName,
        $uploadData['bytes'] ?? 0,
        $uploadData['full_bytes'] ?? 0,
        $isPrimary ? 1 : 0,
    ]);

    return (int) db()->lastInsertId();
}

function delete_talent_photo_record(int $photoId): void
{
    $stmt = db()->prepare('UPDATE talent_photos SET deleted_at = ? WHERE id = ?');
    $stmt->execute([now_ts(), $photoId]);
}

function update_photo_source(int $photoId, string $objectPath, string $thumbUrl): void
{
    // Cropping replaces both the "clean" source (imgbb_id, used as the next
    // crop's source image) and the stored thumb URL — ImgBB has no on-the-fly
    // transform, so the thumb has to be regenerated and re-stored.
    $stmt = db()->prepare('UPDATE talent_photos SET imgbb_id = ?, image_thumb_url = ? WHERE id = ?');
    $stmt->execute([$objectPath, $thumbUrl, $photoId]);
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
