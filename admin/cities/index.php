<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();

$cities = get_all_cities();
$flash = flash_get();

$pageTitle = 'Kota';
$activeNav = 'cities';

include __DIR__ . '/../layout/header.php';
?>
<main class="page-content">
    <?php if ($flash): ?>
        <div class="alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="page-toolbar">
        <a href="/admin/cities/create.php" class="btn btn-primary">+ Add City</a>
    </div>

    <?php if (empty($cities)): ?>
        <p class="empty-state">No cities yet.</p>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($cities as $city): ?>
                <li class="list-item">
                    <div class="list-item-main">
                        <span class="list-item-title"><?= e($city['city_name']) ?></span>
                        <span class="badge badge-<?= e($city['status']) ?>"><?= e($city['status']) ?></span>
                    </div>
                    <div class="list-item-actions">
                        <a href="/admin/cities/edit.php?id=<?= (int) $city['id'] ?>" class="btn-link">Edit</a>
                        <form method="post" action="/admin/cities/delete.php" onsubmit="return confirm('Delete this city?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $city['id'] ?>">
                            <button type="submit" class="btn-link btn-link-danger">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../layout/footer.php'; ?>
