<?php
/**
 * Shared HTML shell.
 *
 * @var string $content     Rendered page body.
 * @var string $pageTitle   Title for the browser tab.
 * @var string $currentPage Active slug, used to highlight the nav.
 */
$navigation = [
    'home'         => 'Home',
    'prayer-times' => 'Prayer Times',
    'quran'        => 'Qur\'an',
    'qibla'        => 'Qibla',
    'zakat'        => 'Zakat',
    'fitrah'       => 'Fitrah',
    'hadith'       => 'Hadith',
    'duas'         => 'Duas',
    'names'        => '99 Names',
    'calendar'     => 'Calendar',
    'tasbih'       => 'Tasbih',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> &middot; <?= e(config('app.name')) ?></title>
<meta name="description" content="<?= e(config('app.description')) ?>">
<meta name="theme-color" content="#0f5132">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><text y='26' font-size='26'>&#127772;</text></svg>">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
</head>
<body data-page="<?= e($currentPage) ?>">
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= e(url('home')) ?>">
      <span class="brand-mark" aria-hidden="true">&#9790;</span>
      <span>
        <strong><?= e(config('app.name')) ?></strong>
        <small><?= e(config('app.tagline')) ?></small>
      </span>
    </a>

    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
      <span class="sr-only">Toggle navigation</span>
      <span class="bars" aria-hidden="true"></span>
    </button>

    <nav id="site-nav" class="site-nav" aria-label="Main">
      <ul>
        <?php foreach ($navigation as $slug => $label): ?>
          <li>
            <a href="<?= e(url($slug)) ?>"<?= $currentPage === $slug ? ' aria-current="page"' : '' ?>>
              <?= e($label) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <button class="theme-toggle" type="button" title="Switch theme">
      <span class="sr-only">Switch colour theme</span>
      <span aria-hidden="true">&#9728;</span>
    </button>
  </div>
</header>

<main id="main" class="wrap">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="wrap footer-inner">
    <div>
      <strong><?= e(config('app.name')) ?></strong>
      <p><?= e(config('app.description')) ?></p>
    </div>
    <div>
      <h3>Pages</h3>
      <ul>
        <?php foreach (array_slice($navigation, 1, 6, true) as $slug => $label): ?>
          <li><a href="<?= e(url($slug)) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h3>More</h3>
      <ul>
        <?php foreach (array_slice($navigation, 7, 6, true) as $slug => $label): ?>
          <li><a href="<?= e(url($slug)) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= e(url('about')) ?>">About</a></li>
      </ul>
    </div>
  </div>
  <p class="copyright">
    Prayer times, Hijri dates and Qibla data from the AlAdhan API &middot;
    Qur'an text from AlQuran Cloud &middot;
    &copy; <?= date('Y') ?> <?= e(config('app.name')) ?>
  </p>
</footer>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
