ALTER TABLE talent_photos ADD COLUMN file_size_bytes INT UNSIGNED NOT NULL DEFAULT 0 AFTER original_filename;
