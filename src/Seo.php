<?php
/**
 * Search-engine plumbing: absolute URLs, the sitemap and robots.txt.
 */

declare(strict_types=1);

/**
 * Is this request being served from a development hostname?
 *
 * The configured canonical origin is meant for production. On a local or
 * staging hostname it would advertise the live domain from the wrong machine,
 * so those requests fall back to describing themselves.
 */
function isLocalRequest(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $host = (string) preg_replace('/:\d+$/', '', $host);

    if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || $host === '[::1]') {
        return true;
    }

    foreach (['.local', '.localhost', '.test', '.example'] as $suffix) {
        if (str_ends_with($host, $suffix)) {
            return true;
        }
    }

    // Private ranges, so a LAN or container address never claims the domain.
    return (bool) preg_match('/^(10\.|127\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $host);
}

/**
 * Absolute base URL of the site, with a trailing slash.
 *
 * Set app.url in config/local.php on production — it is the only value that
 * survives proxies and CDNs reliably. Otherwise it is derived from the request.
 */
function baseUrl(): string
{
    $configured = trim((string) config('app.url', ''));
    if ($configured !== '' && !isLocalRequest()) {
        return rtrim($configured, '/') . '/';
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);

    $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
    // Host headers are attacker-controlled; keep only characters a host may use.
    $host = preg_replace('/[^A-Za-z0-9.\-:]/', '', $host) ?: 'localhost';

    $dir = rtrim(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/\\');

    return ($https ? 'https' : 'http') . '://' . $host . $dir . '/';
}

/**
 * Absolute URL for a page slug.
 */
function absoluteUrl(string $page = 'home', array $query = []): string
{
    $path = $page === 'home' && $query === [] ? '' : ltrim(url($page, $query), '/');

    // url() already includes the base directory; strip it so it is not doubled.
    $base = rtrim(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/\\');
    if ($base !== '' && str_starts_with($path, ltrim($base, '/'))) {
        $path = ltrim(substr($path, strlen(ltrim($base, '/'))), '/');
    }

    return baseUrl() . $path;
}

/**
 * Canonical URL for the request currently being served.
 *
 * Only the parameters that change what is shown are kept, so a page reached
 * with tracking or display parameters still points at one canonical address.
 */
function canonicalUrl(string $page, array $keep = []): string
{
    $query = [];
    foreach ($keep as $key) {
        $value = input($key);
        if ($value !== '') {
            $query[$key] = $value;
        }
    }

    return absoluteUrl($page, $query);
}

/**
 * Every URL the sitemap should advertise.
 *
 * @return array<int, array{loc: string, changefreq: string, priority: string}>
 */
function sitemapEntries(): array
{
    // slug => [change frequency, priority]
    $pages = [
        'home'         => ['daily',   '1.0'],
        'prayer-times' => ['daily',   '0.9'],
        'quran'        => ['weekly',  '0.9'],
        'quran-search' => ['monthly', '0.6'],
        'qibla'        => ['monthly', '0.7'],
        'zakat'        => ['monthly', '0.8'],
        'fitrah'       => ['monthly', '0.8'],
        'hadith'       => ['weekly',  '0.8'],
        'duas'         => ['weekly',  '0.7'],
        'names'        => ['monthly', '0.7'],
        'calendar'     => ['daily',   '0.7'],
        'tasbih'       => ['monthly', '0.5'],
        'about'        => ['yearly',  '0.3'],
    ];

    $entries = [];
    foreach ($pages as $slug => [$frequency, $priority]) {
        $entries[] = [
            'loc'        => absoluteUrl($slug),
            'changefreq' => $frequency,
            'priority'   => $priority,
        ];
    }

    // Every surah is its own page worth indexing. Generated from the numbers
    // rather than the API, so the sitemap never depends on a network call.
    for ($surah = 1; $surah <= 114; $surah++) {
        $entries[] = [
            'loc'        => absoluteUrl('quran', ['surah' => $surah]),
            'changefreq' => 'yearly',
            'priority'   => '0.6',
        ];
    }

    // Dua categories and hadith collections are real, linkable listings.
    foreach (array_unique(array_column(dataset('duas'), 'category')) as $category) {
        $entries[] = [
            'loc'        => absoluteUrl('duas', ['category' => (string) $category]),
            'changefreq' => 'monthly',
            'priority'   => '0.5',
        ];
    }

    foreach (hadithCollections() as $collection) {
        $entries[] = [
            'loc'        => absoluteUrl('hadith', ['collection' => $collection]),
            'changefreq' => 'monthly',
            'priority'   => '0.5',
        ];
    }

    return $entries;
}

/**
 * Emit sitemap.xml and stop.
 */
function renderSitemap(): never
{
    $lastmod = date('Y-m-d');

    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach (sitemapEntries() as $entry) {
        echo "  <url>\n";
        echo '    <loc>' . e($entry['loc']) . "</loc>\n";
        echo '    <lastmod>' . $lastmod . "</lastmod>\n";
        echo '    <changefreq>' . $entry['changefreq'] . "</changefreq>\n";
        echo '    <priority>' . $entry['priority'] . "</priority>\n";
        echo "  </url>\n";
    }

    echo '</urlset>' . "\n";
    exit;
}

/**
 * Emit robots.txt and stop.
 */
function renderRobots(): never
{
    header('Content-Type: text/plain; charset=UTF-8');

    $basePath = (string) parse_url(baseUrl(), PHP_URL_PATH) ?: '/';

    $lines = [
        'User-agent: *',
        'Allow: /',
        '',
        '# Personal, and nothing for a crawler to index.',
        'Disallow: ' . $basePath . '?page=bookmarks',
        '',
        'Sitemap: ' . baseUrl() . 'sitemap.xml',
        '',
    ];

    echo implode("\n", $lines);
    exit;
}
