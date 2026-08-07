<?php
/**
 * City / method picker reused by the prayer time and calendar pages.
 *
 * @var array  $location Result of currentLocation().
 * @var string $formPage Slug the form posts back to.
 * @var array  $extra    Extra hidden fields to carry through.
 */
$extra = $extra ?? [];
?>
<form class="card location-form" method="get" action="<?= e(url('home')) ?>">
  <input type="hidden" name="page" value="<?= e($formPage) ?>">
  <?php foreach ($extra as $name => $value): ?>
    <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
  <?php endforeach; ?>

  <div class="field">
    <label for="city">City</label>
    <input id="city" name="city" type="text" value="<?= e($location['city']) ?>" required
           autocomplete="address-level2" placeholder="Dhaka">
  </div>

  <div class="field">
    <label for="country">Country</label>
    <input id="country" name="country" type="text" value="<?= e($location['country']) ?>" required
           autocomplete="country-name" placeholder="Bangladesh">
  </div>

  <div class="field">
    <label for="method">Calculation method</label>
    <select id="method" name="method">
      <?php foreach (calculationMethods() as $id => $label): ?>
        <option value="<?= (int) $id ?>"<?= $location['method'] === $id ? ' selected' : '' ?>>
          <?= e($label) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label for="school">Asr calculation</label>
    <select id="school" name="school">
      <option value="0"<?= $location['school'] === 0 ? ' selected' : '' ?>>Standard (Shafi'i, Maliki, Hanbali)</option>
      <option value="1"<?= $location['school'] === 1 ? ' selected' : '' ?>>Hanafi</option>
    </select>
  </div>

  <div class="field field-action">
    <button class="btn" type="submit">Update times</button>
  </div>
</form>
