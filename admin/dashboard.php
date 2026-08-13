<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

$stats = [
    ['label' => 'Total Talents', 'value' => count_talents()],
    ['label' => 'Active Talents', 'value' => count_active_talents()],
    ['label' => 'Total Cities', 'value' => count_cities()],
    ['label' => 'Total Photos', 'value' => count_photos()],
];

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

include __DIR__ . '/layout/header.php';
?>
<main class="page-content">
    <div class="stat-grid">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
                <span class="stat-value"><?= (int) $stat['value'] ?></span>
                <span class="stat-label"><?= e($stat['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php include __DIR__ . '/layout/footer.php'; ?>
