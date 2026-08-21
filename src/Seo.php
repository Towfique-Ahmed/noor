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
 * The date the site's content last changed.
 *
 * Stamping every URL with today's date tells crawlers the whole site changed
 * daily, which is untrue and gets the signal discounted. The newest file among
 * the templates and bundled data is an honest answer, and it moves on deploy.
 */
function contentLastModified(): string
{
    $newest = 0;
    foreach (['/views', '/data', '/src', '/public/assets'] as $directory) {
        $path = dirname(__DIR__) . $directory;
        if (!is_dir($path)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $newest = max($newest, (int) $file->getMTime());
        }
    }

    return date('c', $newest ?: time());
}

/**
 * Emit sitemap.xml and stop.
 */
function renderSitemap(): never
{
    $lastmod = contentLastModified();

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
        'Disallow: ' . $basePath . 'bookmarks',
        '',
        'Sitemap: ' . baseUrl() . 'sitemap.xml',
        '',
    ];

    echo implode("\n", $lines);
    exit;
}

/**
 * Meta title and description for a page.
 *
 * Every page gets its own pair: duplicate titles and descriptions across a
 * site are one of the commonest things Search Console flags. Titles aim for
 * roughly 60 characters and descriptions for roughly 155, which is about what
 * Google shows before truncating.
 *
 * @return array{title: string, description: string}
 */
function pageMeta(string $slug): array
{
    $brand = (string) config('app.name');

    $static = [
        'home' => [
            $brand . ' — Prayer Times, Qur\'an, Qibla & Islamic Tools',
            'Free Islamic companion with accurate prayer times for any city, the full Qur\'an with translation, tafsir and audio, Qibla finder, Zakat and Fitrah calculators, hadith, duas and more.',
        ],
        'prayer-times' => [
            'Prayer Times Today — Accurate Salah Timings | ' . $brand,
            'Accurate Fajr, Dhuhr, Asr, Maghrib and Isha times for any city, with a live countdown to the next prayer, a monthly timetable and fifteen calculation methods.',
        ],
        'quran' => [
            'Read the Qur\'an Online with Tafsir & Audio | ' . $brand,
            'Read all 114 surahs in Uthmani Arabic with translations in ten languages, tafsir from eight classical commentaries and verse-by-verse recitation. Free, no sign-up.',
        ],
        'quran-search' => [
            'Search the Qur\'an by Word or Phrase | ' . $brand,
            'Search the whole Qur\'an for any word or phrase and see every matching ayah with its reference, highlighted in your chosen English or Urdu translation.',
        ],
        'bookmarks' => [
            'Your Saved Ayahs & Reading Progress | ' . $brand,
            'The ayahs you starred while reading, kept in your browser, with a link back to the surah you last had open so you can pick up where you stopped.',
        ],
        'qibla' => [
            'Qibla Direction Finder — Compass & Bearing | ' . $brand,
            'Find the Qibla from anywhere: the exact bearing to the Kaaba in degrees, the distance in kilometres and a live compass that follows your phone as you turn.',
        ],
        'zakat' => [
            'Zakat Calculator — Nisab, Gold & Silver | ' . $brand,
            'Work out the Zakat you owe at 2.5%. Add cash, gold, silver, business stock and investments, subtract debts, and check the total against gold or silver nisab.',
        ],
        'fitrah' => [
            'Fitrah Calculator — Sadaqat al-Fitr per Person | ' . $brand,
            'Calculate Sadaqat al-Fitr for your household before Eid al-Fitr. One sa\' per person in wheat, barley, dates, raisins or rice, given in weight or in money.',
        ],
        'hadith' => [
            'Hadith Collection — Bukhari, Muslim & the Six Books | ' . $brand,
            'Search authentic hadith from Sahih al-Bukhari, Sahih Muslim and the six major collections by keyword. Each hadith shows its narrator, book and reference in Arabic and English.',
        ],
        'duas' => [
            'Daily Duas — Arabic Text, Transliteration & Meaning | ' . $brand,
            'Authentic daily supplications (duas) from the Qur\'an and Sunnah for morning, evening, prayer, food, travel and forgiveness, with Arabic, transliteration and English meaning.',
        ],
        'names' => [
            '99 Names of Allah — Asma ul Husna with Meanings | ' . $brand,
            'The 99 Names of Allah (Al-Asma ul-Husna) in Arabic with transliteration and English meaning. Search, learn and memorise each of Allah\'s beautiful names.',
        ],
        'calendar' => [
            'Hijri Calendar — Islamic Date Today & Converter | ' . $brand,
            'Today\'s Islamic (Hijri) date, a Gregorian-to-Hijri date converter, the twelve Islamic months explained, and key dates including Ramadan, Ashura, Arafah and both Eids.',
        ],
        'tasbih' => [
            'Digital Tasbih Counter — Free Online Dhikr Counter | ' . $brand,
            'Count your dhikr online with this free tasbih counter. SubhanAllah, Alhamdulillah, Allahu Akbar and more, with targets, completed rounds and a tally saved in your browser.',
        ],
        'about' => [
            'About ' . $brand . ' — Free Islamic Companion App',
            'Learn what Noor offers, where its prayer times, Qur\'an text, tafsir and hadith data come from, how the Zakat and Fitrah calculators work, and our commitment to privacy.',
        ],
        '404' => [
            'Page Not Found | ' . $brand,
            'That page does not exist. Head back to prayer times, the Qur\'an reader, the Qibla finder or the Zakat calculator.',
        ],
    ];

    [$title, $description] = $static[$slug] ?? $static['home'];

    return dynamicPageMeta($slug, $title, $description, $brand);
}

