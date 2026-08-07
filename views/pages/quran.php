<?php
/** Qur'an index, and the reader when a surah is selected. */

$surahNumber = (int) input('surah', '0');
$translation = input('translation', (string) config('defaults.translation'));
if (!array_key_exists($translation, quranTranslations())) {
    $translation = (string) config('defaults.translation');
}

$index = quranSurahs();
$list  = $index['ok'] ? ($index['data']['data'] ?? []) : [];

if ($surahNumber < 1 || $surahNumber > 114) {
    // ---------- Index ----------
    $heading = 'Read the Qur\'an';
    $sub     = 'All 114 surahs in Uthmani script, side by side with a translation.';
    require dirname(__DIR__) . '/partials/page-header.php';
    ?>

    <?php if (!$index['ok']): ?>
      <?php $message = 'The surah list could not be loaded. ' . ($index['error'] ?? ''); $type = 'error'; ?>
      <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
    <?php else: ?>
      <div class="card toolbar">
        <label for="surah-filter">Find a surah</label>
        <input id="surah-filter" type="search" placeholder="Name or number, e.g. Baqarah or 2" data-filter="#surah-list .surah-card">
      </div>

      <ol id="surah-list" class="grid surah-grid">
        <?php foreach ($list as $surah): ?>
          <li>
            <a class="card surah-card"
               href="<?= e(url('quran', ['surah' => $surah['number'], 'translation' => $translation])) ?>">
              <span class="surah-number"><?= (int) $surah['number'] ?></span>
              <span class="surah-meta">
                <strong><?= e($surah['englishName']) ?></strong>
                <small><?= e($surah['englishNameTranslation']) ?></small>
                <small class="muted">
                  <?= (int) $surah['numberOfAyahs'] ?> ayahs &middot; <?= e(ucfirst((string) $surah['revelationType'])) ?>
                </small>
              </span>
              <span class="surah-arabic" lang="ar"><?= e($surah['name']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>

    <?php
    return;
}

// ---------- Reader ----------
$surah   = quranSurah($surahNumber, $translation);
$editions = $surah['ok'] ? ($surah['data']['data'] ?? []) : [];
$arabic   = $editions[0] ?? null;
$rendered = $editions[1] ?? null;

$heading = $arabic['englishName'] ?? ('Surah ' . $surahNumber);
$sub     = $arabic ? ($arabic['englishNameTranslation'] . ' — ' . count($arabic['ayahs']) . ' ayahs, ' . ucfirst((string) $arabic['revelationType'])) : '';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<div class="card toolbar reader-toolbar">
  <form class="inline-form" method="get" action="<?= e(url('home')) ?>">
    <input type="hidden" name="page" value="quran">
    <label class="sr-only" for="surah-select">Surah</label>
    <select id="surah-select" name="surah">
      <?php foreach ($list as $item): ?>
        <option value="<?= (int) $item['number'] ?>"<?= (int) $item['number'] === $surahNumber ? ' selected' : '' ?>>
          <?= (int) $item['number'] ?>. <?= e($item['englishName']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label class="sr-only" for="translation-select">Translation</label>
    <select id="translation-select" name="translation">
      <?php foreach (quranTranslations() as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= $key === $translation ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>

    <button class="btn btn-sm" type="submit">Open</button>
  </form>

  <div class="reader-nav">
    <?php if ($surahNumber > 1): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('quran', ['surah' => $surahNumber - 1, 'translation' => $translation])) ?>">&larr; Previous</a>
    <?php endif; ?>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('quran')) ?>">All surahs</a>
    <?php if ($surahNumber < 114): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('quran', ['surah' => $surahNumber + 1, 'translation' => $translation])) ?>">Next &rarr;</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!$surah['ok'] || !$arabic): ?>
  <?php $message = 'This surah could not be loaded. ' . ($surah['error'] ?? ''); $type = 'error'; ?>
  <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
<?php else: ?>
  <?php if ($surahNumber !== 9): ?>
    <p class="bismillah" lang="ar">&#1576;&#1616;&#1587;&#1618;&#1605;&#1616; &#1575;&#1604;&#1604;&#1607;&#1616; &#1575;&#1604;&#1585;&#1617;&#1614;&#1581;&#1618;&#1605;&#1614;&#1606;&#1616; &#1575;&#1604;&#1585;&#1617;&#1614;&#1581;&#1616;&#1610;&#1605;&#1616;</p>
  <?php endif; ?>

  <section class="ayah-list">
    <?php foreach ($arabic['ayahs'] as $i => $ayah): ?>
      <article class="card ayah" id="ayah-<?= (int) $ayah['numberInSurah'] ?>">
        <header class="ayah-head">
          <span class="ayah-number"><?= $surahNumber ?>:<?= (int) $ayah['numberInSurah'] ?></span>
          <a class="ayah-anchor" href="#ayah-<?= (int) $ayah['numberInSurah'] ?>" aria-label="Link to this ayah">&#128279;</a>
        </header>
        <p class="arabic" lang="ar" dir="rtl"><?= e($ayah['text']) ?></p>
        <?php if (!empty($rendered['ayahs'][$i]['text'])): ?>
          <p class="translation"><?= e($rendered['ayahs'][$i]['text']) ?></p>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
