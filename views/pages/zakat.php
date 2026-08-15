<?php
defined('NOOR') || http_response_code(404) && exit;
/** Zakat calculator: assets minus debts, checked against nisab. */

$currency    = input('currency', 'USD');
$currency    = preg_replace('/[^A-Za-z$€£₹৳ ]/u', '', $currency) ?: 'USD';
$goldPrice   = inputFloat('gold_price');
$silverPrice = inputFloat('silver_price');
$basis       = input('basis', 'silver') === 'gold' ? 'gold' : 'silver';
$submitted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

$goldGrams   = inputFloat('gold_grams');
$silverGrams = inputFloat('silver_grams');

$assets = [
    'Cash in hand and in the bank' => inputFloat('cash'),
    'Gold holdings'                => metalValue($goldGrams, $goldPrice),
    'Silver holdings'              => metalValue($silverGrams, $silverPrice),
    'Business stock and goods'     => inputFloat('business'),
    'Shares and investments'       => inputFloat('investments'),
    'Money owed to you'            => inputFloat('receivables'),
    'Other zakatable assets'       => inputFloat('other'),
];

$liabilities = inputFloat('liabilities');
$result      = calculateZakat($assets, $liabilities, $goldPrice, $silverPrice, $basis);

$heading = 'Zakat Calculator';
$sub     = 'Zakat is 2.5% of the wealth you have held for one lunar year, once it passes nisab.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<div class="calc-layout">
  <form class="card calc-form" method="post" action="<?= e(url('zakat')) ?>">
    <fieldset>
      <legend>Currency and metal prices</legend>
      <div class="field-row">
        <div class="field">
          <label for="currency">Currency label</label>
          <input id="currency" name="currency" type="text" maxlength="8" value="<?= e($currency) ?>">
        </div>
        <div class="field">
          <label for="gold_price">Gold price per gram</label>
          <input id="gold_price" name="gold_price" type="number" step="any" min="0" value="<?= e($goldPrice ?: '') ?>" placeholder="e.g. 85">
        </div>
        <div class="field">
          <label for="silver_price">Silver price per gram</label>
          <input id="silver_price" name="silver_price" type="number" step="any" min="0" value="<?= e($silverPrice ?: '') ?>" placeholder="e.g. 1.05">
        </div>
      </div>
      <p class="muted">Today's local rate for pure metal is enough — nisab is 87.48 g of gold or 612.36 g of silver.</p>
    </fieldset>

    <fieldset>
      <legend>Assets</legend>
      <div class="field">
        <label for="cash">Cash, savings and current accounts</label>
        <input id="cash" name="cash" type="number" step="any" min="0" value="<?= e(inputFloat('cash') ?: '') ?>">
      </div>
      <div class="field-row">
        <div class="field">
          <label for="gold_grams">Gold owned (grams)</label>
          <input id="gold_grams" name="gold_grams" type="number" step="any" min="0" value="<?= e($goldGrams ?: '') ?>">
        </div>
        <div class="field">
          <label for="silver_grams">Silver owned (grams)</label>
          <input id="silver_grams" name="silver_grams" type="number" step="any" min="0" value="<?= e($silverGrams ?: '') ?>">
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="business">Business stock and goods</label>
          <input id="business" name="business" type="number" step="any" min="0" value="<?= e(inputFloat('business') ?: '') ?>">
        </div>
        <div class="field">
          <label for="investments">Shares and investments</label>
          <input id="investments" name="investments" type="number" step="any" min="0" value="<?= e(inputFloat('investments') ?: '') ?>">
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="receivables">Money owed to you</label>
          <input id="receivables" name="receivables" type="number" step="any" min="0" value="<?= e(inputFloat('receivables') ?: '') ?>">
        </div>
        <div class="field">
          <label for="other">Other zakatable assets</label>
          <input id="other" name="other" type="number" step="any" min="0" value="<?= e(inputFloat('other') ?: '') ?>">
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Liabilities and nisab</legend>
      <div class="field">
        <label for="liabilities">Debts and bills due</label>
        <input id="liabilities" name="liabilities" type="number" step="any" min="0" value="<?= e($liabilities ?: '') ?>">
      </div>
      <div class="field">
        <label for="basis">Nisab based on</label>
        <select id="basis" name="basis">
          <option value="silver"<?= $basis === 'silver' ? ' selected' : '' ?>>Silver — 612.36 g (lower threshold)</option>
          <option value="gold"<?= $basis === 'gold' ? ' selected' : '' ?>>Gold — 87.48 g</option>
        </select>
      </div>
    </fieldset>

    <div class="field-action">
      <button class="btn" type="submit">Calculate Zakat</button>
      <a class="btn btn-ghost" href="<?= e(url('zakat')) ?>">Reset</a>
    </div>
  </form>

  <aside class="card calc-result">
    <h2>Your result</h2>

    <?php if (!$result['has_nisab_input']): ?>
      <?php $message = 'Enter a gold or silver price so nisab can be worked out.'; $type = 'info'; ?>
      <?php require dirname(__DIR__) . '/partials/alert.php'; ?>
    <?php endif; ?>

    <dl class="stat-list">
      <div><dt>Total assets</dt><dd><?= e(money($result['total_assets'], $currency)) ?></dd></div>
      <div><dt>Liabilities</dt><dd>&minus; <?= e(money($result['liabilities'], $currency)) ?></dd></div>
      <div><dt>Net zakatable wealth</dt><dd><strong><?= e(money($result['net_worth'], $currency)) ?></strong></dd></div>
      <div><dt>Nisab (<?= e($result['nisab_basis']) ?>)</dt><dd><?= e(money($result['nisab'], $currency)) ?></dd></div>
    </dl>

    <?php if ($result['has_nisab_input']): ?>
      <p class="result-headline <?= $result['eligible'] ? 'is-due' : 'is-clear' ?>">
        <?php if ($result['eligible']): ?>
          Zakat due: <strong><?= e(money($result['payable'], $currency)) ?></strong>
        <?php else: ?>
          Your net wealth is below nisab, so no Zakat is due this year.
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <?php if ($submitted && $result['eligible']): ?>
      <p class="muted">That is 2.5% of <?= e(money($result['net_worth'], $currency)) ?>.</p>
    <?php endif; ?>

    <h3>How this works</h3>
    <ul class="notes">
      <li>Zakat is due once your net wealth stays at or above nisab for a full lunar year.</li>
      <li>The rate is 2.5% (one fortieth) of that wealth.</li>
      <li>Silver nisab is the lower of the two, so it brings more people into paying — many scholars prefer it for that reason.</li>
      <li>Your home, personal clothing, furniture and the car you drive are not zakatable.</li>
      <li>This tool is a guide. For debts spread over years, pensions, or business assets, ask a scholar you trust.</li>
    </ul>
  </aside>
