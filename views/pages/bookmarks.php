<?php
defined('NOOR') || http_response_code(404) && exit;
/** Saved ayahs. The list lives in the browser, so the page is rendered by JS. */

$heading = 'Your bookmarks';
$sub     = 'Ayahs you starred while reading. They are saved in this browser only.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="card" data-bookmarks
         data-reader-url="<?= e(url('quran')) ?>"
         data-translation="<?= e(config('defaults.translation')) ?>">
  <div class="card-head">
    <h2>Saved ayahs</h2>
    <button class="btn btn-ghost btn-sm" type="button" data-clear-bookmarks hidden>Clear all</button>
  </div>

  <ul class="bookmark-list" data-bookmark-list></ul>

  <p class="muted" data-bookmark-empty>
    Nothing saved yet. Open the <a href="<?= e(url('quran')) ?>">Qur'an reader</a> and tap the
    star beside any ayah to keep it here.
  </p>

  <noscript>
    <p class="muted">Bookmarks need JavaScript, because they are stored in your browser.</p>
  </noscript>
</section>

<section class="card">
  <h2>Where you left off</h2>
  <p class="muted" data-last-read-empty>The reader remembers the last surah you opened.</p>
  <p data-last-read-note hidden><a class="btn" href="#" data-last-read-link>Continue reading</a></p>
</section>
