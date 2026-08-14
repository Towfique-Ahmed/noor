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
 * One surah with any combination of editions (Arabic, translation, tafsir).
 *
 * @param array<int, string> $editions Edition identifiers, in display order.
 */
function quranEditions(int $number, array $editions): array
{
    $editions = array_values(array_filter(array_unique($editions)));
    if ($editions === []) {
        return ['ok' => false, 'data' => [], 'error' => 'No edition was requested.', 'cached' => false];
    }

    $url = config('api.alquran') . '/surah/' . $number . '/editions/' . rawurlencode(implode(',', $editions));

    return fetchJson($url, (int) config('cache.ttl.quran'));
}

/**
 * Tafsir editions offered on the reader.
 *
 * The list is pulled from the API so the identifiers are always the ones it
 * actually serves. The bundled fallback keeps the picker usable when the API
 * cannot be reached.
 *
 * @return array<string, string> identifier => label
 */
function tafsirEditions(): array
{
    $fallback = [
        'ar.muyassar'  => 'Arabic — al-Muyassar',
        'ar.jalalayn'  => 'Arabic — al-Jalalayn',
        'ar.qurtubi'   => 'Arabic — al-Qurtubi',
        'ar.tabari'    => 'Arabic — al-Tabari',
        'ar.baghawi'   => 'Arabic — al-Baghawi',
        'ar.katheer'   => 'Arabic — Ibn Kathir',
        'ar.waseet'    => 'Arabic — al-Waseet',
        'ar.miqbas'    => 'Arabic — Tanwir al-Miqbas',
    ];

    $response = fetchJson(config('api.alquran') . '/edition?format=text&type=tafsir', (int) config('cache.ttl.quran'));
    if (!$response['ok'] || empty($response['data']['data'])) {
        return $fallback;
    }

    $editions = [];
    foreach ($response['data']['data'] as $edition) {
        if (empty($edition['identifier'])) {
            continue;
        }
        $editions[(string) $edition['identifier']] = editionLabel($edition);
    }

    return $editions === [] ? $fallback : $editions;
}

/**
 * Verse-by-verse reciters offered by the audio player.
 *
 * @return array<string, string> identifier => label
 */
function reciters(): array
{
    $fallback = [
        'ar.alafasy'             => 'Mishary Rashid Alafasy',
        'ar.abdulbasitmurattal'  => 'Abdul Basit — Murattal',
        'ar.husary'              => 'Mahmoud Khalil al-Husary',
        'ar.minshawi'            => 'Mohamed Siddiq al-Minshawi',
        'ar.mahermuaiqly'        => 'Maher al-Muaiqly',
        'ar.abdurrahmaansudais'  => 'Abdur-Rahman as-Sudais',
        'ar.saoodshuraym'        => 'Saood ash-Shuraym',
        'ar.hudhaify'            => 'Ali al-Hudhaify',
        'ar.ahmedajamy'          => 'Ahmed al-Ajamy',
        'ar.shaatree'            => 'Abu Bakr ash-Shaatree',
    ];

    $response = fetchJson(config('api.alquran') . '/edition?format=audio&type=versebyverse', (int) config('cache.ttl.quran'));
    if (!$response['ok'] || empty($response['data']['data'])) {
        return $fallback;
    }

    $found = [];
    foreach ($response['data']['data'] as $edition) {
        if (empty($edition['identifier'])) {
            continue;
        }
        $found[(string) $edition['identifier']] = (string) ($edition['englishName'] ?? $edition['name'] ?? $edition['identifier']);
    }

    return $found === [] ? $fallback : $found;
}

/**
 * Readable label for an edition record from the API.
 */
function editionLabel(array $edition): string
{
    $name     = (string) ($edition['englishName'] ?? $edition['name'] ?? $edition['identifier'] ?? '');
    $language = strtoupper((string) ($edition['language'] ?? ''));

    return $language !== '' ? $language . ' — ' . $name : $name;
}

/**
 * Audio file for a single ayah, by its global number (1–6236).
 */
function ayahAudioUrl(string $reciter, int $globalAyah, int $bitrate = 128): string
{
    return 'https://cdn.islamic.network/quran/audio/' . $bitrate . '/'
        . rawurlencode($reciter) . '/' . $globalAyah . '.mp3';
}

/**
 * Audio file for a whole surah.
 */
function surahAudioUrl(string $reciter, int $surah, int $bitrate = 128): string
{
    return 'https://cdn.islamic.network/quran/audio-surah/' . $bitrate . '/'
        . rawurlencode($reciter) . '/' . $surah . '.mp3';
}

/**
 * Full-text search across a translation edition.
 */
function quranSearch(string $keyword, string $edition): array
{
    $keyword = trim($keyword);
    if ($keyword === '') {
        return ['ok' => true, 'data' => ['data' => ['count' => 0, 'matches' => []]], 'error' => null, 'cached' => false];
    }

    $url = config('api.alquran') . '/search/' . rawurlencode($keyword) . '/all/' . rawurlencode($edition);

    return fetchJson($url, (int) config('cache.ttl.quran'));
}

/**
 * The thirty ajza, with the surah and ayah each one opens at.
 *
 * @return array<int, array{juz: int, surah: int, ayah: int, name: string}>
 */
function juzIndex(): array
{
    $starts = [
        [1, 1, 'Alif Lam Mim'],           [2, 142, 'Sayaqul'],          [2, 253, 'Tilkar-Rusul'],
        [3, 92, 'Lan Tanalu'],            [4, 24, 'Wal-Muhsanat'],      [4, 148, 'La Yuhibbullah'],
        [5, 82, 'Wa Idha Sami\'u'],       [6, 111, 'Wa Lau Annana'],    [7, 88, 'Qalal-Mala'],
        [8, 41, 'Wa\'lamu'],              [9, 93, 'Ya\'tadhiruna'],     [11, 6, 'Wa Ma Min Dabbah'],
        [12, 53, 'Wa Ma Ubarri\'u'],      [15, 1, 'Rubama'],            [17, 1, 'Subhanalladhi'],
        [18, 75, 'Qala Alam'],            [21, 1, 'Iqtaraba'],          [23, 1, 'Qad Aflaha'],
        [25, 21, 'Wa Qalalladhina'],      [27, 56, 'Amman Khalaqa'],    [29, 46, 'Utlu Ma Uhiya'],
        [33, 31, 'Wa Manyaqnut'],         [36, 28, 'Wa Mali'],          [39, 32, 'Faman Azlam'],
        [41, 47, 'Ilaihi Yuraddu'],       [46, 1, 'Ha Mim'],            [51, 31, 'Qala Fama Khatbukum'],
        [58, 1, 'Qad Sami Allah'],        [67, 1, 'Tabarakalladhi'],    [78, 1, 'Amma Yatasa\'alun'],
    ];

    $juz = [];
    $number = 1;
    foreach ($starts as $start) {
        $juz[] = ['juz' => $number, 'surah' => $start[0], 'ayah' => $start[1], 'name' => $start[2]];
        $number++;
    }

    return $juz;
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
