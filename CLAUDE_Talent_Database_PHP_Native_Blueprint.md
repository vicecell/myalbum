# CLAUDE.md - Talent Database Mobile Admin

## 1. Project Overview

Build a mobile-first web application for managing a private talent database.

The application is used only by one internal admin. It is opened from a mobile browser, not as a native app and not as a PWA.

The app stores talent data by city or domicile, such as Jakarta, Bandung, Surabaya, and other cities. Each talent can have more than one photo. Photos are uploaded to ImgBB using the ImgBB API. The database only stores the returned image URLs and related metadata.

Video is optional and should be stored only as a URL field. Do not implement video upload in this version.

## 2. Tech Stack

Use only this stack:

- Backend: PHP Native
- Database: MySQL
- Database access: PDO prepared statements
- Frontend: HTML, Vanilla CSS, Vanilla JavaScript
- Media hosting: ImgBB API for image upload
- UI target: Mobile browser
- Authentication: PHP session-based login

Do not use Laravel, CodeIgniter, WordPress, Node.js, React, Vue, Bootstrap, Tailwind, jQuery, or other frameworks unless the user explicitly requests them later.

## 3. Core User

Only one user role exists:

- Admin

Admin can:

- Login
- View dashboard
- Manage city categories
- Manage talents
- Upload multiple photos for each talent
- Set one photo as primary photo
- Delete talent photos from local database records
- Add optional video URL
- Search talents
- Filter talents by city
- Activate or deactivate talent records
- Logout

Talent users do not login and do not edit their own profiles.

## 4. Main Data Structure

Each talent record contains only:

- Talent name
- City or domicile
- Description
- Optional video URL
- Status
- Multiple photos

All flexible details must be written inside the description field, for example:

- Age
- Gender
- Height
- Skill
- Experience
- Rate
- Contact
- Instagram
- Portfolio notes
- Availability

Do not create separate database columns for those details in MVP.

## 5. MVP Feature Scope

Build the first version with these features:

1. Admin login
2. Admin logout
3. Dashboard summary
4. City CRUD
5. Talent CRUD
6. Multiple photo upload through ImgBB API
7. Primary photo selection
8. Talent search
9. Talent filter by city
10. Talent detail page
11. Active or inactive status
12. Mobile-first interface

Do not add advanced features in MVP unless requested.

Out of scope for MVP:

- Multi-admin access
- Public talent catalog
- Talent self-registration
- Payment system
- Booking system
- Video upload
- PWA install feature
- Role and permission system
- Notification system
- AI search

## 6. Recommended User Flow

```text
Admin opens website from mobile browser
  -> Login page
  -> Dashboard
  -> View talent list
  -> Search or filter by city
  -> Add new talent
  -> Fill name, city, description, optional video URL
  -> Upload multiple photos
  -> Photos are sent to ImgBB from backend PHP
  -> ImgBB returns image URLs
  -> URLs are saved to MySQL
  -> Talent appears in list and detail page
```

## 7. Page Structure

Use this page structure:

```text
/login.php
/logout.php

/admin/dashboard.php
/admin/talents/index.php
/admin/talents/create.php
/admin/talents/edit.php
/admin/talents/detail.php
/admin/talents/delete.php

/admin/cities/index.php
/admin/cities/create.php
/admin/cities/edit.php
/admin/cities/delete.php

/admin/settings.php

/api/upload-imgbb.php
/api/delete-photo.php
/api/set-primary-photo.php
```

## 8. Recommended Folder Structure

Create this structure:

```text
talent-database/
├── app/
│   ├── config/
│   │   ├── database.php
│   │   ├── app.php
│   │   └── imgbb.php
│   ├── helpers/
│   │   ├── auth.php
│   │   ├── csrf.php
│   │   ├── flash.php
│   │   ├── sanitize.php
│   │   ├── upload.php
│   │   └── validation.php
│   ├── functions/
│   │   ├── admin_functions.php
│   │   ├── city_functions.php
│   │   ├── talent_functions.php
│   │   └── photo_functions.php
│   └── bootstrap.php
├── admin/
│   ├── dashboard.php
│   ├── layout/
│   │   ├── header.php
│   │   ├── bottom_nav.php
│   │   └── footer.php
│   ├── talents/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   ├── detail.php
│   │   └── delete.php
│   ├── cities/
│   │   ├── index.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── delete.php
│   └── settings.php
├── api/
│   ├── upload-imgbb.php
│   ├── delete-photo.php
│   └── set-primary-photo.php
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css
│   │   ├── js/
│   │   │   └── app.js
│   │   └── img/
│   ├── index.php
│   └── login.php
├── database/
│   ├── schema.sql
│   └── seed.sql
├── docs/
│   └── development-notes.md
├── .env.example
├── README.md
└── CLAUDE.md
```

