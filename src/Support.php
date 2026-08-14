<?php
/**
 * Small helpers shared by every page: config access, escaping, URLs and flash
 * style messages. Deliberately plain functions — the site has no framework.
 */

declare(strict_types=1);

/**
 * Read a dot-notated key out of the configuration array.
 */
function config(string $key = '', mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/config.php';
    }

    if ($key === '') {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

/**
 * Escape a value for HTML output.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Build a site URL for a page slug, keeping the app mountable in a subfolder.
 */
function url(string $page = 'home', array $query = []): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
    $path = $base . '/';

    if ($page !== 'home') {
        $query = ['page' => $page] + $query;
    }

    return $query === [] ? $path : $path . '?' . http_build_query($query);
}

/**
 * Asset URL helper with a cache-busting stamp.
 *
 * When the host serves the repository root instead of public/, the root
 * bootstrap defines NOOR_PUBLIC_PREFIX so assets still resolve.
 */
function asset(string $path): string
{
    $base   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
    $prefix = defined('NOOR_PUBLIC_PREFIX') ? NOOR_PUBLIC_PREFIX : '';
    $path   = ltrim($path, '/');

    $file  = dirname(__DIR__) . '/public/' . $path;
    $stamp = is_file($file) ? (string) filemtime($file) : '1';

    return $base . '/' . $prefix . $path . '?v=' . $stamp;
}

/**
 * Read a trimmed string from the query string or form post.
 */
function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

/**
 * Read a float from the request, tolerating thousands separators and blanks.
 */
function inputFloat(string $key, float $default = 0.0): float
{
    $raw = str_replace([',', ' '], '', input($key));
    if ($raw === '' || !is_numeric($raw)) {
        return $default;
    }

    return (float) $raw;
}

/**
 * Format a money amount for display.
 */
function money(float $amount, string $currency = ''): string
{
    $formatted = number_format($amount, 2);

    return $currency === '' ? $formatted : $currency . ' ' . $formatted;
}

/**
 * Remember the visitor's location preferences in a cookie.
 */
function rememberLocation(string $city, string $country, int $method, int $school): void
{
    if (headers_sent()) {
        return;
    }

    $payload = json_encode(compact('city', 'country', 'method', 'school'));
    setcookie('noor_location', (string) $payload, [
        'expires'  => time() + 90 * 24 * 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Current location preferences: request values win, then the cookie, then the
 * configured defaults.
 */
function currentLocation(): array
{
    $saved = [];
    if (!empty($_COOKIE['noor_location'])) {
        $decoded = json_decode((string) $_COOKIE['noor_location'], true);
        if (is_array($decoded)) {
            $saved = $decoded;
        }
    }

    $city    = input('city', (string) ($saved['city'] ?? config('defaults.city')));
    $country = input('country', (string) ($saved['country'] ?? config('defaults.country')));
    $method  = (int) input('method', (string) ($saved['method'] ?? config('defaults.method')));
    $school  = (int) input('school', (string) ($saved['school'] ?? config('defaults.school')));

    return [
        'city'    => $city !== '' ? $city : (string) config('defaults.city'),
        'country' => $country !== '' ? $country : (string) config('defaults.country'),
        'method'  => $method,
        'school'  => in_array($school, [0, 1], true) ? $school : 1,
    ];
}

/**
 * Calculation methods offered by the AlAdhan API.
 */
function calculationMethods(): array
{
    return [
        3  => 'Muslim World League',
        2  => 'Islamic Society of North America (ISNA)',
        5  => 'Egyptian General Authority of Survey',
        4  => 'Umm al-Qura University, Makkah',
        1  => 'University of Islamic Sciences, Karachi',
        7  => 'Institute of Geophysics, University of Tehran',
        8  => 'Gulf Region',
        9  => 'Kuwait',
        10 => 'Qatar',
        11 => 'Majlis Ugama Islam Singapura',
        12 => 'Union des Organisations Islamiques de France',
        13 => 'Diyanet İşleri Başkanlığı, Turkey',
        14 => 'Spiritual Administration of Muslims of Russia',
        15 => 'Moonsighting Committee Worldwide',
    ];
}

/**
 * Escape text, then wrap every occurrence of a search term in <mark>.
 *
 * The escaping happens first, so the returned string is safe to echo raw.
 */
function highlightTerm(string $text, string $term): string
{
    $escaped = e($text);
    $term    = trim($term);
    if ($term === '') {
        return $escaped;
    }

    $pattern = '/(' . preg_quote(e($term), '/') . ')/iu';

    return (string) preg_replace($pattern, '<mark>$1</mark>', $escaped);
}

/**
 * Load a bundled JSON dataset from /data.
 */
function dataset(string $name): array
{
    $file = dirname(__DIR__) . '/data/' . basename($name) . '.json';
    if (!is_file($file)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);

    return is_array($decoded) ? $decoded : [];
}
