<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();

$errors = [];
$cityName = '';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $cityName = trim($_POST['city_name'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (!validate_required($cityName)) {
        $errors[] = 'City name is required.';
    } elseif (!validate_max_length($cityName, 150)) {
        $errors[] = 'City name must be 150 characters or fewer.';
    } elseif (city_name_exists($cityName)) {
        $errors[] = 'City name already exists.';
    }

    if (!validate_in_list($status, ['active', 'inactive'])) {
        $errors[] = 'Invalid status.';
    }

    if (empty($errors)) {
        create_city($cityName, $status);
        flash_set('success', 'City created.');
        header('Location: /admin/cities/index.php');
        exit;
    }
}

$pageTitle = 'Add City';
$activeNav = 'cities';

include __DIR__ . '/../layout/header.php';
?>
<main class="page-content">
    <?php foreach ($errors as $error): ?>
        <div class="alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" action="/admin/cities/create.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="city_name">City name</label>
            <input type="text" id="city_name" name="city_name" maxlength="150" value="<?= e($cityName) ?>" required>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Save</button>
    </form>
</main>
<?php include __DIR__ . '/../layout/footer.php'; ?>
