<?php
defined('NOOR') || http_response_code(404) && exit;
/** Full-text search across a Qur'an translation. */

$keyword = input('q');
$edition = input('edition', (string) config('defaults.translation'));
if (!array_key_exists($edition, quranTranslations())) {
    $edition = (string) config('defaults.translation');
}

$results = $keyword !== '' ? quranSearch($keyword, $edition) : null;
$matches = ($results && $results['ok']) ? ($results['data']['data']['matches'] ?? []) : [];
$count   = ($results && $results['ok']) ? (int) ($results['data']['data']['count'] ?? 0) : 0;

$heading = 'Search the Qur\'an';
$sub     = 'Find every ayah that mentions a word or phrase, in your chosen translation.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<form class="card toolbar" method="get" action="<?= e(url('home')) ?>">
  <input type="hidden" name="page" value="quran-search">
  <div class="field">
    <label for="q">Word or phrase</label>
    <input id="q" name="q" type="search" value="<?= e($keyword) ?>" placeholder="mercy, patience, orphan…" autofocus>
  </div>
  <div class="field">
    <label for="edition">Translation</label>
    <select id="edition" name="edition">
      <?php foreach (quranTranslations() as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= $key === $edition ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field field-action">
    <button class="btn" type="submit">Search</button>
    <?php if ($keyword !== ''): ?>
      <a class="btn btn-ghost" href="<?= e(url('quran-search')) ?>">Clear</a>
    <?php endif; ?>
  </div>
</form>

<?php if ($keyword === ''): ?>
  <section class="card">
    <h2>Try one of these</h2>
    <nav class="chips">
      <?php foreach (['mercy', 'patience', 'orphan', 'charity', 'knowledge', 'forgiveness', 'garden', 'guidance'] as $suggestion): ?>
        <a class="chip" href="<?= e(url('quran-search', ['q' => $suggestion, 'edition' => $edition])) ?>"><?= e($suggestion) ?></a>
      <?php endforeach; ?>
    </nav>
  </section>

<?php elseif ($results && !$results['ok']): ?>
  <?php $message = 'The search service could not be reached. ' . ($results['error'] ?? ''); $type = 'error'; ?>
  <?php require dirname(__DIR__) . '/partials/alert.php'; ?>

<?php elseif ($matches === []): ?>
  <?php $message = 'No ayah in this translation matched “' . $keyword . '”. Try a different word or another translation.'; $type = 'info'; ?>
  <?php require dirname(__DIR__) . '/partials/alert.php'; ?>

<?php else: ?>
  <p class="muted result-count"><?= (int) $count ?> match<?= $count === 1 ? '' : 'es' ?> for &ldquo;<?= e($keyword) ?>&rdquo;</p>

  <section class="search-results">
    <?php foreach ($matches as $match): ?>
      <?php
        $surahNo = (int) ($match['surah']['number'] ?? 0);
        $ayahNo  = (int) ($match['numberInSurah'] ?? 0);
      ?>
      <article class="card search-hit">
        <header class="ayah-head">
          <span class="ayah-number"><?= $surahNo ?>:<?= $ayahNo ?></span>
          <span class="muted"><?= e($match['surah']['englishName'] ?? '') ?></span>
        </header>
        <p class="translation"><?= highlightTerm((string) ($match['text'] ?? ''), $keyword) ?></p>
        <a class="card-link" href="<?= e(url('quran', ['surah' => $surahNo, 'translation' => $edition])) ?>#ayah-<?= $ayahNo ?>">
          Open in the reader &rarr;
        </a>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
