ALTER TABLE talent_photos ADD COLUMN image_bytes INT UNSIGNED NOT NULL DEFAULT 0 AFTER file_size_bytes;
