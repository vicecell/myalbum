<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();

$stats = [
    ['label' => 'Total Talents', 'value' => (string) count_talents()],
    ['label' => 'Active Talents', 'value' => (string) count_active_talents()],
    ['label' => 'Total Cities', 'value' => (string) count_cities()],
    ['label' => 'Total Photos', 'value' => (string) count_photos()],
    ['label' => 'Photo Storage Used', 'value' => format_bytes(get_total_photo_size_bytes())],
];

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

include __DIR__ . '/layout/header.php';
?>
<main class="page-content">
    <div class="stat-grid">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
                <span class="stat-value"><?= e($stat['value']) ?></span>
                <span class="stat-label"><?= e($stat['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php include __DIR__ . '/layout/footer.php'; ?>
