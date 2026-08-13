<?php

define('SUPABASE_URL', rtrim(getenv_value('SUPABASE_URL', ''), '/'));
define('SUPABASE_SERVICE_KEY', getenv_value('SUPABASE_SERVICE_KEY', ''));
define('SUPABASE_BUCKET', getenv_value('SUPABASE_BUCKET', 'talent-photos'));
