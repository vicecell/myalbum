<?php

function get_all_cities(): array
{
    $rows = supabase_rest('GET', 'cities', [
        'select' => '*',
        'deleted_at' => 'is.null',
    ]);

    usort($rows, fn ($a, $b) => strtolower($a['city_name']) <=> strtolower($b['city_name']));

    return $rows;
}

function count_cities(): int
{
    return supabase_rest_count('cities', ['deleted_at' => 'is.null']);
}

function get_active_cities(): array
{
    $rows = supabase_rest('GET', 'cities', [
        'select' => '*',
        'status' => 'eq.active',
        'deleted_at' => 'is.null',
    ]);

    usort($rows, fn ($a, $b) => strtolower($a['city_name']) <=> strtolower($b['city_name']));

    return $rows;
}

function get_city(int $id): ?array
{
    $rows = supabase_rest('GET', 'cities', [
        'select' => '*',
        'id' => 'eq.' . $id,
        'deleted_at' => 'is.null',
        'limit' => '1',
    ]);

    return $rows[0] ?? null;
}

function city_name_exists(string $name, ?int $excludeId = null): bool
{
    // Plain eq. filter — PostgREST takes the rest-of-value literally here (no
    // escaping needed); reserved chars only matter inside in.()/or=() lists.
    $query = [
        'select' => 'id',
        'city_name' => 'eq.' . $name,
        'deleted_at' => 'is.null',
        'limit' => '1',
    ];

    if ($excludeId !== null) {
        $query['id'] = 'neq.' . $excludeId;
    }

    return !empty(supabase_rest('GET', 'cities', $query));
}

function create_city(string $name, string $status): int
{
    $rows = supabase_rest('POST', 'cities', [], [
        'city_name' => $name,
        'status' => $status,
    ]);

    return (int) $rows[0]['id'];
}

function update_city(int $id, string $name, string $status): void
{
    supabase_rest('PATCH', 'cities', ['id' => 'eq.' . $id], [
        'city_name' => $name,
        'status' => $status,
    ]);
}

function city_has_talents(int $id): bool
{
    $rows = supabase_rest('GET', 'talents', [
        'select' => 'id',
        'city_id' => 'eq.' . $id,
        'deleted_at' => 'is.null',
        'limit' => '1',
    ]);

    return !empty($rows);
}

function delete_city(int $id): bool
{
    if (city_has_talents($id)) {
        return false;
    }

    supabase_rest('PATCH', 'cities', ['id' => 'eq.' . $id], [
        'deleted_at' => now_ts(),
    ]);

    return true;
}