</div>

<?php
$faqHeading = 'Zakat questions people ask';
$faqs = [
    ['What is the nisab for Zakat?',
     'Nisab is the minimum wealth you must hold before Zakat is due: 87.48 grams of gold or 612.36 grams of silver, valued at today\'s price. Silver gives the lower threshold, so it brings more people into paying, and many scholars prefer it for that reason.'],
    ['How much Zakat do I pay?',
     'Zakat is 2.5% — one fortieth — of the net wealth you have held for one full lunar year. On 100,000 of net zakatable wealth that is 2,500.'],
    ['Which assets are zakatable?',
     'Cash in hand and in the bank, gold and silver, business stock and goods for sale, shares held for trade, and money owed to you that you expect to recover.'],
    ['What is not zakatable?',
     'The home you live in, your personal clothing and furniture, the car you drive, and tools you use to earn a living are not zakatable, however much they are worth.'],
    ['Do I subtract my debts?',
     'Yes. Debts and bills that are due are subtracted from your assets before the 2.5% is applied. Long-term debt spread over many years is treated differently by different scholars, so ask someone you trust.'],
    ['When is Zakat due?',
     'Once your net wealth has stayed at or above nisab for a full lunar year, called the hawl. Many people fix a date in Ramadan and calculate on that date every year.'],
];
require dirname(__DIR__) . '/partials/faq.php';
?>