<?php
declare(strict_types=1);

@ini_set("display_errors", "0");

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");

$raw = @file_get_contents(__DIR__ . '/data/content.json');
$c = $raw ? json_decode($raw, true) : null;
if (!is_array($c)) {
    http_response_code(500);
    echo 'Не удалось прочитать content.json';
    exit;
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function safe_url($u): string {
    $u = trim((string)$u);
    return preg_match('~^(https?://|tel:|mailto:)~i', $u) ? $u : '#';
}
function tel_href($p): string { return 'tel:+' . preg_replace('/\D+/', '', (string)$p); }
function img($p): string {
    $p = (string)$p;
    return preg_match('~^(assets|uploads)/[A-Za-z0-9._/-]+$~', $p) && strpos($p, '..') === false ? $p : 'assets/img/hero.svg';
}

$s = $c['site']; $h = $c['hero']; $a = $c['about'];
$ver = @filemtime(__DIR__ . '/data/content.json') ?: time();
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($s['title']) ?></title>
<meta name="description" content="<?= e($s['description']) ?>">
<meta name="theme-color" content="#FFF3E3">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($s['title']) ?>">
<meta property="og:description" content="<?= e($s['description']) ?>">
<meta property="og:locale" content="ru_RU">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<script src="assets/js/boot.js"></script>
<link rel="stylesheet" href="assets/css/style.css?v=<?= (int)$ver ?>">
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'PerformingGroup',
    'name' => $s['name'],
    'description' => $s['description'],
    'areaServed' => $s['city'],
    'telephone' => $s['phone'],
    'email' => $s['email'],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</head>
<body>
<a class="skip" href="#main">К содержимому</a>

<header class="top" id="top">
  <a class="logo" href="#hero" aria-label="<?= e($s['name']) ?>"><?= e($s['name']) ?></a>
  <nav class="nav" id="nav" aria-label="Основная навигация">
    <a href="#about">обо мне</a>
    <a href="#formats">форматы</a>
    <a href="#timeline">сценарий</a>
    <a href="#reviews">отзывы</a>
    <a href="#contact">контакты</a>
  </nav>
  <a class="btn btn--small" href="#contact"><?= e($h['cta']) ?></a>
  <button class="burger" id="burger" aria-label="Меню" aria-expanded="false" aria-controls="nav"><span></span><span></span></button>
</header>

<main id="main">

<section class="hero" id="hero">
  <div class="hero__text">
    <p class="kicker"><?= e($h['kicker']) ?></p>
    <h1 class="hero__title">
      <span class="line"><span><?= e($h['title_1']) ?></span></span>
      <span class="line"><span class="accent"><?= e($h['title_2']) ?></span></span>
      <span class="line"><span><?= e($h['title_3']) ?></span></span>
    </h1>
    <p class="hero__sub"><?= e($h['subtitle']) ?></p>
    <div class="hero__cta">
      <a class="btn magnetic" href="#contact"><?= e($h['cta']) ?></a>
      <a class="btn btn--ghost" href="#timeline"><?= e($h['cta_secondary']) ?></a>
    </div>
  </div>
  <div class="hero__visual">
    <div class="arch">
      <img src="<?= e(img($h['photo'])) ?>" alt="<?= e($h['photo_alt']) ?>" width="640" height="800" fetchpriority="high">
    </div>
    <div class="badge" aria-hidden="true">
      <svg viewBox="0 0 200 200" class="badge__ring"><defs><path id="circ" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0"/></defs><text><textPath href="#circ"><?= e(str_repeat($h['badge'], 2)) ?></textPath></text></svg>
      <span class="badge__mic">✦</span>
    </div>
    <span class="sticker sticker--a" aria-hidden="true">ха-ха!</span>
    <span class="sticker sticker--b" aria-hidden="true">браво</span>
  </div>
</section>

<div class="marquee-wrap">
  <div class="marquee" aria-hidden="true">
    <div class="marquee__track">
      <?php for ($i = 0; $i < 4; $i++): foreach ($c['marquee'] as $m): ?>
        <span><?= e($m) ?></span><i>✦</i>
      <?php endforeach; endfor; ?>
    </div>
  </div>
</div>

<section class="about" id="about">
  <div class="about__photo reveal">
    <div class="blob"><img src="<?= e(img($a['photo'])) ?>" alt="<?= e($a['photo_alt']) ?>" width="640" height="720" loading="lazy"></div>
  </div>
  <div class="about__body">
    <h2 class="h2 reveal"><?= e($a['title']) ?></h2>
    <p class="lead reveal"><?= e($a['text_1']) ?></p>
    <p class="reveal"><?= e($a['text_2']) ?></p>
    <ul class="stats">
      <?php foreach ($a['stats'] as $st): ?>
      <li class="reveal">
        <b><span class="count" data-to="<?= e($st['num']) ?>">0</span><small><?= e($st['suffix']) ?></small></b>
        <span><?= e($st['label']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="formats" id="formats">
  <div class="wrap">
    <h2 class="h2 reveal"><?= e($c['formats']['title']) ?></h2>
    <p class="intro reveal"><?= e($c['formats']['intro']) ?></p>
    <div class="cards">
      <?php foreach ($c['formats']['items'] as $i => $f): ?>
      <article class="card reveal" data-i="<?= (int)$i ?>">
        <button class="card__head" aria-expanded="false" aria-controls="fmt<?= (int)$i ?>">
          <span class="card__tag"><?= e($f['tag']) ?></span>
          <span class="card__title"><?= e($f['title']) ?></span>
          <span class="card__plus" aria-hidden="true"></span>
        </button>
        <div class="card__body" id="fmt<?= (int)$i ?>">
          <div>
            <p><?= e($f['text']) ?></p>
            <ul>
              <?php foreach ($f['points'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
            </ul>
            <a class="link" href="#contact">Обсудить формат →</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="timeline" id="timeline">
  <div class="timeline__sticky">
    <h2 class="h2"><?= e($c['timeline']['title']) ?></h2>
    <p class="intro"><?= e($c['timeline']['intro']) ?></p>
    <div class="clock" aria-hidden="true">
      <span class="clock__time" id="clockTime"><?= e($c['timeline']['items'][0]['time']) ?></span>
      <span class="clock__bar"><i id="clockBar"></i></span>
    </div>
  </div>
  <ol class="cues">
    <?php foreach ($c['timeline']['items'] as $i => $t): ?>
    <li class="cue" data-time="<?= e($t['time']) ?>">
      <span class="cue__time"><?= e($t['time']) ?></span>
      <div class="cue__card">
        <h3><?= e($t['title']) ?></h3>
        <p><?= e($t['text']) ?></p>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="reviews" id="reviews">
  <div class="wrap">
    <h2 class="h2 reveal"><?= e($c['reviews']['title']) ?></h2>
  </div>
  <div class="reviews__rail" tabindex="0" aria-label="Отзывы">
    <?php foreach ($c['reviews']['items'] as $i => $r): ?>
    <figure class="review review--<?= (int)($i % 4) ?>">
      <blockquote><?= e($r['text']) ?></blockquote>
      <figcaption><b><?= e($r['name']) ?></b><span><?= e($r['event']) ?></span></figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
</section>

<section class="process" id="process">
  <div class="wrap">
    <h2 class="h2 reveal"><?= e($c['process']['title']) ?></h2>
    <ol class="steps">
      <?php foreach ($c['process']['items'] as $i => $p): ?>
      <li class="step reveal">
        <span class="step__n"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <h3><?= e($p['title']) ?></h3>
        <p><?= e($p['text']) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="contact" id="contact">
  <div class="wrap contact__grid">
    <div>
      <h2 class="h2 h2--light reveal"><?= e($c['contact']['title']) ?></h2>
      <p class="lead lead--light reveal"><?= e($c['contact']['text']) ?></p>
      <ul class="msgs reveal">
        <li><a class="btn btn--light" href="<?= e(safe_url($s['telegram'])) ?>" target="_blank" rel="noopener noreferrer">Telegram</a></li>
        <li><a class="btn btn--light" href="<?= e(safe_url($s['whatsapp'])) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a></li>
        <li><a class="btn btn--light" href="<?= e(safe_url($s['vk'])) ?>" target="_blank" rel="noopener noreferrer">ВКонтакте</a></li>
      </ul>
      <p class="direct reveal"><a href="<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a><br><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></p>
    </div>
    <form class="form reveal" id="leadForm" method="post" action="lead.php" novalidate>
      <label>Имя
        <input type="text" name="name" required maxlength="80" autocomplete="name">
      </label>
      <label>Телефон или Telegram
        <input type="text" name="contact" required maxlength="80" autocomplete="tel">
      </label>
      <label>Дата и формат праздника
        <textarea name="message" rows="3" maxlength="800"></textarea>
      </label>
      <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <button class="btn btn--accent" type="submit"><?= e($c['contact']['form_button']) ?></button>
      <p class="form__status" id="formStatus" role="status" data-ok="<?= e($c['contact']['form_success']) ?>"></p>
      <p class="form__note"><?= e($c['contact']['privacy']) ?></p>
    </form>
  </div>
</section>

</main>

<footer class="foot">
  <span>© <?= date('Y') ?> <?= e($s['name']) ?></span>
  <a href="#top">наверх ↑</a>
</footer>

<script src="assets/js/main.js?v=<?= (int)$ver ?>" defer></script>
</body>
</html>
