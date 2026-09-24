<?php
declare(strict_types=1);
/** @var string $id */
$p = one_place($id);
if (!$p) { http_response_code(404); layout('مش موجود',
  '<main class="wrap page"><h1>المحل ده مش موجود</h1><a class="btn call wide" href="' . url('places') . '">رجوع للمحلات</a></main>'); exit; }

$ok = $bad = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $what = post('what', 10);
    if ($what === 'rate') {
        $stars = (int) post('stars', 2);
        if ($stars < 1 || $stars > 5) $bad = 'اختار من ١ لـ ٥ نجوم.';
        else {
            q('INSERT INTO ratings (id, place_id, stars, comment, author_name) VALUES (?,?,?,?,?)',
              [new_id(), $p['id'], $stars, post('comment', 320) ?: null, post('author_name', 40) ?: null]);
            $ok = 'شكرًا. رأيك اتسجل.'; $p = one_place($id);
        }
    } elseif ($what === 'report') {
        $reason = post('reason', 20);
        if (!isset(REPORT_REASONS[$reason])) $bad = 'اختار سبب البلاغ.';
        else {
            q('INSERT INTO reports (id, place_id, reason, details, reporter_phone) VALUES (?,?,?,?,?)',
              [new_id(), $p['id'], $reason, post('details', 500) ?: null, post('reporter_phone', 20) ?: null]);
            $ok = 'البلاغ وصل. هنراجعه.';
        }
    }
}

$reviews = reviews_for(null, $p['id']);
$rate = ((int) $p['rating_count'] > 0) ? $p['rating_avg'] : null;

ob_start(); ?>
<main class="wrap page">
  <a class="linkish" href="<?= url('places') ?>" style="margin-bottom:14px"><?= icon('store', 16) ?> رجوع للمحلات</a>
  <div style="display:flex;align-items:center;gap:13px;margin:10px 0 18px">
    <?php if ($p['photo_id']): ?>
      <span class="avatar photo" style="width:62px;height:62px">
        <img src="<?= url('img.php?id=' . e($p['photo_id'])) ?>" alt=""></span>
    <?php else: ?>
      <span class="avatar <?= bubble_class($p['category']) ?>" style="width:62px;height:62px">
        <?= icon($p['category'], 28) ?></span>
    <?php endif; ?>
    <div><h1 style="margin:0;display:flex;align-items:center;gap:8px;flex-wrap:wrap"><?= e($p['name_ar']) ?>
      <?= $rate ? '<span class="rate">' . icon('star', 13) . ' ' . e($rate) . '</span>' : '' ?></h1>
      <p class="lede" style="margin:0;display:flex;align-items:center;gap:5px">
        <?= e(CATEGORIES[$p['category']] ?? $p['category']) ?> <?= icon('pin', 13) ?> <?= e($p['zone_name']) ?></p></div>
  </div>

  <?php if ($p['address_note']): ?><p class="lede"><?= e($p['address_note']) ?></p><?php endif; ?>
  <?php if ($p['hours_note']): ?>
    <p class="lede" style="display:flex;align-items:center;gap:6px"><?= icon('clock', 14) ?> <?= e($p['hours_note']) ?></p>
  <?php endif; ?>
  <?php if ($p['note']): ?><p class="lede"><?= e($p['note']) ?></p><?php endif; ?>

  <?php if ($p['phone']): ?><?= tel_box($p['phone']) ?><?php endif; ?>
  <?php if ($p['whatsapp']): ?>
    <a class="btn wa wide" style="width:100%;margin-top:10px" target="_blank" rel="noreferrer"
       href="<?= e(wa_link($p['whatsapp'], 'السلام عليكم، عايز أطلب من ' . $p['name_ar'])) ?>">
      <?= icon('wa', 20) ?> افتح واتساب</a>
  <?php endif; ?>

  <?= flash($ok, $bad) ?>

  <h2>آراء الناس <?= $rate ? '(' . (int) $p['rating_count'] . ')' : '' ?></h2>
  <?php if (!$reviews): ?><p class="lede">لسه مفيش آراء مكتوبة. كن أول واحد.</p>
  <?php else: ?><div class="card"><?php foreach ($reviews as $r): ?>
    <div class="review"><div class="top"><?= stars_row((int) $r['stars']) ?>
      <span class="who"><?= e($r['author_name'] ?: 'من غير اسم') ?></span>
      <span class="when"><?= e(since_ar($r['created_at'])) ?></span></div>
      <p><?= e($r['comment']) ?></p></div>
  <?php endforeach; ?></div><?php endif; ?>

  <?php include __DIR__ . '/_feedback.php'; ?>
  <?= site_footer() ?>
</main>
<?php
layout($p['name_ar'], (string) ob_get_clean());
