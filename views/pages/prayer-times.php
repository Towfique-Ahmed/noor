<?php
defined('NOOR') || http_response_code(404) && exit;
/** Daily prayer timings with a city picker and a monthly table. */

$location = currentLocation();
rememberLocation($location['city'], $location['country'], $location['method'], $location['school']);

$date  = input('date', date('d-m-Y'));
$times = prayerTimesByCity($location['city'], $location['country'], $location['method'], $location['school'], $date);

$month = (int) input('month', date('n'));
$year  = (int) input('year', date('Y'));
$month = max(1, min(12, $month));
$year  = max(1900, min(2200, $year));

$calendar = prayerCalendarByCity($location['city'], $location['country'], $location['method'], $location['school'], $month, $year);

$timings  = $times['ok'] ? ($times['data']['data']['timings'] ?? []) : [];
$meta     = $times['ok'] ? ($times['data']['data']['meta'] ?? []) : [];
$dateBits = $times['ok'] ? ($times['data']['data']['date'] ?? []) : [];

$prayers = ['Fajr' => 'Fajr', 'Sunrise' => 'Sunrise', 'Dhuhr' => 'Dhuhr', 'Asr' => 'Asr', 'Maghrib' => 'Maghrib', 'Isha' => 'Isha'];

$heading = 'Prayer Times';
$sub     = 'Timings for ' . $location['city'] . ', ' . $location['country'];
require dirname(__DIR__) . '/partials/page-header.php';

$formPage = 'prayer-times';
require dirname(__DIR__) . '/partials/location-form.php';
?>

<?php if (!$times['ok']): ?>
  <?php $message = 'Could not load prayer times. ' . ($times['error'] ?? 'Please check the city name and try again.'); $type = 'error'; ?>
  <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
<?php else: ?>
  <section class="card">
    <header class="card-head">
      <h2>Today &mdash; <?= e($dateBits['readable'] ?? $date) ?></h2>
      <?php if (!empty($dateBits['hijri'])): ?>
        <p class="muted">
          <?= e($dateBits['hijri']['day'] . ' ' . $dateBits['hijri']['month']['en'] . ' ' . $dateBits['hijri']['year']) ?> AH
          <?php if (!empty($meta['timezone'])): ?>
            &middot; <?= e($meta['timezone']) ?>
          <?php endif; ?>
        </p>
      <?php endif; ?>
    </header>

    <div class="countdown" data-countdown>
      <svg class="countdown-ring" viewBox="0 0 120 120" aria-hidden="true">
        <circle class="ring-track" cx="60" cy="60" r="52"></circle>
        <circle class="ring-fill" cx="60" cy="60" r="52" data-ring></circle>
      </svg>
      <div class="countdown-text">
        <span class="countdown-label" data-countdown-name>Next prayer</span>
        <strong class="countdown-value" data-countdown-value>—</strong>
        <span class="muted" data-countdown-at></span>
      </div>
    </div>

    <p class="field-action countdown-actions">
      <button class="btn btn-ghost btn-sm" type="button" data-notify-toggle>Remind me at the next prayer</button>
    </p>

    <ul class="prayer-grid" data-next-prayer>
      <?php foreach ($prayers as $key => $label): ?>
        <?php if (empty($timings[$key])) { continue; } ?>
        <li data-prayer="<?= e($label) ?>" data-time="<?= e(substr((string) $timings[$key], 0, 5)) ?>">
          <span class="prayer-name"><?= e($label) ?></span>
          <time class="prayer-time"><?= e(substr((string) $timings[$key], 0, 5)) ?></time>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="muted next-prayer-note" data-next-label>&nbsp;</p>

    <?php if (!empty($timings['Imsak']) || !empty($timings['Midnight'])): ?>
      <p class="muted">
        <?php if (!empty($timings['Imsak'])): ?>Imsak <?= e(substr((string) $timings['Imsak'], 0, 5)) ?><?php endif; ?>
        <?php if (!empty($timings['Midnight'])): ?> &middot; Islamic midnight <?= e(substr((string) $timings['Midnight'], 0, 5)) ?><?php endif; ?>
      </p>
    <?php endif; ?>
  </section>
