<?php
/**
 * Clean URL routing.
 *
 * Pages are addressed by path — /prayer-times, /quran/al-baqarah — rather than
 * by query string. Settings that only change how a page is displayed
 * (translation, reciter, city, calculation method) stay in the query string,
 * because they are not separate documents.
 */

declare(strict_types=1);

/**
 * Turn a label into a URL slug: "Ali 'Imran" becomes "ali-imran".
 */
function slugify(string $text): string
{
    $text = (string) preg_replace('/[\'’`]/u', '', $text);
    $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);

    return strtolower(trim($text, '-'));
}

/**
 * Surah slugs mapped to their numbers, and the reverse.
 *
 * @return array{to_number: array<string, int>, to_slug: array<int, string>}
 */
function surahSlugs(): array
{
    static $maps = null;
    if ($maps !== null) {
        return $maps;
    }

    $toNumber = [];
    $toSlug   = [];
    foreach (dataset('surahs') as $surah) {
        $number = (int) $surah['number'];
        $slug   = slugify((string) $surah['name']);
        $toNumber[$slug] = $number;
        $toSlug[$number] = $slug;
    }

    return $maps = ['to_number' => $toNumber, 'to_slug' => $toSlug];
}

/**
 * The slug for a surah number, e.g. 2 becomes "al-baqarah".
 */
function surahSlug(int $number): string
{
    return surahSlugs()['to_slug'][$number] ?? (string) $number;
}

/**
 * Slug to original label for the dua categories and hadith collections.
 *
 * @return array<string, string>
 */
function labelSlugs(string $kind): array
{
    static $cache = [];
    if (isset($cache[$kind])) {
        return $cache[$kind];
    }

    $labels = $kind === 'duas'
        ? array_unique(array_column(dataset('duas'), 'category'))
        : hadithCollections();

    $map = [];
    foreach ($labels as $label) {
        $map[slugify((string) $label)] = (string) $label;
    }

    return $cache[$kind] = $map;
}

/**
 * Split a page and its parameters into a clean path plus leftover query.
 *
 * Parameters that identify a distinct document become path segments; the rest
 * stay in the query string.
 *
 * @return array{0: string, 1: array<string, mixed>}
 */
function routePath(string $page, array $query): array
{
    $segments = [];

    switch ($page) {
        case 'home':
            break;

        case 'quran-search':
            $segments = ['quran', 'search'];
            break;

        case 'quran':
            $segments = ['quran'];
            if (isset($query['surah']) && (int) $query['surah'] >= 1 && (int) $query['surah'] <= 114) {
                $segments[] = surahSlug((int) $query['surah']);
            }
            unset($query['surah']);
            break;

        case 'duas':
            $segments = ['duas'];
            if (!empty($query['category'])) {
                $segments[] = slugify((string) $query['category']);
            }
            unset($query['category']);
            break;

        case 'hadith':
            $segments = ['hadith'];
            if (!empty($query['collection'])) {
                $segments[] = slugify((string) $query['collection']);
            }
            unset($query['collection']);
            break;

        default:
            $segments = [$page];
    }

    return [implode('/', array_map('rawurlencode', $segments)), $query];
}

/**
 * Work out which page a request path is asking for.
 *
 * Returns the page slug, the parameters recovered from the path, and — when
 * the request came in on an older or non-canonical address — the clean URL it
 * should be redirected to.
 *
 * @return array{page: string, params: array<string, string>, redirect: ?string}
 */
function resolveRequest(array $routes): array
{
    $base = rtrim(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/\\');
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }

    $segments = array_values(array_filter(explode('/', trim(rawurldecode($path), '/')), 'strlen'));

    // ---- Legacy ?page=… addresses redirect to their clean equivalent ----
    if ($segments === [] && isset($_GET['page'])) {
        $page = (string) $_GET['page'];
        if (isset($routes[$page])) {
            $query = $_GET;
            unset($query['page']);

            return ['page' => $page, 'params' => [], 'redirect' => url($page, $query)];
        }
    }

    if ($segments === []) {
        return ['page' => 'home', 'params' => [], 'redirect' => null];
    }

    $first = strtolower($segments[0]);

    // ---- /quran, /quran/search, /quran/{surah} ----
    if ($first === 'quran') {
        if (!isset($segments[1])) {
            // A surah arriving as ?surah=2 belongs in the path.
            $number = (int) ($_GET['surah'] ?? 0);
            if ($number >= 1 && $number <= 114) {
                $query = $_GET;
                unset($query['surah']);

                return ['page' => 'quran', 'params' => [], 'redirect' => url('quran', ['surah' => $number] + $query)];
            }

            return ['page' => 'quran', 'params' => [], 'redirect' => null];
        }

        if (strtolower($segments[1]) === 'search') {
            return ['page' => 'quran-search', 'params' => [], 'redirect' => null];
        }

        $slug = slugify($segments[1]);

        // A bare number redirects to the named form.
        if (ctype_digit($segments[1])) {
            $number = (int) $segments[1];
            if ($number >= 1 && $number <= 114) {
                return ['page' => 'quran', 'params' => [], 'redirect' => url('quran', ['surah' => $number] + $_GET)];
            }
        }

        $number = surahSlugs()['to_number'][$slug] ?? 0;
        if ($number > 0) {
            return ['page' => 'quran', 'params' => ['surah' => (string) $number], 'redirect' => null];
        }

        return ['page' => '404', 'params' => [], 'redirect' => null];
    }

    // ---- /duas/{category} and /hadith/{collection} ----
    foreach (['duas' => 'category', 'hadith' => 'collection'] as $section => $parameter) {
        if ($first !== $section) {
            continue;
        }
        if (!isset($segments[1])) {
            return ['page' => $section, 'params' => [], 'redirect' => null];
        }

        $label = labelSlugs($section)[slugify($segments[1])] ?? null;

        return $label !== null
            ? ['page' => $section, 'params' => [$parameter => $label], 'redirect' => null]
            : ['page' => '404', 'params' => [], 'redirect' => null];
    }

    // ---- Everything else is a single-segment page ----
    if (isset($routes[$first]) && !isset($segments[1])) {
        return ['page' => $first, 'params' => [], 'redirect' => null];
    }

    return ['page' => '404', 'params' => [], 'redirect' => null];
}
