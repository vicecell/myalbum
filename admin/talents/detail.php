<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$talent = get_talent($id);

if (!$talent) {
    http_response_code(404);
    exit('Talent not found.');
}

$photos = get_talent_photos($id);
$flash = flash_get();

$pageTitle = $talent['name'];
$activeNav = 'talents';

include __DIR__ . '/../layout/header.php';
?>
<main class="page-content">
    <?php if ($flash): ?>
        <div class="alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (empty($photos)): ?>
        <p class="empty-state">No photos yet.</p>
    <?php else: ?>
        <div class="photo-gallery">
            <?php foreach ($photos as $photo): ?>
                <div class="photo-item" data-photo-id="<?= (int) $photo['id'] ?>">
                    <img src="<?= e($photo['image_thumb_url'] ?: $photo['image_url']) ?>" data-full="<?= e($photo['image_url']) ?>" alt="" class="photo-gallery-img<?= $photo['is_primary'] ? ' is-primary' : '' ?>">
                    <div class="photo-item-actions">
                        <?php if (!$photo['is_primary']): ?>
                            <button type="button" class="btn-link set-primary-btn" data-photo-id="<?= (int) $photo['id'] ?>">Set primary</button>
                        <?php endif; ?>
                        <button type="button" class="btn-link btn-link-danger delete-photo-btn" data-photo-id="<?= (int) $photo['id'] ?>">Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form id="photoUploadForm" class="form-group" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="talent_id" value="<?= (int) $talent['id'] ?>">
        <label for="photos">Add photos (jpeg, png, webp; max <?= (int) MAX_IMAGE_SIZE_MB ?>MB each)</label>
        <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
        <button type="submit" class="btn btn-primary btn-block" id="uploadBtn">Upload</button>
        <p id="uploadStatus" class="upload-status"></p>
    </form>

    <h1><?= e($talent['name']) ?></h1>
    <p class="talent-city"><?= e($talent['city_name']) ?> &middot; <span class="badge badge-<?= e($talent['status']) ?>"><?= e($talent['status']) ?></span></p>
    <?php if (!empty($talent['rate'])): ?>
        <p class="talent-rate"><?= e($talent['rate']) ?></p>
    <?php endif; ?>
    <p class="talent-description"><?= nl2br(e($talent['description'])) ?></p>

    <?php if (!empty($talent['video_url'])): ?>
        <p><a href="<?= e($talent['video_url']) ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">Watch Video</a></p>
    <?php endif; ?>

    <div class="page-toolbar">
        <a href="/admin/talents/edit.php?id=<?= (int) $talent['id'] ?>" class="btn btn-primary">Edit</a>
        <form method="post" action="/admin/talents/delete.php" onsubmit="return confirm('Delete this talent?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $talent['id'] ?>">
            <button type="submit" class="btn-link btn-link-danger">Delete</button>
        </form>
    </div>
</main>

<div class="lightbox" id="photoLightbox">
    <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
    <button type="button" class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Previous photo">&#8249;</button>
    <img src="" alt="" class="lightbox-img" id="lightboxImg">
    <button type="button" class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Next photo">&#8250;</button>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