/**
 * Refine the meta for pages whose content depends on a parameter, so a surah,
 * a dua category or a search term each get their own title and description.
 *
 * @return array{title: string, description: string}
 */
function dynamicPageMeta(string $slug, string $title, string $description, string $brand): array
{
    if ($slug === 'quran') {
        $number = (int) input('surah');
        if ($number >= 1 && $number <= 114) {
            $surah = surahName($number);
            $title = 'Surah ' . $surah['name'] . ' (' . $surah['meaning'] . ') with Tafsir & Audio | ' . $brand;

            // A few meanings are long enough to push the brand off the end.
            // Drop the meaning rather than let the title be cut mid-suffix.
            if (mb_strlen($title) > 70) {
                $title = 'Surah ' . $surah['name'] . ' — Read with Tafsir & Audio | ' . $brand;
            }

            $description = 'Read Surah ' . $surah['name'] . ', chapter ' . $number . ' of the Qur\'an, in Uthmani Arabic with '
                . 'English translation, verse-by-verse tafsir and audio recitation you can play ayah by ayah.';
        }
    }

    if ($slug === 'quran-search') {
        $term = input('q');
        if ($term !== '') {
            $title = '"' . $term . '" in the Qur\'an — Search Results | ' . $brand;
            $description = 'Every ayah of the Qur\'an that mentions "' . $term . '", with its surah and verse reference '
                . 'and a link straight into the reader at that verse.';
        }
    }

    if ($slug === 'duas') {
        $category = input('category');
        if ($category !== '') {
            $title = $category . ' Duas — Arabic, Transliteration & Meaning | ' . $brand;
            $description = 'Authentic supplications for ' . strtolower($category) . ', each with the Arabic text, an easy '
                . 'transliteration, the English meaning and the hadith or ayah it comes from.';
        }
    }

    if ($slug === 'hadith') {
        $collection = input('collection');
        $term       = input('q');
        if ($term !== '') {
            $title = '"' . $term . '" — Hadith Search Results | ' . $brand;
            $description = 'Hadith about ' . strtolower($term) . ' from the six books, each with its narrator, '
                . 'book and reference number in Arabic and English.';
        } elseif ($collection !== '') {
            $title = $collection . ' — Search Hadith by Keyword | ' . $brand;
            $description = 'Browse and search hadith from ' . $collection . ', each shown with its narrator, book '
                . 'and reference number in Arabic and English.';
        }
    }

    return [
        'title'       => trimMeta($title, 70),
        'description' => trimMeta($description, 165),
    ];
}

/**
 * The English name and meaning of a surah.
 *
 * Read from the bundled list rather than the API, so page titles are correct
 * even when the network is unavailable.
 *
 * @return array{name: string, meaning: string}
 */
function surahName(int $number): array
{
    static $surahs = null;
    if ($surahs === null) {
        $surahs = [];
        foreach (dataset('surahs') as $surah) {
            $surahs[(int) $surah['number']] = $surah;
        }
    }

    $found = $surahs[$number] ?? null;

    return [
        'name'    => (string) ($found['name'] ?? ('Surah ' . $number)),
        'meaning' => (string) ($found['meaning'] ?? ''),
    ];
}

/**
 * Keep a meta string within its limit, cutting on a word boundary.
 */
function trimMeta(string $text, int $limit): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    $cut   = mb_substr($text, 0, $limit - 1);
    $space = mb_strrpos($cut, ' ');

    return rtrim($space !== false ? mb_substr($cut, 0, $space) : $cut, " ,.;:-") . '…';
}
