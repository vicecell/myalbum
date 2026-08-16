<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();

$pageTitle = 'Setting';
$activeNav = 'settings';

include __DIR__ . '/layout/header.php';
?>
<main class="page-content">
    <div class="list-item">
        <div class="talent-meta">
            <span class="list-item-title"><?= e(APP_NAME) ?></span>
            <span class="talent-city">Logged in as <?= e($_SESSION['admin_username']) ?></span>
        </div>
    </div>
    <div class="page-toolbar">
        <a href="/logout.php" class="btn btn-primary btn-block">Logout</a>
    </div>
</main>
<?php include __DIR__ . '/layout/footer.php'; ?>
