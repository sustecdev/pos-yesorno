<?php

/**
 * Fallback front controller when the web root is the Laravel project folder.
 * Prefer .htaccess rewrites; this ensures the root URL still boots the app.
 */
require __DIR__.'/public/index.php';