<?php endif; ?>

<section class="card">
  <header class="card-head">
    <h2>Monthly timetable</h2>
    <form class="inline-form" method="get" action="<?= e(url('home')) ?>">
      <input type="hidden" name="page" value="prayer-times">
      <input type="hidden" name="city" value="<?= e($location['city']) ?>">
      <input type="hidden" name="country" value="<?= e($location['country']) ?>">
      <input type="hidden" name="method" value="<?= (int) $location['method'] ?>">
      <input type="hidden" name="school" value="<?= (int) $location['school'] ?>">
      <label class="sr-only" for="month">Month</label>
      <select id="month" name="month">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?= $m ?>"<?= $m === $month ? ' selected' : '' ?>><?= e(date('F', mktime(0, 0, 0, $m, 1))) ?></option>
        <?php endfor; ?>
      </select>
      <label class="sr-only" for="year">Year</label>
      <select id="year" name="year">
        <?php for ($y = (int) date('Y') - 2; $y <= (int) date('Y') + 2; $y++): ?>
          <option value="<?= $y ?>"<?= $y === $year ? ' selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <button class="btn btn-sm" type="submit">Show</button>
    </form>
  </header>

  <?php if (!$calendar['ok'] || empty($calendar['data']['data'])): ?>
    <?php $message = 'The monthly timetable is unavailable right now.'; $type = 'warn'; ?>
    <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
  <?php else: ?>
    <div class="table-scroll">
      <table class="table">
        <caption class="sr-only">Prayer times for <?= e(date('F Y', mktime(0, 0, 0, $month, 1, $year))) ?></caption>
        <thead>
          <tr>
            <th scope="col">Date</th>
            <th scope="col">Hijri</th>
            <?php foreach ($prayers as $label): ?>
              <th scope="col"><?= e($label) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($calendar['data']['data'] as $day): ?>
            <?php
              $dayTimings = $day['timings'] ?? [];
              $isToday    = ($day['date']['gregorian']['date'] ?? '') === date('d-m-Y');
            ?>
            <tr<?= $isToday ? ' class="is-today"' : '' ?>>
              <th scope="row"><?= e($day['date']['gregorian']['day'] ?? '') ?> <?= e(substr((string) ($day['date']['gregorian']['month']['en'] ?? ''), 0, 3)) ?></th>
              <td class="muted"><?= e(($day['date']['hijri']['day'] ?? '') . ' ' . substr((string) ($day['date']['hijri']['month']['en'] ?? ''), 0, 3)) ?></td>
              <?php foreach (array_keys($prayers) as $key): ?>
                <td><?= e(substr((string) ($dayTimings[$key] ?? '—'), 0, 5)) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php
$faqHeading = 'Prayer time questions people ask';
$faqs = [
    ['Why do prayer times differ between apps and mosques?',
     'Because they use different calculation methods. Methods disagree on the sun\'s angle below the horizon that marks Fajr and Isha, which can shift those two times by several minutes. Pick the method your local mosque follows.'],
    ['Which calculation method should I use?',
     'Follow your local mosque. As a rough guide: Karachi in South Asia, ISNA in North America, Muslim World League across much of Europe, Umm al-Qura in Saudi Arabia and Egyptian General Authority in Egypt.'],
    ['What is the difference between the two Asr times?',
     'In the Hanafi school Asr begins when an object\'s shadow is twice its length; in the Shafi\'i, Maliki and Hanbali schools it begins at one length. The Hanafi time is therefore later.'],
    ['How accurate are these times?',
     'They are calculated from your city\'s coordinates and the method you choose, and are accurate to within a minute or two. Where your local mosque announces a different time, follow the mosque.'],
    ['What are Imsak and Islamic midnight?',
     'Imsak is the moment a fasting person stops eating, shortly before Fajr. Islamic midnight is the midpoint between sunset and dawn, which marks the end of the time for Isha.'],
];
require dirname(__DIR__) . '/partials/faq.php';
?>