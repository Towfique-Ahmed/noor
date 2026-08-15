<?php
defined('NOOR') || http_response_code(404) && exit;
/** Qibla bearing from coordinates, with a live compass in the browser. */

$hasCoords = input('lat') !== '' && input('lng') !== '';
$lat = inputFloat('lat', 23.8103);
$lng = inputFloat('lng', 90.4125);
$lat = max(-90.0, min(90.0, $lat));
$lng = max(-180.0, min(180.0, $lng));

$bearing  = qiblaBearing($lat, $lng);
$distance = distanceToKaaba($lat, $lng);

$remote = qiblaDirection($lat, $lng);
if ($remote['ok'] && isset($remote['data']['data']['direction'])) {
    $bearing = (float) $remote['data']['data']['direction'];
}

$heading = 'Qibla Direction';
$sub     = 'The bearing you face for salah, measured clockwise from true north.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<form class="card location-form" method="get" action="<?= e(url('qibla')) ?>">

  <div class="field">
    <label for="lat">Latitude</label>
    <input id="lat" name="lat" type="number" step="any" min="-90" max="90" value="<?= e(round($lat, 6)) ?>" required>
  </div>

  <div class="field">
    <label for="lng">Longitude</label>
    <input id="lng" name="lng" type="number" step="any" min="-180" max="180" value="<?= e(round($lng, 6)) ?>" required>
  </div>

  <div class="field field-action">
    <button class="btn" type="submit">Find Qibla</button>
    <button class="btn btn-ghost" type="button" data-locate>Use my location</button>
  </div>
</form>

<section class="qibla-panel">
  <div class="card compass-card">
    <div class="compass" data-compass data-qibla="<?= e(round($bearing, 2)) ?>">
      <div class="compass-face">
        <span class="cardinal n">N</span>
        <span class="cardinal e">E</span>
        <span class="cardinal s">S</span>
        <span class="cardinal w">W</span>
        <div class="compass-needle" data-needle>
          <span class="needle-arrow" aria-hidden="true"></span>
          <span class="needle-label">Qibla</span>
        </div>
      </div>
    </div>
    <p class="muted compass-hint" data-compass-hint>
      Turn until the needle points straight up. On a phone, tap &ldquo;Enable compass&rdquo;
      to let the needle follow the device.
    </p>
    <p><button class="btn btn-ghost btn-sm" type="button" data-compass-enable>Enable compass</button></p>
  </div>

  <div class="card">
    <h2>Your reading</h2>
    <dl class="stat-list">
      <div><dt>Qibla bearing</dt><dd><strong><?= e(number_format($bearing, 2)) ?>&deg;</strong> from true north</dd></div>
      <div><dt>Compass direction</dt><dd><?= e(compassPoint($bearing)) ?></dd></div>
      <div><dt>Distance to the Kaaba</dt><dd><?= e(number_format($distance, 0)) ?> km</dd></div>
      <div><dt>Coordinates</dt><dd><?= e(round($lat, 5)) ?>, <?= e(round($lng, 5)) ?></dd></div>
    </dl>
    <p class="muted">
      <?php if (!$hasCoords): ?>
        These are the default coordinates for Dhaka. Enter your own or use the location button above.
      <?php else: ?>
        Phone compasses read magnetic north and drift near metal; treat this as a close guide.
      <?php endif; ?>
    </p>
  </div>
</section>

<?php
$faqHeading = 'Qibla questions people ask';
$faqs = [
    ['How is the Qibla direction calculated?',
     'It is the great-circle bearing from where you stand to the Kaaba in Makkah — the shortest path across the surface of the earth. That is why the direction on a flat map can look surprising from far away.'],
    ['Why does my phone compass disagree slightly?',
     'A phone compass reads magnetic north, which differs from true north by a few degrees in most places, and it drifts near metal, speakers and electronics. The bearing in degrees is the reliable figure.'],
    ['How do I face the Qibla without a compass?',
     'Take the bearing shown here and line it up with a known direction. If you know where the sun rises, that is roughly east, and you can count round from there.'],
    ['What if I cannot work out the direction?',
     'Make your best effort and pray. A prayer offered after a sincere attempt to find the Qibla is valid, even if the direction later turns out to have been slightly off.'],
];
require dirname(__DIR__) . '/partials/faq.php';
?>