<?php

/**
 * Upload to your Laravel PROJECT root, then open:
 * https://pos.yesorno.bar/check-server.php
 * DELETE this file after debugging.
 */

header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;

echo "=== TeboOS server check ===\n\n";
echo 'DOCUMENT_ROOT: '.($_SERVER['DOCUMENT_ROOT'] ?? '?')."\n";
echo 'REQUEST_URI: '.($_SERVER['REQUEST_URI'] ?? '?')."\n";
echo 'SCRIPT_FILENAME: '.($_SERVER['SCRIPT_FILENAME'] ?? '?')."\n";
echo 'PHP: '.PHP_VERSION."\n\n";

$checks = [
    'Project root' => $root,
    'public/index.php' => $root.'/public/index.php',
    'vendor/autoload.php' => $root.'/vendor/autoload.php',
    '.env' => $root.'/.env',
    'public/build/manifest.json' => $root.'/public/build/manifest.json',
];

foreach ($checks as $label => $path) {
    echo $label.': '.(file_exists($path) ? 'OK' : 'MISSING')." ($path)\n";
}

echo "\nRecommended docroot in hPanel:\n";
echo $root."/public\n";
