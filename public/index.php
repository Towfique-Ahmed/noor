<?php
/**
 * Front controller. Every request enters here, picks a page and renders it
 * inside the shared layout.
 */

declare(strict_types=1);

// Marks a legitimate request. View files refuse to run without it, so they
// cannot be reached directly when the host serves the repository root.
define('NOOR', true);

$root = dirname(__DIR__);

// require_once, so the site is unharmed if a Composer autoloader has already
// pulled these in — the helpers are plain functions and cannot be redeclared.
require_once $root . '/src/Support.php';
require_once $root . '/src/Http.php';
require_once $root . '/src/Services.php';
require_once $root . '/src/Calculators.php';
require_once $root . '/src/Seo.php';

date_default_timezone_set((string) config('app.timezone', 'UTC'));

if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Crawler files are served by path, so /sitemap.xml and /robots.txt work
// whether or not a rewrite rule is in play.
$path = strtolower(basename(parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: ''));

if ($path === 'sitemap.xml' || input('page') === 'sitemap.xml') {
    renderSitemap();
}
if ($path === 'robots.txt' || input('page') === 'robots.txt') {
    renderRobots();
}

/** Pages the router will serve: slug => [file, title]. */
$routes = [
    'home'         => ['home',         'Home'],
    'prayer-times' => ['prayer-times', 'Prayer Times'],
    'quran'        => ['quran',        'Read the Qur\'an'],
    'quran-search' => ['quran-search', 'Search the Qur\'an'],
    'bookmarks'    => ['bookmarks',    'Bookmarks'],
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

// Query parameters that genuinely change what a page shows. Anything else —
// display preferences, tracking tags — collapses onto the canonical address.
$canonicalParams = [
    'quran'        => ['surah'],
    'quran-search' => ['q'],
    'duas'         => ['category'],
    'hadith'       => ['collection', 'q'],
];

$canonical = $slug === '404'
    ? absoluteUrl('home')
    : canonicalUrl($slug, $canonicalParams[$slug] ?? []);

// Unique title and description per page, refined by surah, category or search
// term where the page has one.
$meta            = pageMeta($slug);
$metaTitle       = $meta['title'];
$pageDescription = $meta['description'];

// Render the page into a buffer first so a page can set $pageSubtitle or send
// its own headers before the layout writes anything.
ob_start();
require $root . '/views/pages/' . $view . '.php';
$content = (string) ob_get_clean();

require $root . '/views/layout.php';
