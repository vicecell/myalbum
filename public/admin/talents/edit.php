<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$talent = get_talent($id);

if (!$talent) {
    http_response_code(404);
    exit('Talent not found.');
}

$errors = [];
$name = $talent['name'];
$cityId = (int) $talent['city_id'];
$description = $talent['description'];
$videoUrl = $talent['video_url'] ?? '';
$rate = $talent['rate'] ?? '';
$status = $talent['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $cityId = (int) ($_POST['city_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $videoUrl = trim($_POST['video_url'] ?? '');
    $rate = trim($_POST['rate'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (!validate_required($name)) {
        $errors[] = 'Name is required.';
    } elseif (!validate_max_length($name, 180)) {
        $errors[] = 'Name must be 180 characters or fewer.';
    }

    if (!$cityId || !city_exists($cityId)) {
        $errors[] = 'Please select a valid city.';
    }

    if (!validate_required($description)) {
        $errors[] = 'Description is required.';
    }

    if (!validate_url_or_empty($videoUrl)) {
        $errors[] = 'Video URL must be a valid http(s) URL.';
    }

    if ($rate !== '' && !validate_max_length($rate, 100)) {
        $errors[] = 'Rate must be 100 characters or fewer.';
    }

    if (!validate_in_list($status, ['active', 'inactive'])) {
        $errors[] = 'Invalid status.';
    }

    if (empty($errors)) {
        update_talent($id, [
            'city_id' => $cityId,
            'name' => $name,
            'description' => $description,
            'video_url' => sanitize_video_url($videoUrl),
            'rate' => $rate !== '' ? $rate : null,
            'status' => $status,
        ]);
        flash_set('success', 'Talent updated.');
        header('Location: /admin/talents/detail.php?id=' . $id);
        exit;
    }
}

$cities = get_active_cities();
$pageTitle = 'Edit Talent';
$activeNav = 'talents';

include __DIR__ . '/../layout/header.php';
?>
<main class="page-content">
    <?php foreach ($errors as $error): ?>
        <div class="alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" action="/admin/talents/edit.php?id=<?= (int) $id ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" maxlength="180" value="<?= e($name) ?>" required>
        </div>
        <div class="form-group">
            <label for="city_id">City</label>
            <select id="city_id" name="city_id" required>
                <option value="">Select city</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= (int) $city['id'] ?>" <?= $cityId === (int) $city['id'] ? 'selected' : '' ?>><?= e($city['city_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?= e($description) ?></textarea>
        </div>
        <div class="form-group">
            <label for="video_url">Video URL (optional)</label>
            <input type="url" id="video_url" name="video_url" value="<?= e($videoUrl) ?>">
        </div>
        <div class="form-group">
            <label for="rate">Rate (optional)</label>
            <input type="text" id="rate" name="rate" maxlength="100" value="<?= e($rate) ?>" placeholder="e.g. 1jt / nego">
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
