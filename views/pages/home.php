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

<section class="card">
  <h2>Your free Islamic companion</h2>
  <p>
    Noor brings together the tools Muslims reach for every day — all in one place, with no
    sign-up and no ads. Look up accurate prayer times for any city in the world, then read
    the Qur'an in Uthmani script with verse-by-verse translation, tafsir from classical
    scholars and audio recitation from ten acclaimed reciters. When Ramadan or Eid approaches,
    use the Zakat and Fitrah calculators to work out what you owe, and check today's date in
    the Hijri calendar. Between prayers, count your dhikr with the digital tasbih, browse
    authentic hadith from the six major collections, or find the right dua for any occasion.
  </p>
  <p>
    Everything runs in your browser. Your bookmarks, your last reading position and your tasbih
    count stay on your device — nothing is sent to a server. Prayer times and Qur'an text come
    from the AlAdhan and AlQuran Cloud APIs, cached so pages load instantly even on a slow connection.
  </p>
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

<?php
$faqHeading = 'Questions about Noor';
$faqs = [
    ['What is Noor?',
     'Noor is a free, open-source Islamic companion website that brings prayer times, the full Qur\'an with translation and tafsir, Qibla direction, Zakat and Fitrah calculators, hadith, duas, the 99 Names of Allah, the Hijri calendar and a digital tasbih counter into one place.'],
    ['Is Noor free to use?',
     'Yes, completely free with no sign-up, no ads and no tracking beyond basic analytics. All features work in your browser without an account.'],
    ['Where do the prayer times come from?',
     'Prayer times are calculated by the AlAdhan API using your city\'s coordinates and the calculation method you choose. Fifteen methods are available, including those from ISNA, the Muslim World League, Umm al-Qura and the University of Islamic Sciences, Karachi.'],
    ['Does Noor store my personal data?',
     'No. Your bookmarks, reading position and tasbih count are saved only in your own browser using local storage. Nothing is sent to a server or shared with anyone.'],
    ['Which Qur\'an translations are available?',
     'The Qur\'an reader offers translations in ten languages, including English (Sahih International), Urdu, French, Turkish and Indonesian, alongside the original Uthmani Arabic text with tafsir from eight classical commentaries.'],
];
require dirname(__DIR__) . '/partials/faq.php';
?>
