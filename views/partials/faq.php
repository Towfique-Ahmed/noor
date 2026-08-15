<?php
defined('NOOR') || http_response_code(404) && exit;
/**
 * Question-and-answer block, with matching FAQPage structured data.
 *
 * @var array<int, array{0: string, 1: string}> $faqs
 * @var string                                  $faqHeading
 */
$faqs = $faqs ?? [];
if ($faqs === []) {
    return;
}
?>
<section class="card faq">
  <h2><?= e($faqHeading ?? 'Common questions') ?></h2>
  <?php foreach ($faqs as [$question, $answer]): ?>
    <details class="faq-item">
      <summary><?= e($question) ?></summary>
      <p><?= e($answer) ?></p>
    </details>
  <?php endforeach; ?>
</section>

<script type="application/ld+json"><?= json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(static fn (array $faq): array => [
        '@type'          => 'Question',
        'name'           => $faq[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq[1]],
    ], $faqs),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
