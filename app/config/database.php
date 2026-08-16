<?php
// Talks to Supabase's PostgREST HTTP API (SUPABASE_URL/rest/v1/...) instead of
// a native Postgres connection. Production's CentOS 7 system libpq is too old
// for SCRAM auth and PGDG dropped EL-7 support, so raw PDO/libpq is a dead end
// there; plain HTTPS+JSON works everywhere curl works.

function supabase_rest(string $method, string $path, array $query = [], $body = null): array
{
    if (!SUPABASE_URL || !SUPABASE_SERVICE_KEY) {
        throw new RuntimeException('Supabase is not configured.');
    }

    $url = SUPABASE_URL . '/rest/v1/' . $path;

    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    $headers = [
        'apikey: ' . SUPABASE_SERVICE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
        'Content-Type: application/json',
    ];

    if ($body !== null && in_array($method, ['POST', 'PATCH'], true)) {
        $prefer = 'return=representation';

        if (isset($query['on_conflict'])) {
            $prefer = 'resolution=merge-duplicates,' . $prefer;
        }

        $headers[] = 'Prefer: ' . $prefer;
    }

    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ];

    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($body);
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Supabase request failed: ' . $curlError);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException('Supabase request failed [' . $httpCode . ']: ' . supabase_error_message($response));
    }

    return json_decode($response, true) ?: [];
}

function supabase_rest_count(string $path, array $query = []): int
{
    if (!SUPABASE_URL || !SUPABASE_SERVICE_KEY) {
        throw new RuntimeException('Supabase is not configured.');
    }

    $url = SUPABASE_URL . '/rest/v1/' . $path;

    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    $responseHeaders = [];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => 'HEAD',
        CURLOPT_NOBODY => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'Prefer: count=exact',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HEADERFUNCTION => function ($curl, $headerLine) use (&$responseHeaders) {
            $parts = explode(':', $headerLine, 2);

            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }

            return strlen($headerLine);
        },
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Supabase count request failed: ' . $curlError);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException('Supabase count request failed [' . $httpCode . ']');
    }

    $range = $responseHeaders['content-range'] ?? '';
    $slashPos = strpos($range, '/');

    if ($slashPos === false) {
        return 0;
    }

    $total = substr($range, $slashPos + 1);

    return $total === '*' ? 0 : (int) $total;
}

function supabase_error_message(string $rawResponse): string
{
    $decoded = json_decode($rawResponse, true);

    if (!is_array($decoded) || !isset($decoded['message'])) {
        return $rawResponse;
    }

    $message = $decoded['message'];

    if (!empty($decoded['details'])) {
        $message .= ' (' . $decoded['details'] . ')';
    }

    if (!empty($decoded['hint'])) {
        $message .= ' [hint: ' . $decoded['hint'] . ']';
    }

    return $message;
}

function now_ts(): string
{
    return gmdate('Y-m-d\TH:i:s\Z');
}
