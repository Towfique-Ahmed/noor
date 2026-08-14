# Noor

An Islamic resources website built with plain PHP — prayer times, Qur'an reading
with tafsir and recitation, Qibla direction, Zakat and Fitrah calculators,
hadith, duas, the 99 names, the Hijri calendar and a tasbih counter.

No framework, no database, no build step, no Composer packages.

## Features

| Page | What it does |
| --- | --- |
| **Home** | Today's timings at a glance, the next prayer highlighted, and a verse of the day. |
| **Prayer Times** | Daily timings for any city plus a full monthly timetable, with a **live countdown ring** to the next prayer and an optional browser reminder. Fifteen calculation methods and both Asr opinions. Your city is remembered in a cookie. |
| **Qur'an** | All 114 surahs in Uthmani script beside a translation in ten languages, with **tafsir** from eight classical commentaries and **verse-by-verse recitation** from ten reciters. Browse by surah or by juz; bookmark, copy and link any ayah. |
| **Qur'an search** | Full-text search across any translation, with the matched term highlighted and a jump straight into the reader. |
| **Bookmarks** | Starred ayahs and a "continue reading" link back to where you stopped. Saved in the browser. |
| **Qibla** | Bearing to the Kaaba from any coordinates, with distance, a 16-point compass label, geolocation and a needle that follows the device compass. |
| **Zakat** | Cash, gold, silver, business stock, investments and receivables, less liabilities, checked against gold or silver nisab at 2.5%. |
| **Fitrah** | Sadaqat al-Fitr per person by staple food, given in weight or in money. |
| **Hadith** | Search a curated selection from the six books by keyword and collection, with narrator and reference. |
| **Duas** | Everyday supplications grouped by occasion, in Arabic, transliteration and English. |
| **99 Names** | Al-Asma ul-Husna with meanings, filterable. |
| **Calendar** | Today's Hijri date, a Gregorian → Hijri converter, the twelve months and the days to remember. |
| **Tasbih** | A dhikr counter that saves your count and rounds in the browser. |

The site works with JavaScript disabled. The recitation player, countdown ring,
bookmarks, tafsir toggles, compass, theme toggle, list filters and tasbih counter
are progressive enhancements layered on pages that already render server-side.

## Requirements

- PHP 8.1 or later
- The `curl` extension recommended (the site falls back to `file_get_contents`)
- A writable `storage/cache` directory

## Running locally

```bash
git clone https://github.com/Towfique-Ahmed/noor.git
cd noor
php -S localhost:8000 -t public
```

Then open <http://localhost:8000>.

## Deploying

**Point the site's web root at `public/`.** That is the intended setup and it
keeps `config/`, `src/`, `views/` and `storage/` outside the served tree
entirely. On xCloud, Ploi, RunCloud, cPanel or Plesk, set the web root to
`/public` in the site settings.

If the web root stays at the repository root, the site still works: the root
`index.php` bootstrap forwards to the front controller, and the root
`.htaccess` handles Apache. **A panel that serves the repository root with no
index file is what produces `403 Forbidden` from nginx** — the bootstrap fixes
that, but setting the web root to `public/` is still better.

nginx does not read `.htaccess`, so on nginx add the rules from
[`deploy/nginx.conf`](deploy/nginx.conf) to the site's custom configuration.
That file covers both layouts.

The `storage/cache` directory should be writable by the web server. If it is
not, the site falls back to the system temp directory rather than losing
caching.

### Composer

The site has no dependencies, but a `composer.json` ships anyway because most
panels run `composer install` as a deploy step and abort when the file is
missing. Running it is harmless — it installs nothing and writes an autoloader
the site does not need. The front controller loads its own helpers with
`require_once`, so an autoloader in play changes nothing.

## Configuration

`config/config.php` holds the defaults — app name, default city, calculation
method, cache lifetimes, nisab weights and the sa' measures used for Fitrah.

Do not edit it directly for local changes. Copy the keys you want into
`config/local.php`, which is git-ignored and merged on top:

```php
<?php
return [
    'app'      => ['name' => 'Masjid an-Noor', 'timezone' => 'Asia/Dhaka'],
    'defaults' => ['city' => 'Chattogram', 'country' => 'Bangladesh', 'method' => 1],
];
```

## Layout

```
index.php   bootstrap for hosts that serve the repository root
config/     configuration, plus your git-ignored local.php
data/       bundled JSON: hadith, duas, the 99 names, verses
deploy/     sample nginx configuration
public/     document root — front controller, CSS, JS, .htaccess
src/        Support.php, Http.php, Services.php, Calculators.php
storage/    on-disk response cache
views/      layout, partials and one file per page
```

Requests enter `public/index.php`, which resolves a slug to a file in
`views/pages/`, renders it into a buffer and wraps it in `views/layout.php`.

## Data sources

- Prayer times, Hijri dates and the Qibla bearing: the [AlAdhan API](https://aladhan.com/prayer-times-api)
- Qur'an text, translations, tafsir and the edition lists: [AlQuran Cloud](https://alquran.cloud/api)
- Recitation audio: the [islamic.network CDN](https://cdn.islamic.network) that accompanies it
- Hadith, duas, the 99 names and the verses ship as JSON in `data/`

The tafsir and reciter menus are built from the API's own edition list, so the
identifiers are always ones it actually serves; a bundled fallback keeps the menus
usable when the API is unreachable. Both APIs are free and need no key.

Responses are cached on disk — six hours
for prayer times, a day for calendar data, a month for Qur'an text and Qibla
bearings. If an API is unreachable the site serves a stale cached copy when it
has one, and otherwise shows a notice rather than failing.

## A note on accuracy

The calculators and timings are tools, not rulings. Prayer times depend on the
method and coordinates you pick; where your local mosque differs, follow the
mosque. Zakat on pensions, shares held for trade or debts spread over years can
turn on details these forms do not ask about — ask a scholar you trust.

## License

MIT — see [LICENSE](LICENSE).
