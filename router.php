<?php
// Dev-only router for `php -S`. The built-in server's docroot is public/,
// but admin/ and api/ live at the project root per the blueprint's folder
// structure (kept outside public/ so they can't be listed as static files
// under a misconfigured real webserver). This maps /admin/* and /api/*
// requests to those root-level directories; everything else falls through
// to the built-in server's normal public/ handling.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('#^/(admin|api)(/.*)?$#', $uri, $matches)) {
    $relativePath = $matches[1] . ($matches[2] ?? '');
    $file = __DIR__ . '/' . $relativePath;

    if (is_dir($file)) {
        $file = rtrim($file, '/') . '/index.php';
    }

    if (substr($file, -4) === '.php' && file_exists($file)) {
        chdir(dirname($file));
        require $file;
        return true;
    }

    http_response_code(404);
    echo 'Not found';
    return true;
}

return false;