## 9. Database Schema

Create `database/schema.sql` with this schema.

```sql
CREATE DATABASE IF NOT EXISTS talent_database
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE talent_database;

CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE cities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(150) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_city_name (city_name)
) ENGINE=InnoDB;

CREATE TABLE talents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    city_id INT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    video_url VARCHAR(500) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_talents_city
        FOREIGN KEY (city_id) REFERENCES cities(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    INDEX idx_talents_city_id (city_id),
    INDEX idx_talents_name (name),
    INDEX idx_talents_status (status)
) ENGINE=InnoDB;

CREATE TABLE talent_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    talent_id INT UNSIGNED NOT NULL,
    image_url VARCHAR(700) NOT NULL,
    image_display_url VARCHAR(700) NULL,
    image_thumb_url VARCHAR(700) NULL,
    image_medium_url VARCHAR(700) NULL,
    image_delete_url VARCHAR(700) NULL,
    imgbb_id VARCHAR(150) NULL,
    original_filename VARCHAR(255) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_talent_photos_talent
        FOREIGN KEY (talent_id) REFERENCES talents(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_talent_photos_talent_id (talent_id),
    INDEX idx_talent_photos_is_primary (is_primary)
) ENGINE=InnoDB;
```

Create `database/seed.sql` with basic seed data.

```sql
USE talent_database;

INSERT INTO cities (city_name, status) VALUES
('Jakarta', 'active'),
('Bandung', 'active'),
('Surabaya', 'active'),
('Yogyakarta', 'active'),
('Bali', 'active'),
('Medan', 'active'),
('Makassar', 'active'),
('Semarang', 'active'),
('Tangerang', 'active'),
('Bekasi', 'active'),
('Depok', 'active'),
('Bogor', 'active'),
('Lainnya', 'active');
```

For the admin account, do not insert plain text password manually. Create a small PHP script or setup command that generates password_hash using `password_hash()`.

Example:

```php
<?php
// Run once from terminal or temporary setup page, then delete this file.
echo password_hash('change-this-password', PASSWORD_DEFAULT);
```

## 10. Environment Configuration

Create `.env.example`:

```env
APP_NAME="Talent Database"
APP_ENV=local
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=talent_database
DB_USER=root
DB_PASS=

IMGBB_API_KEY=your_imgbb_api_key_here
MAX_IMAGE_SIZE_MB=5
ALLOWED_IMAGE_TYPES=image/jpeg,image/png,image/webp
SESSION_NAME=talent_admin_session
```

Do not commit real `.env` values.

Create simple env loader in PHP or use a minimal custom parser. Do not install external dotenv package for MVP unless requested.

## 11. ImgBB Upload Requirement

Use server-side PHP cURL to upload images to ImgBB.

Important rules:

- Never expose ImgBB API key in JavaScript.
- JavaScript can send image files to local PHP endpoint.
- PHP endpoint sends image to ImgBB.
- Save the returned ImgBB URLs into MySQL.
- Store `url`, `display_url`, `thumb.url`, `medium.url`, `delete_url`, and `id` if available.
- Validate file MIME type and file size before sending to ImgBB.
- Accept only jpeg, png, and webp for MVP.

Recommended upload flow:

```text
Browser selects image files
  -> JavaScript sends files with FormData to /api/upload-imgbb.php
  -> PHP validates session, CSRF, file size, MIME type
  -> PHP converts file to base64 or sends multipart image field
  -> PHP posts to https://api.imgbb.com/1/upload?key=IMGBB_API_KEY
  -> ImgBB returns JSON response
  -> PHP saves image metadata to talent_photos table
  -> PHP returns JSON success response to browser
  -> Browser updates photo preview list
```

## 12. Example ImgBB Upload Function

Implement a helper similar to this in `app/helpers/upload.php`.

```php
<?php

function uploadImageToImgBB(string $tmpFilePath, string $originalName): array
{
    $apiKey = getenv_value('IMGBB_API_KEY');

    if (!$apiKey) {
        throw new RuntimeException('ImgBB API key is not configured.');
    }

    if (!is_uploaded_file($tmpFilePath) && !file_exists($tmpFilePath)) {
        throw new RuntimeException('Uploaded file not found.');
    }

    $imageData = base64_encode(file_get_contents($tmpFilePath));

    $postFields = [
        'image' => $imageData,
        'name' => pathinfo($originalName, PATHINFO_FILENAME),
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.imgbb.com/1/upload?key=' . urlencode($apiKey),
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('ImgBB upload failed: ' . $curlError);
    }

    $decoded = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300 || empty($decoded['success'])) {
        throw new RuntimeException('ImgBB upload failed: ' . $response);
    }

    return $decoded['data'];
}
```

