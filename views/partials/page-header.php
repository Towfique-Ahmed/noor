<?php
defined('NOOR') || http_response_code(404) && exit;
/**
 * Page title block.
 *
 * @var string $heading
 * @var string $sub
 */
?>
<section class="page-head">
  <h1><?= e($heading ?? '') ?></h1>
  <?php if (!empty($sub)): ?>
    <p class="lead"><?= e($sub) ?></p>
  <?php endif; ?>
</section>
