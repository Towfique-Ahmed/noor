<?php
defined('NOOR') || http_response_code(404) && exit;
/** Digital dhikr counter. The count is kept in the browser. */

$dhikr = [
    ['SubhanAllah', '&#1587;&#1615;&#1576;&#1618;&#1581;&#1614;&#1575;&#1606;&#1614; &#1575;&#1604;&#1604;&#1607;', 'Glory be to Allah', 33],
    ['Alhamdulillah', '&#1575;&#1604;&#1618;&#1581;&#1614;&#1605;&#1618;&#1583;&#1615; &#1604;&#1616;&#1604;&#1617;&#1614;&#1607;', 'All praise is for Allah', 33],
    ['Allahu Akbar', '&#1575;&#1604;&#1604;&#1607;&#1615; &#1571;&#1614;&#1603;&#1618;&#1576;&#1614;&#1585;', 'Allah is the Greatest', 34],
    ['La ilaha illallah', '&#1604;&#1575; &#1573;&#1604;&#1607; &#1573;&#1604;&#1575; &#1575;&#1604;&#1604;&#1607;', 'There is no god but Allah', 100],
    ['Astaghfirullah', '&#1571;&#1587;&#1578;&#1594;&#1601;&#1585; &#1575;&#1604;&#1604;&#1607;', 'I seek Allah\'s forgiveness', 100],
];

$heading = 'Tasbih Counter';
$sub     = 'Keep count of your dhikr. The tally is saved in this browser until you reset it.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="card tasbih" data-tasbih>
  <div class="tasbih-select">
    <label class="sr-only" for="dhikr">Dhikr</label>
    <select id="dhikr" data-dhikr-select>
      <?php foreach ($dhikr as $i => [$name, $arabic, $meaning, $target]): ?>
        <option value="<?= $i ?>" data-target="<?= (int) $target ?>" data-arabic="<?= $arabic ?>" data-meaning="<?= e($meaning) ?>">
          <?= e($name) ?> (<?= (int) $target ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <p class="arabic tasbih-arabic" lang="ar" dir="rtl" data-dhikr-arabic><?= $dhikr[0][1] ?></p>
  <p class="translation" data-dhikr-meaning><?= e($dhikr[0][2]) ?></p>

  <button class="tasbih-button" type="button" data-count-button>
    <span class="tasbih-count" data-count>0</span>
    <span class="tasbih-hint">Tap to count</span>
  </button>

  <div class="tasbih-progress">
    <div class="bar"><span data-progress style="width:0%"></span></div>
    <p class="muted"><span data-count-echo>0</span> of <span data-target-echo>33</span> &middot; <span data-rounds>0</span> rounds completed</p>
  </div>

  <div class="field-action">
    <button class="btn btn-ghost btn-sm" type="button" data-undo>Undo</button>
    <button class="btn btn-ghost btn-sm" type="button" data-reset>Reset</button>
  </div>

  <p class="muted">Keyboard: press space or enter while the counter is focused.</p>
</section>

<section class="card">
  <h2>Tasbih after salah</h2>
  <p>
    The Prophet &#65018; taught a dhikr to say after every obligatory prayer: SubhanAllah
    thirty-three times, Alhamdulillah thirty-three times and Allahu Akbar thirty-four times,
    which completes one hundred.
  </p>
  <ul class="notes">
    <?php foreach ($dhikr as [$name, $arabic, $meaning, $target]): ?>
      <li><strong><?= e($name) ?></strong> — <?= e($meaning) ?> <span class="muted">(<?= (int) $target ?>)</span></li>
    <?php endforeach; ?>
  </ul>
</section>
