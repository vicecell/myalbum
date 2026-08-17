<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
require_admin();

$search = trim($_GET['q'] ?? '');
$cityId = isset($_GET['city_id']) && $_GET['city_id'] !== '' ? (int) $_GET['city_id'] : null;

$talents = get_talents($search !== '' ? $search : null, $cityId);
$cities = get_active_cities();
$flash = flash_get();

$pageTitle = 'Talent';
$activeNav = 'talents';

include __DIR__ . '/../layout/header.php';
?>
<main class="page-content page-content-fixed-toolbar">
    <?php if ($flash): ?>
        <div class="alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="chip-row">
        <a href="/admin/talents/index.php<?= $search ? '?q=' . urlencode($search) : '' ?>" class="chip<?= !$cityId ? ' is-active' : '' ?>">All</a>
        <?php foreach ($cities as $city): ?>
            <a href="/admin/talents/index.php?city_id=<?= (int) $city['id'] ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="chip<?= $cityId === (int) $city['id'] ? ' is-active' : '' ?>"><?= e($city['city_name']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($talents)): ?>
        <p class="empty-state">No talents found.</p>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($talents as $talent): ?>
                <li class="list-item talent-card">
                    <a href="/admin/talents/detail.php?id=<?= (int) $talent['id'] ?>" class="talent-card-link">
                        <img src="<?= e($talent['primary_photo'] ?: '/assets/img/placeholder.svg') ?>" alt="" class="talent-thumb">
                        <div class="talent-meta">
                            <span class="list-item-title"><?= e($talent['name']) ?></span>
                            <span class="talent-city"><?= e($talent['city_name']) ?><?php if (!empty($talent['rate'])): ?> - <span class="talent-rate"><?= e($talent['rate']) ?></span><?php endif; ?></span>
                        </div>
                    </a>
                    <div class="list-item-actions">
                        <a href="/admin/talents/edit.php?id=<?= (int) $talent['id'] ?>" class="btn-link" aria-label="Edit">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

<div class="talent-fixed-toolbar">
    <a href="/admin/talents/create.php" class="btn btn-primary btn-block">+ Add Talent</a>
    <form method="get" action="/admin/talents/index.php" class="form-group">
        <input type="search" name="q" placeholder="Search talent..." value="<?= e($search) ?>">
        <?php if ($cityId): ?><input type="hidden" name="city_id" value="<?= (int) $cityId ?>"><?php endif; ?>
    </form>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
