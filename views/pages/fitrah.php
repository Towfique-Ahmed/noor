<?php
defined('NOOR') || http_response_code(404) && exit;
/** Sadaqat al-Fitr calculator. */

$people     = (int) input('people', '1');
$people     = max(0, min(999, $people));
$staple     = input('staple', 'wheat');
$pricePerKg = inputFloat('price');
$currency   = input('currency', 'USD');
$currency   = preg_replace('/[^A-Za-z$€£₹৳ ]/u', '', $currency) ?: 'USD';

$result = calculateFitrah($people, $staple, $pricePerKg);

$heading = 'Fitrah Calculator';
$sub     = 'Sadaqat al-Fitr is given for every member of the household before the Eid prayer.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<div class="calc-layout">
  <form class="card calc-form" method="post" action="<?= e(url('fitrah')) ?>">
    <fieldset>
      <legend>Household</legend>
      <div class="field">
        <label for="people">People in the household</label>
        <input id="people" name="people" type="number" min="1" max="999" step="1" value="<?= (int) $people ?>" required>
        <small class="muted">Count everyone you support, including children and dependants.</small>
      </div>
    </fieldset>

    <fieldset>
      <legend>Staple food</legend>
      <div class="field">
        <label for="staple">Give as</label>
        <select id="staple" name="staple">
          <?php foreach ($result['measures'] as $key => $kg): ?>
            <option value="<?= e($key) ?>"<?= $key === $result['staple'] ? ' selected' : '' ?>>
              <?= e(stapleLabel($key)) ?> — <?= e($kg) ?> kg per person
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="price">Local price per kilogram</label>
          <input id="price" name="price" type="number" step="any" min="0" value="<?= e($pricePerKg ?: '') ?>" placeholder="e.g. 1.20">
        </div>
        <div class="field">
          <label for="currency">Currency label</label>
          <input id="currency" name="currency" type="text" maxlength="8" value="<?= e($currency) ?>">
        </div>
      </div>
      <p class="muted">Leave the price blank if you plan to give the food itself rather than its value.</p>
    </fieldset>

    <div class="field-action">
      <button class="btn" type="submit">Calculate Fitrah</button>
      <a class="btn btn-ghost" href="<?= e(url('fitrah')) ?>">Reset</a>
    </div>
  </form>

  <aside class="card calc-result">
    <h2>Your result</h2>

    <dl class="stat-list">
      <div><dt>Per person</dt><dd><?= e($result['per_person_kg']) ?> kg of <?= e(strtolower(stapleLabel($result['staple']))) ?></dd></div>
      <div><dt>People</dt><dd><?= (int) $result['people'] ?></dd></div>
      <div><dt>Total weight</dt><dd><strong><?= e(number_format($result['total_kg'], 2)) ?> kg</strong></dd></div>
      <?php if ($result['price_per_kg'] > 0): ?>
        <div><dt>Value per person</dt><dd><?= e(money($result['per_person'], $currency)) ?></dd></div>
      <?php endif; ?>
    </dl>

    <?php if ($result['price_per_kg'] > 0): ?>
      <p class="result-headline is-due">
        Total to give: <strong><?= e(money($result['total'], $currency)) ?></strong>
      </p>
    <?php else: ?>
      <p class="result-headline is-clear">
        Give <strong><?= e(number_format($result['total_kg'], 2)) ?> kg</strong> of <?= e(strtolower(stapleLabel($result['staple']))) ?>.
      </p>
    <?php endif; ?>

    <h3>How this works</h3>
    <ul class="notes">
      <li>One sa' is due for each person. A sa' is about 3.5 kg of most staples, or roughly 1.75 kg for wheat and flour.</li>
      <li>It is paid on behalf of every member of the household, including a newborn.</li>
      <li>Give it before the Eid al-Fitr prayer so it reaches those in need in time for the day.</li>
      <li>Many people give the money value instead of the food; both are widely accepted practice.</li>
      <li>Local mosques often publish a set amount — follow theirs if it differs from this estimate.</li>
    </ul>
  </aside>
</div>

<?php
$faqHeading = 'Fitrah questions people ask';
$faqs = [
    ['How much is Sadaqat al-Fitr per person?',
     'One sa\' of the staple food of your area for every person in the household. A sa\' is roughly 3.5 kg of rice, barley, dates or raisins, and about 1.75 kg of wheat or flour.'],
    ['Who must Fitrah be paid for?',
     'Every member of the household the head of the family supports, including children and a baby born before the Eid prayer. It is paid on their behalf whether or not they fasted.'],
    ['When must it be given?',
     'Before the Eid al-Fitr prayer, so it reaches those in need in time for the day itself. Giving it a day or two early is widely accepted; giving it after the prayer counts as ordinary charity.'],
    ['Can I give money instead of food?',
     'Many scholars permit giving the money value of the food, and most mosques collect it that way. Others hold that the food itself should be given. Both positions are well established.'],
    ['Who receives Fitrah?',
     'The poor and needy, so that they can eat well on the day of Eid. It can be given locally or sent where the need is greater.'],
];
require dirname(__DIR__) . '/partials/faq.php';
?>