## 13. Security Requirements

Implement these security rules:

### Authentication

- Use PHP sessions.
- Use `password_hash()` and `password_verify()`.
- Regenerate session ID after successful login.
- Protect all `/admin` and `/api` routes.
- Redirect unauthenticated users to login page.

### CSRF Protection

- Generate CSRF token in session.
- Validate CSRF token for all POST requests.
- Include CSRF token in forms and AJAX upload requests.

### Database Security

- Use PDO prepared statements only.
- Do not concatenate user input into SQL.
- Set PDO error mode to exception.

### Output Security

- Escape output using `htmlspecialchars()`.
- Sanitize video URL before rendering.
- Validate URL format for video URL.

### File Upload Security

- Validate MIME type using `finfo_file()`.
- Validate file size.
- Reject SVG, GIF, PHP files, HTML files, and unknown MIME types.
- Do not store uploaded images on local server except temporary upload file.
- Do not expose local file paths.

### Configuration Security

- Never commit real `.env` file.
- Never put ImgBB API key in frontend JavaScript.
- Use `.env.example` only as template.

## 14. UI and UX Direction

The application must be mobile-first.

Target screen:

- Mobile browser width 360px to 430px
- Still usable on tablet and desktop, but desktop is not the main focus

Recommended UI style:

- Clean mobile admin interface
- Card-based talent list
- Large touch-friendly buttons
- Sticky top header
- Bottom navigation
- Floating add button for new talent
- Horizontal city filter chips
- Single-column forms
- Photo grid preview
- Clear empty states
- Simple confirmation dialogs

Bottom navigation:

```text
Dashboard | Talent | Kota | Setting
```

Primary screens:

### Login

- App title
- Username input
- Password input
- Login button
- Error message area

### Dashboard

Show summary cards:

- Total talents
- Active talents
- Total cities
- Total photos

### Talent List

Include:

- Search input
- City filter chips
- Talent card list
- Primary photo thumbnail
- Talent name
- City name
- Status badge
- Quick actions: detail, edit

### Talent Detail

Include:

- Photo gallery
- Talent name
- City
- Description
- Optional video link button
- Status
- Edit button

### Talent Form

Fields:

- Name
- City dropdown
- Description textarea
- Optional video URL
- Status dropdown
- Multiple photo upload
- Photo preview
- Save button

### City List

Include:

- City name
- Status
- Edit action
- Delete action only if no talent uses the city

## 15. JavaScript Requirements

Use Vanilla JavaScript only.

Needed JS behavior:

- Mobile menu or bottom navigation active state
- Photo preview before upload
- AJAX photo upload to local PHP endpoint
- Upload progress/loading state
- Remove photo from preview
- Confirm delete
- Set primary photo with button
- Character counter for description if desired
- Form validation before submit

Do not use jQuery.

## 16. API Endpoints Inside This Project

These are local PHP endpoints, not public APIs.

### POST `/api/upload-imgbb.php`

Purpose:

Upload one or more images to ImgBB and save results to database.

Request:

- `talent_id`
- `photos[]`
- `csrf_token`

Response success:

```json
{
  "success": true,
  "message": "Photos uploaded successfully.",
  "photos": [
    {
      "id": 1,
      "image_url": "https://...",
      "thumb_url": "https://...",
      "is_primary": 0
    }
  ]
}
```

Response error:

```json
{
  "success": false,
  "message": "Upload failed."
}
```

### POST `/api/delete-photo.php`

Purpose:

Delete photo record from MySQL.

Important:

- MVP may delete only local database record.
- Keep `image_delete_url` stored for reference.
- If later requested, implement remote deletion logic carefully.

### POST `/api/set-primary-photo.php`

Purpose:

Set one photo as the primary talent photo.

Rules:

- Set all photos for the talent to `is_primary = 0`.
- Set selected photo to `is_primary = 1`.
- Use transaction.

## 17. PHP Coding Standards

Use simple procedural PHP with clear helper functions.

Rules:

- Keep files small and focused.
- Put repeated logic in helper or function files.
- Use strict validation.
- Use early returns for error handling.
- Use clear variable names.
- Avoid mixing too much business logic inside HTML templates.
- Use `require_once` for bootstrap and helper files.

Recommended basic pattern for admin pages:

```php
<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_admin();

// page logic here
?>

<?php include __DIR__ . '/../layout/header.php'; ?>
<!-- HTML here -->
<?php include __DIR__ . '/../layout/footer.php'; ?>
```

## 18. Database Connection Pattern

Create `app/config/database.php`:

