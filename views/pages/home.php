<?php
defined('NOOR') || http_response_code(404) && exit;
/** Landing page: today's timings at a glance plus links into every tool. */

$location = currentLocation();
$times    = prayerTimesByCity($location['city'], $location['country'], $location['method'], $location['school']);

$timings = $times['ok'] ? ($times['data']['data']['timings'] ?? []) : [];
$dateBits = $times['ok'] ? ($times['data']['data']['date'] ?? []) : [];

$prayers = ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'];

$tools = [
    ['prayer-times', '&#128337;', 'Prayer Times', 'Daily and monthly timings for any city, with a live countdown to the next prayer.'],
    ['quran',        '&#128214;', 'Read the Qur\'an', 'All 114 surahs with translation, tafsir and verse-by-verse recitation.'],
    ['quran-search', '&#128269;', 'Search the Qur\'an', 'Find every ayah that mentions a word or phrase, in your chosen translation.'],
    ['bookmarks',    '&#11088;',  'Bookmarks', 'The ayahs you starred, and a link back to where you stopped reading.'],
    ['qibla',        '&#129517;', 'Qibla Direction', 'Find the bearing to the Kaaba from anywhere, with a live compass.'],
    ['zakat',        '&#128176;', 'Zakat Calculator', 'Add up your assets, subtract debts and see the 2.5% due against nisab.'],
    ['fitrah',       '&#127806;', 'Fitrah Calculator', 'Sadaqat al-Fitr per person by staple food, in weight and money.'],
    ['hadith',       '&#128220;', 'Hadith', 'Search a curated collection from the six books, with narrator and reference.'],
    ['duas',         '&#129330;', 'Duas', 'Everyday supplications in Arabic, transliteration and translation.'],
    ['names',        '&#10024;',  '99 Names of Allah', 'Al-Asma ul-Husna with meaning for each name.'],
    ['calendar',     '&#128197;', 'Hijri Calendar', 'Today\'s Hijri date and a full month of prayer times.'],
    ['tasbih',       '&#128092;', 'Tasbih Counter', 'A digital counter for dhikr that remembers your count.'],
];
?>

<section class="hero">
  <div>
    <p class="eyebrow">Assalamu alaikum</p>
    <h1>Your daily Islamic companion</h1>
    <p class="lead"><?= e(config('app.description')) ?></p>
    <p class="hero-actions">
      <a class="btn" href="<?= e(url('prayer-times')) ?>">See prayer times</a>
      <a class="btn btn-ghost" href="<?= e(url('quran')) ?>">Read the Qur'an</a>
    </p>
  </div>

  <aside class="card today-card">
    <h2>Today in <?= e($location['city']) ?></h2>
    <?php if (!empty($dateBits['hijri'])): ?>
      <p class="muted">
        <?= e($dateBits['hijri']['day'] . ' ' . $dateBits['hijri']['month']['en'] . ' ' . $dateBits['hijri']['year']) ?> AH
        &middot; <?= e($dateBits['readable'] ?? date('d M Y')) ?>
      </p>
    <?php endif; ?>

    <?php if (!$times['ok']): ?>
      <?php $message = 'Prayer times are unavailable right now. ' . ($times['error'] ?? ''); $type = 'warn'; ?>
      <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
    <?php else: ?>
      <ul class="times-list" data-next-prayer>
        <?php foreach ($prayers as $prayer): ?>
          <?php if (empty($timings[$prayer])) { continue; } ?>
          <li data-prayer="<?= e($prayer) ?>" data-time="<?= e(substr((string) $timings[$prayer], 0, 5)) ?>">
            <span><?= e($prayer) ?></span>
            <time><?= e(substr((string) $timings[$prayer], 0, 5)) ?></time>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="muted next-prayer-note" data-next-label>&nbsp;</p>
    <?php endif; ?>

    <a class="card-link" href="<?= e(url('prayer-times')) ?>">Change city &rarr;</a>
  </aside>
</section>

<section class="tools">
  <h2>Everything in one place</h2>
  <div class="grid">
    <?php foreach ($tools as [$slug, $icon, $name, $blurb]): ?>
      <a class="card tool-card" href="<?= e(url($slug)) ?>">
        <span class="tool-icon" aria-hidden="true"><?= $icon ?></span>
        <h3><?= e($name) ?></h3>
        <p><?= e($blurb) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="verse card">
  <h2>Verse of the day</h2>
  <?php $verse = dataset('verses')[date('z') % max(1, count(dataset('verses')))] ?? null; ?>
  <?php if ($verse): ?>
    <p class="arabic"><?= e($verse['arabic']) ?></p>
    <p class="translation">&ldquo;<?= e($verse['translation']) ?>&rdquo;</p>
    <p class="muted">&mdash; <?= e($verse['reference']) ?></p>
  <?php endif; ?>
</section>
