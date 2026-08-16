<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/talents/index.php');
    exit;
}

require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$talent = get_talent($id);

if ($talent) {
    delete_talent($id);
    flash_set('success', 'Talent deleted.');
} else {
    flash_set('error', 'Talent not found.');
}

header('Location: /admin/talents/index.php');
exit;
