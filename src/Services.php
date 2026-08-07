<?php
/**
 * Thin wrappers around the AlAdhan and AlQuran Cloud APIs.
 *
 * Each function returns the same shape as fetchJson() so pages can render an
 * error banner without special-casing the service they called.
 */

declare(strict_types=1);

/**
 * Daily prayer timings for a city.
 */
function prayerTimesByCity(string $city, string $country, int $method, int $school, string $date = ''): array
{
    $date = $date !== '' ? $date : date('d-m-Y');
    $url  = config('api.aladhan') . '/timingsByCity/' . rawurlencode($date) . '?' . http_build_query([
        'city'    => $city,
        'country' => $country,
        'method'  => $method,
        'school'  => $school,
    ]);

    return fetchJson($url, (int) config('cache.ttl.prayer_times'));
}

/**
 * A full month of prayer timings for a city.
 */
function prayerCalendarByCity(string $city, string $country, int $method, int $school, int $month, int $year): array
{
    $url = config('api.aladhan') . '/calendarByCity/' . $year . '/' . $month . '?' . http_build_query([
        'city'    => $city,
        'country' => $country,
        'method'  => $method,
        'school'  => $school,
    ]);

    return fetchJson($url, (int) config('cache.ttl.calendar'));
}

/**
 * Gregorian date converted to the Hijri calendar.
 */
function hijriDate(string $date = ''): array
{
    $date = $date !== '' ? $date : date('d-m-Y');

    return fetchJson(config('api.aladhan') . '/gToH/' . rawurlencode($date), (int) config('cache.ttl.calendar'));
}

/**
 * Qibla bearing, in degrees clockwise from true north.
 */
function qiblaDirection(float $latitude, float $longitude): array
{
    $url = config('api.aladhan') . '/qibla/' . rawurlencode((string) $latitude) . '/' . rawurlencode((string) $longitude);

    return fetchJson($url, (int) config('cache.ttl.qibla'));
}

/**
 * Great-circle bearing to the Kaaba, computed locally as a fallback and as a
 * cross-check for the API answer.
 */
function qiblaBearing(float $lat, float $lng): float
{
    $kaabaLat = deg2rad(21.4224779);
    $kaabaLng = deg2rad(39.8251832);
    $lat      = deg2rad($lat);
    $lng      = deg2rad($lng);

    $deltaLng = $kaabaLng - $lng;
    $y        = sin($deltaLng);
    $x        = cos($lat) * tan($kaabaLat) - sin($lat) * cos($deltaLng);

    $bearing = rad2deg(atan2($y, $x));

    return fmod($bearing + 360.0, 360.0);
}

/**
 * Distance in kilometres between a point and the Kaaba.
 */
function distanceToKaaba(float $lat, float $lng): float
{
    $earthRadius = 6371.0;
    $dLat = deg2rad(21.4224779 - $lat);
    $dLng = deg2rad(39.8251832 - $lng);

    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat)) * cos(deg2rad(21.4224779)) * sin($dLng / 2) ** 2;

    return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/**
 * Turn a bearing in degrees into a 16-point compass label.
 */
function compassPoint(float $bearing): string
{
    $points = ['North', 'North-northeast', 'Northeast', 'East-northeast', 'East', 'East-southeast',
               'Southeast', 'South-southeast', 'South', 'South-southwest', 'Southwest', 'West-southwest',
               'West', 'West-northwest', 'Northwest', 'North-northwest'];

    return $points[(int) round(fmod($bearing + 360.0, 360.0) / 22.5) % 16];
}

/**
 * The list of the 114 surahs.
 */
function quranSurahs(): array
{
    return fetchJson(config('api.alquran') . '/surah', (int) config('cache.ttl.quran'));
}

/**
 * One surah in Arabic plus a translation edition.
 */
function quranSurah(int $number, string $translation): array
{
    $editions = 'quran-uthmani,' . $translation;
    $url      = config('api.alquran') . '/surah/' . $number . '/editions/' . rawurlencode($editions);

    return fetchJson($url, (int) config('cache.ttl.quran'));
}

/**
 * Translations offered on the Qur'an page.
 */
function quranTranslations(): array
{
    return [
        'en.sahih'     => 'English — Saheeh International',
        'en.pickthall' => 'English — Pickthall',
        'en.yusufali'  => 'English — Yusuf Ali',
        'bn.bengali'   => 'Bangla — Muhiuddin Khan',
        'ur.jalandhry' => 'Urdu — Jalandhry',
        'id.indonesian'=> 'Indonesian — Bahasa',
        'tr.diyanet'   => 'Turkish — Diyanet',
        'fr.hamidullah'=> 'French — Hamidullah',
        'es.cortes'    => 'Spanish — Cortes',
        'ru.kuliev'    => 'Russian — Kuliev',
    ];
}

/**
 * Search the bundled hadith collection.
 *
 * @return array<int, array<string, string>>
 */
function searchHadith(string $query, string $collection = ''): array
{
    $hadith = dataset('hadith');
    $query  = trim($query);

    return array_values(array_filter($hadith, static function (array $item) use ($query, $collection): bool {
        if ($collection !== '' && ($item['collection'] ?? '') !== $collection) {
            return false;
        }
        if ($query === '') {
            return true;
        }

        $haystack = mb_strtolower(implode(' ', [
            $item['text'] ?? '',
            $item['narrator'] ?? '',
            $item['book'] ?? '',
            $item['collection'] ?? '',
        ]));

        return str_contains($haystack, mb_strtolower($query));
    }));
}

/**
 * Distinct hadith collection names in the bundled dataset.
 *
 * @return array<int, string>
 */
function hadithCollections(): array
{
    $names = array_column(dataset('hadith'), 'collection');
    $names = array_values(array_unique(array_filter($names)));
    sort($names);

    return $names;
}
