<?php
/**
 * Application configuration.
 *
 * Copy any value you want to override into config/local.php, which is ignored
 * by git and merged on top of this array.
 */

$config = [
    'app' => [
        'name'        => 'Noor',
        'tagline'     => 'Prayer times, Qur\'an, Qibla and more',
        'description' => 'Noor is a free Islamic companion: accurate prayer times, '
                       . 'the full Qur\'an with translation, Qibla direction, Zakat '
                       . 'and Fitrah calculators, hadith collections, duas and the '
                       . 'Hijri calendar.',
        'timezone'    => 'UTC',
        'debug'       => false,

        // Canonical origin — what sitemap.xml, robots.txt and the canonical
        // tags advertise. Deriving it from the request is unreliable behind a
        // proxy or CDN, and Search Console rejects a sitemap whose URLs sit on
        // a different host, so the production domain is committed here.
        //
        // Override per environment with the NOOR_APP_URL environment variable,
        // or in config/local.php. Requests arriving on a local hostname ignore
        // this value and use the request instead, so development stays honest.
        'url'         => getenv('NOOR_APP_URL') ?: 'https://noor.towfique.com',

        // Shown in the footer copyright line.
        'owner'       => 'towfique.com',
        'owner_url'   => 'https://towfique.com',
    ],

    'analytics' => [
        // Google Analytics 4 measurement ID. The tag is left out entirely when
        // this is empty, and on local or private hostnames, so development
        // traffic never reaches the property.
        'ga4' => getenv('NOOR_GA4') !== false ? (string) getenv('NOOR_GA4') : 'G-8590TQFP2D',
    ],

    // Remote services. Both are free and need no API key.
    'api' => [
        'aladhan'  => 'https://api.aladhan.com/v1',
        'alquran'  => 'https://api.alquran.cloud/v1',
        'timeout'  => 12,
    ],

    // Responses are cached on disk so repeated page loads stay fast and we stay
    // friendly to the upstream APIs.
    'cache' => [
        'dir' => __DIR__ . '/../storage/cache',
        'ttl' => [
            'prayer_times' => 6 * 3600,
            'calendar'     => 24 * 3600,
            'qibla'        => 30 * 24 * 3600,
            'quran'        => 30 * 24 * 3600,
        ],
    ],

    // Defaults used until the visitor picks their own city.
    'defaults' => [
        'city'        => 'Dhaka',
        'country'     => 'Bangladesh',
        'method'      => 1,   // University of Islamic Sciences, Karachi
        'school'      => 1,   // Hanafi Asr
        'translation' => 'en.sahih',
        'tafsir'      => 'ar.muyassar',
        'reciter'     => 'ar.alafasy',
    ],

    // Nisab thresholds in grams, used by the Zakat calculator.
    'zakat' => [
        'nisab_gold_grams'   => 87.48,
        'nisab_silver_grams' => 612.36,
        'rate'               => 0.025,
    ],

    // Sadaqat al-Fitr measures per person.
    'fitrah' => [
        'sa_kg' => [
            'wheat'  => 1.75,
            'barley' => 3.5,
            'dates'  => 3.5,
            'raisin' => 3.5,
            'rice'   => 3.5,
        ],
    ],
];

if (is_file(__DIR__ . '/local.php')) {
    $local  = require __DIR__ . '/local.php';
    $config = array_replace_recursive($config, is_array($local) ? $local : []);
}

return $config;
