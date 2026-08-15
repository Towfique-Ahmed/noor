<?php
defined('NOOR') || http_response_code(404) && exit;
/** About page: what the site is, where the data comes from, how to run it. */

$heading = 'About Noor';
$sub     = 'A small, free Islamic companion built with plain PHP.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="two-col">
  <article class="card">
    <h2>What is here</h2>
    <ul class="notes">
      <li><strong>Prayer times</strong> for any city, with a live countdown to the next prayer, fifteen calculation methods and both Asr opinions.</li>
      <li><strong>The Qur'an</strong> — all 114 surahs in Uthmani script with translations in ten languages, tafsir from eight classical commentaries, and verse-by-verse recitation from ten reciters.</li>
      <li><strong>Search</strong> across any translation, with bookmarks and a link back to where you stopped reading.</li>
      <li><strong>Qibla direction</strong> from your coordinates, with a compass that follows your phone.</li>
      <li><strong>Zakat calculator</strong> covering cash, gold, silver, business stock and debts against nisab.</li>
      <li><strong>Fitrah calculator</strong> for Sadaqat al-Fitr, in weight or in money.</li>
      <li><strong>Hadith</strong>, <strong>duas</strong>, the <strong>99 names</strong>, the <strong>Hijri calendar</strong> and a <strong>tasbih counter</strong>.</li>
    </ul>
  </article>

  <article class="card">
    <h2>Where the data comes from</h2>
    <ul class="notes">
      <li>Prayer times, Hijri dates and the Qibla bearing: the <strong>AlAdhan API</strong>.</li>
      <li>Qur'an text, translations and tafsir: <strong>AlQuran Cloud</strong>; recitation audio from the islamic.network CDN.</li>
      <li>Hadith, duas and the 99 names ship with the site as JSON files.</li>
      <li>Responses are cached on disk, so pages stay fast and the APIs are not hammered.</li>
    </ul>
    <p class="muted">
      Prayer times depend on the method and coordinates you choose. Where your local mosque
      differs, follow the mosque.
    </p>
  </article>
</section>

<section class="card">
  <h2>A note on accuracy</h2>
  <p>
    The calculators and timings here are tools, not rulings. Zakat on pensions, shares held for
    trade, or debts spread over years can turn on details this form does not ask about. When
    something matters, ask a scholar you trust.
  </p>
</section>
