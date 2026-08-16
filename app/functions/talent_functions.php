<?php

function count_talents(): int
{
    return supabase_rest_count('talents', ['deleted_at' => 'is.null']);
}

function count_active_talents(): int
{
    return supabase_rest_count('talents', ['status' => 'eq.active', 'deleted_at' => 'is.null']);
}

function get_talents(?string $search = null, ?int $cityId = null): array
{
    $query = [
        'select' => '*,cities(city_name),talent_photos(image_thumb_url)',
        'deleted_at' => 'is.null',
        'talent_photos.is_primary' => 'eq.1',
        'talent_photos.deleted_at' => 'is.null',
    ];

    if ($cityId) {
        $query['city_id'] = 'eq.' . $cityId;
    }

    $rows = supabase_rest('GET', 'talents', $query);

    foreach ($rows as &$row) {
        $row['city_name'] = $row['cities']['city_name'] ?? null;
        $row['primary_photo'] = $row['talent_photos'][0]['image_thumb_url'] ?? null;
        unset($row['cities'], $row['talent_photos']);
    }
    unset($row);

    // Free-text search and case-insensitive sort done client-side rather than via
    // PostgREST's or=()/ilike filter DSL — avoids escaping commas/parens/periods
    // in search terms, and ORDER BY LOWER(...) isn't expressible via plain
    // order=. No pagination anywhere in the app, so fetch-all is cheap at this
    // scale (revisit if talent count grows into the hundreds).
    if ($search !== null && $search !== '') {
        $rows = array_values(array_filter($rows, function ($row) use ($search) {
            return stripos($row['name'], $search) !== false || stripos($row['description'], $search) !== false;
        }));
    }

    usort($rows, fn ($a, $b) => strtolower($a['name']) <=> strtolower($b['name']));

    return $rows;
}

function get_talent(int $id): ?array
{
    $rows = supabase_rest('GET', 'talents', [
        'select' => '*,cities(city_name)',
        'id' => 'eq.' . $id,
        'deleted_at' => 'is.null',
        'limit' => '1',
    ]);

    if (empty($rows)) {
        return null;
    }

    $talent = $rows[0];
    $talent['city_name'] = $talent['cities']['city_name'] ?? null;
    unset($talent['cities']);

    return $talent;
}

function get_talent_photos(int $id): array
{
    return supabase_rest('GET', 'talent_photos', [
        'select' => '*',
        'talent_id' => 'eq.' . $id,
        'deleted_at' => 'is.null',
        'order' => 'is_primary.desc,sort_order.asc,id.asc',
    ]);
}

function city_exists(int $id): bool
{
    $rows = supabase_rest('GET', 'cities', [
        'select' => 'id',
        'id' => 'eq.' . $id,
        'deleted_at' => 'is.null',
        'limit' => '1',
    ]);

    return !empty($rows);
}

function create_talent(array $data): int
{
    $rows = supabase_rest('POST', 'talents', [], [
        'city_id' => $data['city_id'],
        'name' => $data['name'],
        'description' => $data['description'],
        'video_url' => $data['video_url'],
        'rate' => $data['rate'],
        'links' => $data['links'] ?? [],
        'status' => $data['status'],
    ]);

    return (int) $rows[0]['id'];
}

function update_talent(int $id, array $data): void
{
    supabase_rest('PATCH', 'talents', ['id' => 'eq.' . $id], [
        'city_id' => $data['city_id'],
        'name' => $data['name'],
        'description' => $data['description'],
        'video_url' => $data['video_url'],
        'rate' => $data['rate'],
        'links' => $data['links'] ?? [],
        'status' => $data['status'],
    ]);
}

function delete_talent(int $id): void
{
    supabase_rest('PATCH', 'talents', ['id' => 'eq.' . $id], [
        'deleted_at' => now_ts(),
    ]);
}
