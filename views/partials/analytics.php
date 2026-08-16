<?php
defined('NOOR') || http_response_code(404) && exit;
/**
 * Google tag (gtag.js).
 *
 * Placed immediately after <head>, as Google asks, and emitted once per page.
 * It is skipped when no measurement ID is configured and on local or private
 * hostnames, so development traffic never lands in the property.
 */
$measurementId = trim((string) config('analytics.ga4', ''));

// Whitelist the ID rather than escaping it: it goes into a script URL and a
// JavaScript string literal, where HTML escaping would corrupt it.
if ($measurementId === '' || isLocalRequest() || !preg_match('/^[A-Za-z0-9\-]{4,32}$/', $measurementId)) {
    return;
}
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= $measurementId ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '<?= $measurementId ?>');
</script>
