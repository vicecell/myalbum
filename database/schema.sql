-- MySQL/MariaDB schema. Run against the app's local database.
--
-- Compatible with MariaDB 5.5+ (production target): no JSON column type (added
-- MariaDB 10.2), no DATETIME auto-timestamps (added MariaDB 10.0/MySQL 5.6.5;
-- pre-that, only a single TIMESTAMP column per table can auto-init/update, so
-- only created_at uses that — updated_at is plain, app-managed if ever needed).
-- CHECK constraints are parsed but silently unenforced pre-MariaDB 10.2.1 —
-- kept here as self-documentation, harmless no-op on old servers.

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL,
    UNIQUE KEY unique_admin_username (username)
) ENGINE=InnoDB;

CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(150) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    UNIQUE KEY unique_city_name (city_name)
) ENGINE=InnoDB;

CREATE TABLE talents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT NOT NULL,
    name VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    video_url VARCHAR(500) NULL,
    rate VARCHAR(100) NULL,
    -- JSON array of {"label": "...", "url": "..."} objects, stored as TEXT (no
    -- native JSON type on MariaDB 5.5) — encoded/decoded in PHP.
    links TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    CONSTRAINT fk_talents_city FOREIGN KEY (city_id) REFERENCES cities(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    KEY idx_talents_city_id (city_id),
    KEY idx_talents_name (name),
    KEY idx_talents_status (status)
) ENGINE=InnoDB;

CREATE TABLE talent_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    talent_id INT NOT NULL,
    image_url VARCHAR(700) NOT NULL,
    image_display_url VARCHAR(700) NULL,
    image_thumb_url VARCHAR(700) NULL,
    image_medium_url VARCHAR(700) NULL,
    image_delete_url VARCHAR(700) NULL,
    imgbb_id VARCHAR(700) NULL,
    original_filename VARCHAR(255) NULL,
    -- Bytes of the clean + watermarked-full objects at upload time (not
    -- updated on crop, so it drifts slightly after that — close enough for a
    -- dashboard-level estimate).
    file_size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    CONSTRAINT fk_talent_photos_talent FOREIGN KEY (talent_id) REFERENCES talents(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_talent_photos_talent_id (talent_id),
    KEY idx_talent_photos_is_primary (is_primary)
) ENGINE=InnoDB;
