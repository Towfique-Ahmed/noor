<?php
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
