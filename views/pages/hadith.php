<?php
defined('NOOR') || http_response_code(404) && exit;
/** Searchable hadith collection, served from the bundled dataset. */

$query      = input('q');
$collection = input('collection');
$collections = hadithCollections();
if ($collection !== '' && !in_array($collection, $collections, true)) {
    $collection = '';
}

$results = searchHadith($query, $collection);

$heading = 'Hadith';
$sub     = 'A curated selection from the six books, with narrator and reference for each.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<form class="card toolbar" method="get" action="<?= e(url('hadith')) ?>">
  <div class="field">
    <label for="q">Search</label>
    <input id="q" name="q" type="search" value="<?= e($query) ?>" placeholder="intention, charity, patience…">
  </div>
  <div class="field">
    <label for="collection">Collection</label>
    <select id="collection" name="collection">
      <option value="">All collections</option>
      <?php foreach ($collections as $name): ?>
        <option value="<?= e($name) ?>"<?= $name === $collection ? ' selected' : '' ?>><?= e($name) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field field-action">
    <button class="btn" type="submit">Search</button>
    <?php if ($query !== '' || $collection !== ''): ?>
      <a class="btn btn-ghost" href="<?= e(url('hadith')) ?>">Clear</a>
    <?php endif; ?>
  </div>
</form>

<p class="muted result-count">
  <?= count($results) ?> hadith<?= count($results) === 1 ? '' : 's' ?>
  <?php if ($query !== ''): ?> matching &ldquo;<?= e($query) ?>&rdquo;<?php endif; ?>
</p>

<?php if ($results === []): ?>
  <?php $message = 'No hadith matched that search. Try a single keyword such as “prayer” or “knowledge”.'; $type = 'info'; ?>
  <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
<?php else: ?>
  <section class="hadith-list">
    <?php foreach ($results as $item): ?>
      <article class="card hadith">
        <?php if (!empty($item['arabic'])): ?>
          <p class="arabic" lang="ar" dir="rtl"><?= e($item['arabic']) ?></p>
        <?php endif; ?>
        <blockquote><?= e($item['text']) ?></blockquote>
        <footer class="hadith-meta">
          <?php if (!empty($item['narrator'])): ?>
            <span>Narrated by <?= e($item['narrator']) ?></span>
          <?php endif; ?>
          <span class="tag"><?= e($item['collection']) ?><?php if (!empty($item['reference'])): ?> <?= e($item['reference']) ?><?php endif; ?></span>
          <?php if (!empty($item['book'])): ?>
            <span class="muted"><?= e($item['book']) ?></span>
          <?php endif; ?>
        </footer>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<section class="card">
  <h2>About the hadith collections</h2>
  <p>
    Hadith are the recorded sayings, actions and approvals of the Prophet Muhammad ﷺ. They
    are the second source of Islamic law after the Qur'an and cover every aspect of life —
    from worship and morals to transactions and family matters. The most authoritative
    collections are the &ldquo;six books&rdquo; (al-Kutub al-Sittah): Sahih al-Bukhari,
    Sahih Muslim, Sunan Abu Dawud, Jami' at-Tirmidhi, Sunan an-Nasa'i and Sunan Ibn Majah.
  </p>
  <p class="muted">
    Wording follows widely used English renderings. For study, check each hadith against a
    published edition of its collection.
  </p>
</section>

<?php
$faqHeading = 'Hadith questions people ask';
$faqs = [
    ['What is a hadith?',
     'A hadith is a report of what the Prophet Muhammad ﷺ said, did or silently approved. Each hadith has a chain of narrators (isnad) and a text (matn). Scholars graded them by the reliability of each narrator in the chain.'],
    ['What does "sahih" mean?',
     'Sahih means authentic. A sahih hadith has an unbroken chain of trustworthy narrators and a text free of hidden defects. Sahih al-Bukhari and Sahih Muslim are regarded as the two most authentic collections.'],
    ['What are the six major hadith collections?',
     'They are Sahih al-Bukhari, Sahih Muslim, Sunan Abu Dawud, Jami\' at-Tirmidhi, Sunan an-Nasa\'i and Sunan Ibn Majah. Together they are called al-Kutub al-Sittah (the Six Books) and form the core reference for hadith scholarship.'],
    ['How do I search hadith on this page?',
     'Type a keyword — such as "prayer", "charity" or "patience" — into the search box above. You can also filter by collection. Every result shows the narrator, book and reference number so you can verify it in a published edition.'],
    ['Can I rely on the English translations here?',
     'The English wording follows widely used renderings and is suitable for general reading. For detailed study or deriving rulings, check the Arabic original in a published edition of the collection.'],
];
require dirname(__DIR__) . '/partials/faq.php';
?>
