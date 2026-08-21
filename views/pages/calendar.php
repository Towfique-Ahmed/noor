<?php
defined('NOOR') || http_response_code(404) && exit;
/** Hijri date for today plus the Islamic months and notable days. */

$hijri  = hijriDate();
$today  = $hijri['ok'] ? ($hijri['data']['data']['hijri'] ?? []) : [];
$greg   = $hijri['ok'] ? ($hijri['data']['data']['gregorian'] ?? []) : [];

$lookup     = input('date');
$lookupData = [];
if ($lookup !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $lookup)) {
    $converted = hijriDate(date('d-m-Y', (int) strtotime($lookup)));
    if ($converted['ok']) {
        $lookupData = $converted['data']['data'] ?? [];
    }
}

$months = [
    'Muharram'        => 'The first month, one of the four sacred months. The 10th is Ashura.',
    'Safar'           => 'The second month.',
    'Rabi al-Awwal'   => 'The month the Prophet ﷺ was born.',
    'Rabi al-Thani'   => 'The fourth month.',
    'Jumada al-Ula'   => 'The fifth month.',
    'Jumada al-Akhirah' => 'The sixth month.',
    'Rajab'           => 'A sacred month; the Isra and Miraj is remembered on the 27th.',
    'Sha\'ban'        => 'The month before Ramadan; the Prophet ﷺ fasted much of it.',
    'Ramadan'         => 'The month of fasting and the revelation of the Qur\'an.',
    'Shawwal'         => 'Opens with Eid al-Fitr; six voluntary fasts follow.',
    'Dhul Qadah'      => 'A sacred month.',
    'Dhul Hijjah'     => 'The month of Hajj; Eid al-Adha falls on the 10th.',
];

$observances = [
    ['1 Muharram',    'Islamic New Year'],
    ['10 Muharram',   'Day of Ashura — a recommended fast'],
    ['27 Rajab',      'Isra and Miraj'],
    ['15 Sha\'ban',   'Laylat al-Bara\'ah'],
    ['1 Ramadan',     'Fasting begins'],
    ['Last ten nights of Ramadan', 'Laylat al-Qadr is sought among them'],
    ['1 Shawwal',     'Eid al-Fitr'],
    ['9 Dhul Hijjah', 'Day of Arafah — a recommended fast'],
    ['10 Dhul Hijjah','Eid al-Adha'],
];

$heading = 'Hijri Calendar';
$sub     = 'Today in the Islamic calendar, with a converter and the months of the year.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="card today-hijri">
  <?php if (!$hijri['ok'] || $today === []): ?>
    <?php $message = 'The Hijri date service is unavailable right now.'; $type = 'warn'; ?>
    <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
  <?php else: ?>
    <p class="eyebrow">Today</p>
    <h2 class="big-date">
      <?= e($today['day']) ?> <?= e($today['month']['en']) ?> <?= e($today['year']) ?> AH
    </h2>
    <p class="arabic" lang="ar" dir="rtl"><?= e($today['day'] . ' ' . $today['month']['ar'] . ' ' . $today['year']) ?></p>
    <p class="muted">
      <?= e($today['weekday']['en'] ?? '') ?> &middot; <?= e($greg['date'] ?? date('d-m-Y')) ?>
      <?php if (!empty($today['designation']['abbreviated'])): ?> &middot; <?= e($today['designation']['abbreviated']) ?><?php endif; ?>
    </p>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Convert a date</h2>
  <form class="inline-form" method="get" action="<?= e(url('calendar')) ?>">
    <label class="sr-only" for="date">Gregorian date</label>
    <input id="date" name="date" type="date" value="<?= e($lookup ?: date('Y-m-d')) ?>">
    <button class="btn btn-sm" type="submit">Convert</button>
  </form>

  <?php if ($lookup !== '' && $lookupData !== []): ?>
    <p class="result-headline is-clear">
      <?= e($lookupData['gregorian']['date'] ?? $lookup) ?> is
      <strong><?= e($lookupData['hijri']['day'] . ' ' . $lookupData['hijri']['month']['en'] . ' ' . $lookupData['hijri']['year']) ?> AH</strong>
    </p>
  <?php elseif ($lookup !== ''): ?>
    <?php $message = 'That date could not be converted. Please try another.'; $type = 'warn'; ?>
    <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
  <?php endif; ?>
</section>

<div class="two-col">
  <section class="card">
    <h2>The twelve months</h2>
    <ol class="month-list">
      <?php foreach ($months as $name => $note): ?>
        <li>
          <strong><?= e($name) ?></strong>
          <span class="muted"><?= e($note) ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>

  <section class="card">
    <h2>Days to remember</h2>
    <ul class="notes">
      <?php foreach ($observances as [$when, $what]): ?>
        <li><strong><?= e($when) ?></strong> — <?= e($what) ?></li>
      <?php endforeach; ?>
    </ul>
    <p class="muted">
      Hijri months begin with the sighting of the new moon, so local dates can shift by a day.
      Follow the announcement of your local mosque.
    </p>
  </section>
</div>

<?php
$faqHeading = 'Hijri calendar questions';
$faqs = [
    ['What is the Hijri calendar?',
     'The Hijri (Islamic) calendar is a lunar calendar of twelve months used to determine Islamic holidays, fasting days and the Hajj. It began with the migration (Hijra) of the Prophet ﷺ from Makkah to Madinah in 622 CE.'],
    ['How does the Hijri calendar differ from the Gregorian?',
     'The Hijri calendar is based on the lunar cycle, making each month either 29 or 30 days. A Hijri year is about 354 days — roughly 11 days shorter than a Gregorian year — so Islamic dates shift earlier in the Gregorian calendar each year.'],
    ['Why do Hijri dates vary by location?',
     'Each month begins with the sighting of the new crescent moon, which can be visible in one region a day before another. That is why Ramadan and Eid may start on different days in different countries. Follow the announcement of your local mosque or authority.'],
    ['What are the sacred months in Islam?',
     'Four months are sacred (haram): Muharram, Rajab, Dhul Qadah and Dhul Hijjah. Fighting was traditionally forbidden during these months, and good deeds carry extra weight in them.'],
    ['When is Ramadan in the Hijri calendar?',
     'Ramadan is the ninth month of the Hijri calendar. Because the Islamic year is shorter than the Gregorian year, Ramadan moves roughly 11 days earlier each Gregorian year, cycling through all seasons over about 33 years.'],
];
require dirname(__DIR__) . '/partials/faq.php';
?>
