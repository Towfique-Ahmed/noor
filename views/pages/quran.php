<?php
defined('NOOR') || http_response_code(404) && exit;
/** Qur'an index, and the reader with translation, tafsir and recitation. */

$surahNumber = (int) input('surah', '0');

$translation = input('translation', (string) config('defaults.translation'));
if (!array_key_exists($translation, quranTranslations())) {
    $translation = (string) config('defaults.translation');
}

$tafsirs = tafsirEditions();
$tafsir  = input('tafsir', (string) config('defaults.tafsir'));
if ($tafsir !== '' && !array_key_exists($tafsir, $tafsirs)) {
    $tafsir = (string) array_key_first($tafsirs);
}

$allReciters = reciters();
$reciter     = input('reciter', (string) config('defaults.reciter'));
if (!array_key_exists($reciter, $allReciters)) {
    $reciter = (string) array_key_first($allReciters);
}

$index = quranSurahs();
$list  = $index['ok'] ? ($index['data']['data'] ?? []) : [];

// If the API is unreachable, build the list from the bundled surah names. The
// index is the only page that links to all 114 surah pages, so without this a
// crawl during an outage would find no route to them at all.
if ($list === []) {
    foreach (dataset('surahs') as $surah) {
        $list[] = [
            'number'                 => $surah['number'],
            'name'                   => '',
            'englishName'            => $surah['name'],
            'englishNameTranslation' => $surah['meaning'],
            'numberOfAyahs'          => 0,
            'revelationType'         => '',
        ];
    }
}

$carry = ['translation' => $translation, 'tafsir' => $tafsir, 'reciter' => $reciter];

if ($surahNumber < 1 || $surahNumber > 114) {
    // ---------------------------- Index ----------------------------
    $heading = 'Read the Qur\'an';
    $sub     = 'All 114 surahs in Uthmani script, with translation, tafsir and recitation.';
    require dirname(__DIR__) . '/partials/page-header.php';
    ?>

    <?php if (!$index['ok']): ?>
      <?php $message = 'Ayah counts are unavailable right now, but every surah still opens.'; $type = 'warn'; ?>
      <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
    <?php endif; ?>

      <div class="card toolbar quran-toolbar">
        <div class="field">
          <label for="surah-filter">Find a surah</label>
          <input id="surah-filter" type="search" placeholder="Name or number — Baqarah, 2, The Cow"
                 data-filter="#surah-list .surah-card">
        </div>
        <div class="field field-action">
          <a class="btn btn-ghost btn-sm" href="<?= e(url('quran-search')) ?>">Search the text &rarr;</a>
        </div>
      </div>

      <div class="tabs" role="tablist">
        <button class="tab is-active" type="button" role="tab" data-tab-target="#by-surah" aria-selected="true">By surah</button>
        <button class="tab" type="button" role="tab" data-tab-target="#by-juz" aria-selected="false">By juz</button>
      </div>

      <section id="by-surah" class="tab-panel is-active">
        <ol id="surah-list" class="grid surah-grid">
          <?php foreach ($list as $surah): ?>
            <li>
              <a class="card surah-card"
                 href="<?= e(url('quran', ['surah' => $surah['number']] + $carry)) ?>">
                <span class="surah-number"><?= (int) $surah['number'] ?></span>
                <span class="surah-meta">
                  <strong><?= e($surah['englishName']) ?></strong>
                  <small><?= e($surah['englishNameTranslation']) ?></small>
                  <?php if ((int) $surah['numberOfAyahs'] > 0): ?>
                    <small class="muted">
                      <?= (int) $surah['numberOfAyahs'] ?> ayahs &middot; <?= e(ucfirst((string) $surah['revelationType'])) ?>
                    </small>
                  <?php endif; ?>
                </span>
                <?php if ($surah['name'] !== ''): ?>
                  <span class="surah-arabic" lang="ar"><?= e($surah['name']) ?></span>
                <?php endif; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>

      <section id="by-juz" class="tab-panel">
        <ol class="grid juz-grid">
          <?php foreach (juzIndex() as $juz): ?>
            <?php $surahName = ''; ?>
            <?php foreach ($list as $item) { if ((int) $item['number'] === $juz['surah']) { $surahName = (string) $item['englishName']; break; } } ?>
            <li>
              <a class="card juz-card"
                 href="<?= e(url('quran', ['surah' => $juz['surah']] + $carry)) ?>#ayah-<?= (int) $juz['ayah'] ?>">
                <span class="juz-number">Juz <?= (int) $juz['juz'] ?></span>
                <strong><?= e($juz['name']) ?></strong>
                <small class="muted">Starts at <?= e($surahName ?: 'Surah ' . $juz['surah']) ?> <?= (int) $juz['surah'] ?>:<?= (int) $juz['ayah'] ?></small>
              </a>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>

      <p class="muted" data-last-read-note data-reader-url="<?= e(url('quran')) ?>" hidden>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('quran')) ?>" data-last-read-link>Continue where you left off</a>
      </p>

    <?php
    return;
}

