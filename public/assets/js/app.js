(function () {
    var navLinks = document.querySelectorAll('.bottom-nav .bottom-nav-item');

    if (!navLinks.length) {
        return;
    }

    navLinks.forEach(function (link) {
        if (link.getAttribute('href') === window.location.pathname) {
            link.classList.add('is-active');
        }
    });
})();

(function () {
    var uploadForm = document.getElementById('photoUploadForm');

    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var fileInput = document.getElementById('photos');
            var urlInput = document.getElementById('photo_url');

            if (!fileInput.files.length && !(urlInput && urlInput.value.trim())) {
                return;
            }

            var uploadBtn = document.getElementById('uploadBtn');
            var status = document.getElementById('uploadStatus');
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Uploading...';
            status.textContent = '';

            fetch('/api/upload-imgbb.php', { method: 'POST', body: new FormData(uploadForm) })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        window.location.reload();
                        return;
                    }

                    status.textContent = data.message || 'Upload failed.';
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Upload';
                })
                .catch(function () {
                    status.textContent = 'Upload failed.';
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Upload';
                });
        });
    }

    var csrfInput = document.querySelector('#photoUploadForm input[name="csrf_token"]');
    var csrfToken = csrfInput ? csrfInput.value : '';

    document.querySelectorAll('.delete-photo-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Delete this photo?')) {
                return;
            }

            fetch('/api/delete-photo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ photo_id: btn.dataset.photoId, csrf_token: csrfToken }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        window.location.reload();
                    }
                });
        });
    });

    document.querySelectorAll('.set-primary-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fetch('/api/set-primary-photo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ photo_id: btn.dataset.photoId, csrf_token: csrfToken }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        window.location.reload();
                    }
                });
        });
    });
})();

(function () {
    var lightbox = document.getElementById('photoLightbox');

    if (!lightbox) {
        return;
    }

    var lightboxImg = document.getElementById('lightboxImg');
    var closeBtn = document.getElementById('lightboxClose');
    var prevBtn = document.getElementById('lightboxPrev');
    var nextBtn = document.getElementById('lightboxNext');
    var photos = Array.prototype.map.call(
        document.querySelectorAll('.photo-gallery-img'),
        function (img) { return img.dataset.full || img.src; }
    );
    var currentIndex = 0;

    function showAt(index) {
        currentIndex = (index + photos.length) % photos.length;
        lightboxImg.src = photos[currentIndex];
    }

    function openLightbox(index) {
        showAt(index);
        lightbox.classList.add('is-open');
    }

    function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightboxImg.src = '';
    }

    if (photos.length > 1) {
        prevBtn.hidden = false;
        nextBtn.hidden = false;
    } else {
        prevBtn.hidden = true;
        nextBtn.hidden = true;
    }

    document.querySelectorAll('.photo-gallery-img').forEach(function (img, index) {
        img.addEventListener('click', function () {
            openLightbox(index);
        });
    });

    closeBtn.addEventListener('click', closeLightbox);
    prevBtn.addEventListener('click', function () { showAt(currentIndex - 1); });
    nextBtn.addEventListener('click', function () { showAt(currentIndex + 1); });

    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (!lightbox.classList.contains('is-open')) {
            return;
        }

        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowLeft') {
            showAt(currentIndex - 1);
        } else if (e.key === 'ArrowRight') {
            showAt(currentIndex + 1);
        }
    });
})();

