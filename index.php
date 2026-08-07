<?php
/**
 * Root bootstrap for hosts that serve the repository root.
 *
 * The real document root is public/. Panels such as xCloud, cPanel and Plesk
 * often point the web root at the repository itself, which leaves nginx with
 * no index file to serve and returns "403 Forbidden". This file forwards those
 * requests to the front controller.
 *
 * Pointing the web root at public/ is still the better setup — it keeps
 * config/, src/, views/ and storage/ outside the served tree entirely.
 */

declare(strict_types=1);

// Tells asset() that CSS and JS live one directory down.
define('NOOR_PUBLIC_PREFIX', 'public/');

require __DIR__ . '/public/index.php';
