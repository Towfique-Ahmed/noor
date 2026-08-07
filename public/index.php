<?php
/**
 * Front controller. Every request enters here, picks a page and renders it
 * inside the shared layout.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/src/Support.php';
require $root . '/src/Http.php';
require $root . '/src/Services.php';
require $root . '/src/Calculators.php';

date_default_timezone_set((string) config('app.timezone', 'UTC'));

if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

/** Pages the router will serve: slug => [file, title]. */
$routes = [
    'home'         => ['home',         'Home'],
    'prayer-times' => ['prayer-times', 'Prayer Times'],
    'quran'        => ['quran',        'Read the Qur\'an'],
    'qibla'        => ['qibla',        'Qibla Direction'],
    'zakat'        => ['zakat',        'Zakat Calculator'],
    'fitrah'       => ['fitrah',       'Fitrah Calculator'],
    'hadith'       => ['hadith',       'Hadith'],
    'duas'         => ['duas',         'Duas'],
    'names'        => ['names',        '99 Names of Allah'],
    'calendar'     => ['calendar',     'Hijri Calendar'],
    'tasbih'       => ['tasbih',       'Tasbih Counter'],
    'about'        => ['about',        'About'],
];

$slug = input('page', 'home');
if (!isset($routes[$slug])) {
    http_response_code(404);
    $slug = '404';
    [$view, $title] = ['404', 'Page not found'];
} else {
    [$view, $title] = $routes[$slug];
}

$currentPage = $slug;
$pageTitle   = $title;

// Render the page into a buffer first so a page can set $pageSubtitle or send
// its own headers before the layout writes anything.
ob_start();
require $root . '/views/pages/' . $view . '.php';
$content = (string) ob_get_clean();

require $root . '/views/layout.php';
