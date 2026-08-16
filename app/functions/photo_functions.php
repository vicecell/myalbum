<?php

function count_photos(): int
{
    return supabase_rest_count('talent_photos', ['deleted_at' => 'is.null']);
}

function get_talent_photo(int $photoId): ?array
{
    $rows = supabase_rest('GET', 'talent_photos', [
        'select' => '*',
        'id' => 'eq.' . $photoId,
        'deleted_at' => 'is.null',
        'limit' => '1',
    ]);

    return $rows[0] ?? null;
}

function talent_has_any_photo(int $talentId): bool
{
    $rows = supabase_rest('GET', 'talent_photos', [
        'select' => 'id',
        'talent_id' => 'eq.' . $talentId,
        'deleted_at' => 'is.null',
        'limit' => '1',
    ]);

    return !empty($rows);
}

function insert_talent_photo(int $talentId, array $uploadData, ?string $originalName, bool $isPrimary): int
{
    $rows = supabase_rest('POST', 'talent_photos', [], [
        'talent_id' => $talentId,
        'image_url' => $uploadData['url'] ?? '',
        'image_display_url' => $uploadData['url'] ?? null,
        'image_thumb_url' => $uploadData['thumb_url'] ?? $uploadData['url'] ?? null,
        'image_medium_url' => $uploadData['medium_url'] ?? $uploadData['url'] ?? null,
        'image_delete_url' => null,
        'imgbb_id' => $uploadData['path'] ?? null,
        'original_filename' => $originalName,
        'is_primary' => $isPrimary ? 1 : 0,
    ]);

    return (int) $rows[0]['id'];
}

function delete_talent_photo_record(int $photoId): void
{
    supabase_rest('PATCH', 'talent_photos', ['id' => 'eq.' . $photoId], [
        'deleted_at' => now_ts(),
    ]);
}

function update_photo_thumb(int $photoId, string $thumbUrl): void
{
    supabase_rest('PATCH', 'talent_photos', ['id' => 'eq.' . $photoId], [
        'image_thumb_url' => $thumbUrl,
        'image_medium_url' => $thumbUrl,
    ]);
}

function set_primary_photo(int $talentId, int $photoId): void
{
    // Runs as a single Postgres transaction inside the DB function itself (see
    // database/schema.sql) via PostgREST's RPC endpoint — keeps the unset-all
    // then set-one swap atomic without a native Postgres client connection.
    supabase_rest('POST', 'rpc/set_primary_photo', [], [
        'p_talent_id' => $talentId,
        'p_photo_id' => $photoId,
    ]);
}
