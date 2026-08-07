<?php
defined('NOOR') || http_response_code(404) && exit;
/**
 * Inline notice.
 *
 * @var string $message
 * @var string $type info|warn|error
 */
$type = $type ?? 'info';
?>
<p class="alert alert-<?= e($type) ?>" role="status"><?= e($message ?? '') ?></p>
