<?php

define('MAX_IMAGE_SIZE_MB', (int) getenv_value('MAX_IMAGE_SIZE_MB', 5));
define('ALLOWED_IMAGE_MIME_TYPES', array_map('trim', explode(',', getenv_value('ALLOWED_IMAGE_TYPES', 'image/jpeg,image/png,image/webp'))));
