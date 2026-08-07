<?php
defined('NOOR') || http_response_code(404) && exit;
/** Everyday supplications, grouped by occasion. */

$duas   = dataset('duas');
$filter = input('category');
$categories = array_values(array_unique(array_column($duas, 'category')));
sort($categories);

if ($filter !== '' && !in_array($filter, $categories, true)) {
    $filter = '';
}

$visible = $filter === ''
    ? $duas
    : array_values(array_filter($duas, static fn (array $d): bool => $d['category'] === $filter));

$heading = 'Duas';
$sub     = 'Short supplications for the moments of the day, in Arabic, transliteration and English.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<nav class="chips" aria-label="Filter duas by occasion">
  <a class="chip<?= $filter === '' ? ' is-active' : '' ?>" href="<?= e(url('duas')) ?>">All</a>
  <?php foreach ($categories as $category): ?>
    <a class="chip<?= $filter === $category ? ' is-active' : '' ?>" href="<?= e(url('duas', ['category' => $category])) ?>">
      <?= e($category) ?>
    </a>
  <?php endforeach; ?>
</nav>

<section class="grid dua-grid">
  <?php foreach ($visible as $dua): ?>
    <article class="card dua">
      <h2><?= e($dua['title']) ?></h2>
      <p class="arabic" lang="ar" dir="rtl"><?= e($dua['arabic']) ?></p>
      <p class="translit"><?= e($dua['transliteration']) ?></p>
      <p class="translation"><?= e($dua['translation']) ?></p>
      <p class="muted"><span class="tag"><?= e($dua['category']) ?></span> <?= e($dua['source'] ?? '') ?></p>
    </article>
  <?php endforeach; ?>
</section>
