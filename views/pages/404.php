<?php
defined('NOOR') || http_response_code(404) && exit;
/** Not found. */
$heading = 'Page not found';
$sub     = 'That page does not exist here.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="card">
  <p>Try one of these instead:</p>
  <p class="hero-actions">
    <a class="btn" href="<?= e(url('home')) ?>">Home</a>
    <a class="btn btn-ghost" href="<?= e(url('prayer-times')) ?>">Prayer times</a>
    <a class="btn btn-ghost" href="<?= e(url('quran')) ?>">Qur'an</a>
  </p>
</section>
