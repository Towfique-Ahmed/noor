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

<form class="card toolbar" method="get" action="<?= e(url('home')) ?>">
  <input type="hidden" name="page" value="hadith">
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

<p class="muted">
  Wording follows widely used English renderings. For study, check each hadith against a
  published edition of its collection.
</p>
