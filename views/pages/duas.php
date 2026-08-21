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

<section class="card">
  <h2>Why daily duas matter</h2>
  <p>
    Dua is the essence of worship. The Prophet ﷺ said: &ldquo;Dua is worship&rdquo;
    (Sunan Abu Dawud 1479). Making supplication at the right moments — when waking, before
    eating, when leaving the house, during travel and before sleep — turns everyday actions
    into acts of remembrance and brings barakah into your day.
  </p>
  <p>
    Each dua on this page includes the original Arabic text, an easy-to-read transliteration
    so you can learn the pronunciation, and the English meaning so you understand what you
    are asking for.
  </p>
</section>

<?php
$faqHeading = 'Questions about duas';
$faqs = [
    ['What is a dua?',
     'A dua is a personal supplication or prayer to Allah. Unlike the five daily salah, which follow a fixed form, a dua can be made at any time, in any language, for anything you need. The Qur\'an encourages it: "Call upon Me; I will respond to you" (40:60).'],
    ['When is the best time to make dua?',
     'Several times are singled out: the last third of the night, between the adhan and iqamah, while prostrating in prayer, on Friday afternoon, while fasting, and during rain. But dua is accepted at any time.'],
    ['Do I have to say duas in Arabic?',
     'Duas from the Qur\'an and Sunnah are best said in Arabic to preserve the exact wording. Personal supplications can be made in any language — Allah understands all languages.'],
    ['What are the etiquettes of making dua?',
     'Begin with praise of Allah and salawat on the Prophet ﷺ, face the Qibla if convenient, raise your hands, be sincere, ask with certainty and close with salawat. Avoid making dua for something sinful or for cutting family ties.'],
];
require dirname(__DIR__) . '/partials/faq.php';
?>
