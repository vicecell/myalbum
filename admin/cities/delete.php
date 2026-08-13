<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/cities/index.php');
    exit;
}

require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$city = get_city($id);

if (!$city) {
    flash_set('error', 'City not found.');
} elseif (!delete_city($id)) {
    flash_set('error', 'Cannot delete a city that has talents.');
} else {
    flash_set('success', 'City deleted.');
}

header('Location: /admin/cities/index.php');
exit;