// ---------------------------- Reader ----------------------------
$wanted  = ['quran-uthmani', $translation];
if ($tafsir !== '') {
    $wanted[] = $tafsir;
}

$surah    = quranEditions($surahNumber, $wanted);
$editions = $surah['ok'] ? ($surah['data']['data'] ?? []) : [];

// The API returns editions in the order they were requested.
$arabic      = $editions[0] ?? null;
$rendered    = $editions[1] ?? null;
$commentary  = $tafsir !== '' ? ($editions[2] ?? null) : null;

$heading = $arabic['englishName'] ?? ('Surah ' . $surahNumber);
$sub     = $arabic
    ? ($arabic['englishNameTranslation'] . ' — ' . count($arabic['ayahs']) . ' ayahs, ' . ucfirst((string) $arabic['revelationType']))
    : '';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<div class="card reader-toolbar">
  <form class="reader-controls" method="get" action="<?= e(url('home')) ?>">
    <input type="hidden" name="page" value="quran">

    <div class="field">
      <label for="surah-select">Surah</label>
      <select id="surah-select" name="surah">
        <?php foreach ($list as $item): ?>
          <option value="<?= (int) $item['number'] ?>"<?= (int) $item['number'] === $surahNumber ? ' selected' : '' ?>>
            <?= (int) $item['number'] ?>. <?= e($item['englishName']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="translation-select">Translation</label>
      <select id="translation-select" name="translation">
        <?php foreach (quranTranslations() as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $key === $translation ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="tafsir-select">Tafsir</label>
      <select id="tafsir-select" name="tafsir">
        <option value="">None</option>
        <?php foreach ($tafsirs as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $key === $tafsir ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="reciter-select">Reciter</label>
      <select id="reciter-select" name="reciter">
        <?php foreach ($allReciters as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $key === $reciter ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field field-action">
      <button class="btn" type="submit">Apply</button>
    </div>
  </form>

  <div class="reader-nav">
    <?php if ($surahNumber > 1): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('quran', ['surah' => $surahNumber - 1] + $carry)) ?>">&larr; Previous</a>
    <?php endif; ?>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('quran')) ?>">All surahs</a>
    <button class="btn btn-ghost btn-sm" type="button" data-toggle-all-tafsir>Show all tafsir</button>
    <?php if ($surahNumber < 114): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('quran', ['surah' => $surahNumber + 1] + $carry)) ?>">Next &rarr;</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!$surah['ok'] || !$arabic): ?>
  <?php $message = 'This surah could not be loaded. ' . ($surah['error'] ?? ''); $type = 'error'; ?>
  <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
<?php else: ?>

  <section class="surah-banner">
    <div class="surah-banner-inner">
      <p class="eyebrow">Surah <?= $surahNumber ?></p>
      <h2 lang="ar" dir="rtl" class="surah-banner-arabic"><?= e($arabic['name']) ?></h2>
      <p class="muted"><?= e($arabic['englishName']) ?> &middot; <?= e($arabic['englishNameTranslation']) ?></p>
    </div>
  </section>

  <?php if ($surahNumber !== 9): ?>
    <p class="bismillah" lang="ar">&#1576;&#1616;&#1587;&#1618;&#1605;&#1616; &#1575;&#1604;&#1604;&#1607;&#1616; &#1575;&#1604;&#1585;&#1617;&#1614;&#1581;&#1618;&#1605;&#1614;&#1606;&#1616; &#1575;&#1604;&#1585;&#1617;&#1614;&#1581;&#1616;&#1610;&#1605;&#1616;</p>
  <?php endif; ?>

  <section class="ayah-list"
           data-reader
           data-surah="<?= $surahNumber ?>"
           data-surah-name="<?= e($arabic['englishName']) ?>"
           data-reciter="<?= e($reciter) ?>"
           data-surah-audio="<?= e(surahAudioUrl($reciter, $surahNumber)) ?>">
    <?php foreach ($arabic['ayahs'] as $i => $ayah): ?>
      <?php $inSurah = (int) $ayah['numberInSurah']; ?>
      <article class="card ayah" id="ayah-<?= $inSurah ?>"
               data-ayah="<?= $inSurah ?>"
               data-audio="<?= e(ayahAudioUrl($reciter, (int) $ayah['number'])) ?>">
        <header class="ayah-head">
          <span class="ayah-number"><?= $surahNumber ?>:<?= $inSurah ?></span>
          <div class="ayah-actions">
            <button class="icon-btn" type="button" data-play-ayah title="Play this ayah" aria-label="Play ayah <?= $inSurah ?>">&#9654;</button>
            <button class="icon-btn" type="button" data-bookmark title="Bookmark this ayah" aria-label="Bookmark ayah <?= $inSurah ?>">&#9734;</button>
            <button class="icon-btn" type="button" data-copy title="Copy this ayah" aria-label="Copy ayah <?= $inSurah ?>">&#128203;</button>
            <a class="icon-btn" href="#ayah-<?= $inSurah ?>" title="Link to this ayah" aria-label="Link to ayah <?= $inSurah ?>">&#128279;</a>
          </div>
        </header>

        <p class="arabic" lang="ar" dir="rtl" data-arabic><?= e($ayah['text']) ?></p>

        <?php if (!empty($rendered['ayahs'][$i]['text'])): ?>
          <p class="translation" data-translation><?= e($rendered['ayahs'][$i]['text']) ?></p>
        <?php endif; ?>

        <?php if (!empty($commentary['ayahs'][$i]['text'])): ?>
          <?php $isArabicTafsir = str_starts_with((string) ($commentary['language'] ?? 'ar'), 'ar'); ?>
          <details class="tafsir">
            <summary>
              <span class="tafsir-label">Tafsir</span>
              <span class="muted"><?= e($commentary['englishName'] ?? $tafsir) ?></span>
            </summary>
            <div class="tafsir-body<?= $isArabicTafsir ? ' arabic-body' : '' ?>"
                 <?= $isArabicTafsir ? 'lang="ar" dir="rtl"' : '' ?>>
              <?= nl2br(e($commentary['ayahs'][$i]['text'])) ?>
            </div>
          </details>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>

  <?php if ($tafsir !== '' && !$commentary): ?>
    <?php $message = 'The selected tafsir has no text for this surah, or could not be loaded. Try another edition.'; $type = 'warn'; ?>
    <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
  <?php endif; ?>

  <!-- Sticky recitation player, shown once playback starts. -->
  <div class="player" data-player hidden>
    <div class="player-inner">
      <button class="player-btn" type="button" data-player-prev title="Previous ayah" aria-label="Previous ayah">&#9198;</button>
      <button class="player-btn is-primary" type="button" data-player-toggle title="Play or pause" aria-label="Play or pause">&#9654;</button>
      <button class="player-btn" type="button" data-player-next title="Next ayah" aria-label="Next ayah">&#9197;</button>

      <div class="player-meta">
        <strong data-player-title><?= e($arabic['englishName']) ?></strong>
        <small class="muted" data-player-sub><?= e($allReciters[$reciter]) ?></small>
        <div class="player-bar"><span data-player-progress style="width:0%"></span></div>
      </div>

      <label class="player-option">
        <input type="checkbox" data-player-repeat> Repeat
      </label>

      <label class="player-option">
        <span class="sr-only">Playback speed</span>
        <select data-player-speed>
          <option value="0.75">0.75&times;</option>
          <option value="1" selected>1&times;</option>
          <option value="1.25">1.25&times;</option>
          <option value="1.5">1.5&times;</option>
        </select>
      </label>

      <button class="player-btn" type="button" data-player-close title="Close the player" aria-label="Close the player">&times;</button>
    </div>
  </div>
<?php endif; ?>
