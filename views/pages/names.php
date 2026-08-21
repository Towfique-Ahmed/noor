<?php
defined('NOOR') || http_response_code(404) && exit;
/** Al-Asma ul-Husna — the 99 names, with meanings. */

$names = dataset('names');

$heading = '99 Names of Allah';
$sub     = 'Al-Asma ul-Husna — the most beautiful names, with transliteration and meaning.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<div class="card toolbar">
  <label for="name-filter">Find a name</label>
  <input id="name-filter" type="search" placeholder="e.g. Rahman, Merciful, 1" data-filter="#name-list .name-card">
</div>

<ol id="name-list" class="grid name-grid">
  <?php foreach ($names as $item): ?>
    <li>
      <article class="card name-card">
        <span class="name-number"><?= (int) $item['number'] ?></span>
        <p class="arabic" lang="ar" dir="rtl"><?= e($item['arabic']) ?></p>
        <h2><?= e($item['transliteration']) ?></h2>
        <p class="translation"><?= e($item['meaning']) ?></p>
      </article>
    </li>
  <?php endforeach; ?>
</ol>

<section class="card">
  <h2>About the 99 Names</h2>
  <p>
    The 99 Names of Allah, known as Al-Asma ul-Husna (the Most Beautiful Names), are the
    attributes of Allah mentioned in the Qur'an and Sunnah. The Prophet ﷺ said:
    &ldquo;Allah has ninety-nine names — whoever memorises them will enter Paradise.&rdquo;
    (Sahih al-Bukhari 2736). Memorising them means learning their meanings, reflecting on
    them and calling upon Allah by them in supplication.
  </p>
  <p class="muted">
    &ldquo;And to Allah belong the best names, so call upon Him by them.&rdquo; &mdash; Surah al-A'raf 7:180
  </p>
</section>

<?php
$faqHeading = 'Questions about the 99 Names of Allah';
$faqs = [
    ['What are the 99 Names of Allah?',
     'They are the attributes of Allah found in the Qur\'an and Sunnah, such as Ar-Rahman (The Most Merciful), Al-Malik (The King) and As-Salam (The Source of Peace). Together they are called Al-Asma ul-Husna, meaning the Most Beautiful Names.'],
    ['What is the reward for memorising the 99 Names?',
     'The Prophet ﷺ said: "Allah has ninety-nine names — one hundred minus one — whoever memorises them will enter Paradise." (Sahih al-Bukhari 2736). Scholars explain that memorising here means learning their meanings, reflecting on them and acting by them.'],
    ['Can I make dua using the 99 Names?',
     'Yes. The Qur\'an says: "Call upon Him by them" (7:180). You can choose the name that fits what you are asking for — for example, calling upon Ar-Razzaq (The Provider) when asking for provision, or Ash-Shafi (The Healer) when asking for recovery.'],
    ['Are there only 99 Names of Allah?',
     'The hadith mentions ninety-nine names specifically, but scholars note that Allah has other names and attributes mentioned in the Qur\'an and Sunnah beyond this number. The ninety-nine are those singled out with the promise of Paradise for whoever memorises them.'],
];
require dirname(__DIR__) . '/partials/faq.php';
?>
