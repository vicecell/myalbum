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