```php
<?php

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv_value('DB_HOST');
    $port = getenv_value('DB_PORT') ?: '3306';
    $name = getenv_value('DB_NAME');
    $user = getenv_value('DB_USER');
    $pass = getenv_value('DB_PASS');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
```

## 19. Validation Rules

Talent validation:

- name: required, max 180 chars
- city_id: required, must exist in cities table
- description: required
- video_url: optional, valid URL if filled
- status: active or inactive only

City validation:

- city_name: required, max 150 chars, unique
- status: active or inactive only

Photo validation:

- max image size: from `MAX_IMAGE_SIZE_MB`
- allowed MIME: image/jpeg, image/png, image/webp
- reject invalid upload error codes

## 20. Development Milestones

Implement project in this order.

### Milestone 1 - Project Foundation

Create:

- Folder structure
- Bootstrap file
- Environment loader
- Database connection
- Basic CSS
- Basic layout
- Login page UI

Acceptance criteria:

- Project opens in browser
- No fatal PHP errors
- CSS loads correctly
- Database connection can be tested

### Milestone 2 - Authentication

Create:

- Admin login
- Logout
- Session protection
- Password verification
- CSRF helper

Acceptance criteria:

- Admin can login
- Admin can logout
- Admin pages are blocked when not logged in
- Session ID regenerates on login

### Milestone 3 - City CRUD

Create:

- City list
- Add city
- Edit city
- Delete city if unused
- Active/inactive status

Acceptance criteria:

- Admin can manage city categories from mobile browser
- City cannot be deleted if used by talents

### Milestone 4 - Talent CRUD

Create:

- Talent list
- Add talent
- Edit talent
- Detail talent
- Delete talent
- Search by name/description
- Filter by city

Acceptance criteria:

- Admin can create, update, view, and delete talent data
- Search and city filter work
- Talent form is mobile-friendly

### Milestone 5 - ImgBB Photo Upload

Create:

- Backend upload helper
- Local upload API endpoint
- Multiple photo upload
- Photo preview
- Save image metadata into MySQL
- Set primary photo
- Delete local photo record

Acceptance criteria:

- Admin can upload more than one photo per talent
- Photos are visible in talent detail
- Primary photo appears in talent list card
- ImgBB API key is not visible in browser source

### Milestone 6 - Dashboard and Polish

Create:

- Dashboard summary
- Empty states
- Flash messages
- Confirmation modals
- Mobile UI polish
- Error handling

Acceptance criteria:

- Application feels usable on mobile browser
- Main flows are clear
- Error messages are understandable

## 21. Testing Checklist

Before saying the project is done, test:

### Login

- Wrong password fails
- Correct password works
- Logout works
- Admin pages redirect if not logged in

### Cities

- Add city
- Edit city
- Deactivate city
- Delete unused city
- Prevent delete when city has talent

### Talents

- Add talent
- Edit talent
- Delete talent
- Search talent
- Filter by city
- View detail
- Add video URL
- Empty video URL still works

### Photos

- Upload one photo
- Upload multiple photos
- Reject invalid file type
- Reject file too large
- Set primary photo
- Delete photo record
- Talent list uses primary photo

### Security

- CSRF token required for POST
- SQL injection attempt does not work
- Uploaded PHP file is rejected
- ImgBB API key is not shown in frontend
- HTML inside description is escaped when displayed

## 22. Important Implementation Notes for Claude

When implementing this project:

1. Read this entire file first.
2. Do not build unrelated features.
3. Do not add frameworks.
4. Do not expose secrets in frontend.
5. Implement milestone by milestone.
6. After each milestone, summarize what files were created or changed.
7. If there is ambiguity, make the safest simple choice and document it.
8. Use mobile-first CSS from the beginning.
9. Keep the project easy to deploy on standard shared hosting or VPS with PHP and MySQL.
10. Prioritize clarity, maintainability, and security over clever architecture.

## 23. Suggested First Prompt to Use in Claude

Use this prompt after placing this file in the root project as `CLAUDE.md`:

```text
Read CLAUDE.md completely.

I want to build this project using PHP Native, MySQL, Vanilla CSS, and Vanilla JavaScript.

Start with Milestone 1 only.
Create the project foundation, folder structure, bootstrap file, database connection, environment example, login page UI skeleton, and base mobile-first CSS.

Do not implement all features yet.
After generating Milestone 1, explain the files created and how to run the project locally.
```

## 24. Suggested Local Run Command

For simple local testing:

```bash
php -S localhost:8000 -t public
```

Open:

```text
http://localhost:8000
```

## 25. Expected Final Result

The final MVP should be a simple mobile web admin app where one admin can manage a private database of talents by city, write all talent details in a flexible description field, upload multiple photos through ImgBB, store the image URLs in MySQL, and browse/search/filter talent records easily from a mobile browser.
