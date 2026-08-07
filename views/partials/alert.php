<?php
/**
 * Inline notice.
 *
 * @var string $message
 * @var string $type info|warn|error
 */
$type = $type ?? 'info';
?>
<p class="alert alert-<?= e($type) ?>" role="status"><?= e($message ?? '') ?></p>
