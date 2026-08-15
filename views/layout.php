<?php
defined('NOOR') || http_response_code(404) && exit;
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
    'quran-search' => 'Search',
    'qibla'        => 'Qibla',
    'zakat'        => 'Zakat',
    'fitrah'       => 'Fitrah',
    'hadith'       => 'Hadith',
    'duas'         => 'Duas',
    'names'        => '99 Names',
    'calendar'     => 'Calendar',
    'tasbih'       => 'Tasbih',
    'bookmarks'    => 'Bookmarks',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($metaTitle ?? $pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription ?? config('app.description')) ?>">
<meta name="theme-color" content="#0f5132">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="robots" content="<?= e($robots ?? 'index, follow, max-image-preview:large') ?>">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(config('app.name')) ?>">
<meta property="og:title" content="<?= e($metaTitle ?? $pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDescription ?? config('app.description')) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:locale" content="en">
<meta property="og:image" content="<?= e(absoluteUrl()) ?>assets/img/og-cover.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= e(config('app.name')) ?> — <?= e(config('app.tagline')) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= e(absoluteUrl()) ?>assets/img/og-cover.png">
<meta name="twitter:title" content="<?= e($metaTitle ?? $pageTitle) ?>">
<meta name="twitter:description" content="<?= e($pageDescription ?? config('app.description')) ?>">

<script type="application/ld+json"><?= json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'WebSite',
    'name'        => config('app.name'),
    'description' => config('app.description'),
    'url'         => baseUrl(),
    'potentialAction' => [
        '@type'       => 'SearchAction',
        'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => absoluteUrl('quran-search') . '&q={search_term_string}'],
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<?php if ($currentPage !== 'home' && $currentPage !== '404'): ?>
<script type="application/ld+json"><?= json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => absoluteUrl('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $pageTitle, 'item' => $canonical],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><text y='26' font-size='26'>&#127772;</text></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Outfit:wght@300;400;600;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
</head>
<body data-page="<?= e($currentPage) ?>">
<div class="page-glow" aria-hidden="true"></div>
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
        <li><a href="<?= e(baseUrl()) ?>sitemap.xml">Sitemap</a></li>
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
