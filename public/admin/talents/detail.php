<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
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
                    <img src="<?= e($photo['imgbb_id'] ? supabase_render_url($photo['imgbb_id'], 100) : $photo['image_url']) ?>" data-full="<?= e($photo['image_url']) ?>" alt="" class="photo-gallery-img<?= $photo['is_primary'] ? ' is-primary' : '' ?>">
                    <div class="photo-item-actions">
                        <?php if (!$photo['is_primary']): ?>
                            <button type="button" class="btn-link set-primary-btn" data-photo-id="<?= (int) $photo['id'] ?>">Set primary</button>
                        <?php else: ?>
                            <button type="button" class="btn-link crop-btn" data-photo-id="<?= (int) $photo['id'] ?>" data-crop-source="<?= e($photo['imgbb_id'] ? supabase_render_url($photo['imgbb_id'], 1200) : $photo['image_url']) ?>">Crop</button>
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
        <label for="photo_url">Or image URL</label>
        <input type="url" id="photo_url" name="photo_url" placeholder="https://example.com/photo.jpg">
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

    <?php if (!empty($talent['links'])): ?>
        <div class="chip-row">
            <?php foreach ($talent['links'] as $link): ?>
                <a href="<?= e($link['url']) ?>" class="chip" target="_blank" rel="noopener noreferrer"><?= e($link['label']) ?></a>
            <?php endforeach; ?>
        </div>
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

<div class="lightbox crop-overlay" id="cropModal">
    <button type="button" class="lightbox-close" id="cropClose" aria-label="Close">&times;</button>
    <div class="crop-frame" id="cropFrame">
        <img src="" alt="" class="crop-img" id="cropImg" crossorigin="anonymous">
        <div class="crop-box" id="cropBox">
            <div class="crop-box-handle" id="cropBoxHandle"></div>
        </div>
    </div>
    <p class="crop-hint">Drag to move, drag corner to resize</p>
    <button type="button" class="btn btn-primary" id="cropSave">Save crop</button>
    <p id="cropStatus" class="upload-status"></p>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>