(function () {
    var modal = document.getElementById('cropModal');

    if (!modal) {
        return;
    }

    var frame = document.getElementById('cropFrame');
    var img = document.getElementById('cropImg');
    var box = document.getElementById('cropBox');
    var handle = document.getElementById('cropBoxHandle');
    var closeBtn = document.getElementById('cropClose');
    var saveBtn = document.getElementById('cropSave');
    var status = document.getElementById('cropStatus');
    var csrfInput = document.querySelector('#photoUploadForm input[name="csrf_token"]');
    var csrfToken = csrfInput ? csrfInput.value : '';

    var activePhotoId = null;
    var boxRect = { x: 0, y: 0, size: 0 };
    var drag = null;
    var MIN_BOX = 40;

    function clampBox() {
        var frameW = frame.clientWidth;
        var frameH = frame.clientHeight;
        boxRect.size = Math.max(MIN_BOX, Math.min(boxRect.size, Math.min(frameW, frameH)));
        boxRect.x = Math.max(0, Math.min(boxRect.x, frameW - boxRect.size));
        boxRect.y = Math.max(0, Math.min(boxRect.y, frameH - boxRect.size));
    }

    function renderBox() {
        box.style.left = boxRect.x + 'px';
        box.style.top = boxRect.y + 'px';
        box.style.width = boxRect.size + 'px';
        box.style.height = boxRect.size + 'px';
    }

    function initBox() {
        var frameW = frame.clientWidth;
        var frameH = frame.clientHeight;
        boxRect.size = Math.min(frameW, frameH) * 0.7;
        boxRect.x = (frameW - boxRect.size) / 2;
        boxRect.y = (frameH - boxRect.size) / 2;
        renderBox();
    }

    function pointFromEvent(e) {
        return { x: e.clientX, y: e.clientY };
    }

    box.addEventListener('pointerdown', function (e) {
        if (e.target === handle) {
            return;
        }
        drag = { mode: 'move', start: pointFromEvent(e), origin: { x: boxRect.x, y: boxRect.y } };
        box.setPointerCapture(e.pointerId);
    });

    handle.addEventListener('pointerdown', function (e) {
        e.stopPropagation();
        drag = { mode: 'resize', start: pointFromEvent(e), origin: { size: boxRect.size } };
        handle.setPointerCapture(e.pointerId);
    });

    frame.addEventListener('pointermove', function (e) {
        if (!drag) {
            return;
        }

        var point = pointFromEvent(e);
        var dx = point.x - drag.start.x;
        var dy = point.y - drag.start.y;

        if (drag.mode === 'move') {
            boxRect.x = drag.origin.x + dx;
            boxRect.y = drag.origin.y + dy;
        } else {
            boxRect.size = drag.origin.size + Math.max(dx, dy);
        }

        clampBox();
        renderBox();
    });

    document.addEventListener('pointerup', function () {
        drag = null;
    });

    document.querySelectorAll('.crop-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activePhotoId = btn.dataset.photoId;
            status.textContent = '';
            img.src = btn.dataset.cropSource;
            modal.classList.add('is-open');

            img.onload = function () {
                initBox();
            };
        });
    });

    closeBtn.addEventListener('click', function () {
        modal.classList.remove('is-open');
        img.src = '';
        activePhotoId = null;
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeBtn.click();
        }
    });

    saveBtn.addEventListener('click', function () {
        if (!activePhotoId || !img.naturalWidth) {
            return;
        }

        var scale = img.naturalWidth / img.getBoundingClientRect().width;

        var sx = boxRect.x * scale;
        var sy = boxRect.y * scale;
        var ssize = boxRect.size * scale;

        var outputSize = Math.min(800, Math.round(ssize));
        var canvas = document.createElement('canvas');
        canvas.width = outputSize;
        canvas.height = outputSize;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(img, sx, sy, ssize, ssize, 0, 0, outputSize, outputSize);

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        status.textContent = '';

        canvas.toBlob(function (blob) {
            if (!blob) {
                status.textContent = 'Could not process crop.';
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save crop';
                return;
            }

            var formData = new FormData();
            formData.append('photo_id', activePhotoId);
            formData.append('csrf_token', csrfToken);
            formData.append('cropped_image', blob, 'crop.jpg');

            fetch('/api/crop-photo.php', { method: 'POST', body: formData })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        window.location.reload();
                        return;
                    }

                    status.textContent = data.message || 'Crop failed.';
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save crop';
                })
                .catch(function () {
                    status.textContent = 'Crop failed.';
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save crop';
                });
        }, 'image/jpeg', 0.9);
    });
})();
