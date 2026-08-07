<?php
defined('NOOR') || http_response_code(404) && exit;
/** Al-Asma ul-Husna — the 99 names, with meanings. */

$names = dataset('names');

$heading = '99 Names of Allah';
$sub     = 'Al-Asma ul-Husna — the most beautiful names, with transliteration and meaning.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<div class="card toolbar">
  <label for="name-filter">Find a name</label>
  <input id="name-filter" type="search" placeholder="e.g. Rahman, Merciful, 1" data-filter="#name-list .name-card">
</div>

<ol id="name-list" class="grid name-grid">
  <?php foreach ($names as $item): ?>
    <li>
      <article class="card name-card">
        <span class="name-number"><?= (int) $item['number'] ?></span>
        <p class="arabic" lang="ar" dir="rtl"><?= e($item['arabic']) ?></p>
        <h2><?= e($item['transliteration']) ?></h2>
        <p class="translation"><?= e($item['meaning']) ?></p>
      </article>
    </li>
  <?php endforeach; ?>
</ol>

<p class="muted">
  &ldquo;And to Allah belong the best names, so call upon Him by them.&rdquo; &mdash; Surah al-A'raf 7:180
</p>
