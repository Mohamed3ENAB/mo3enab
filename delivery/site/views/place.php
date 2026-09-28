<?php
declare(strict_types=1);

$pl = $id ? one_place($id) : null;
if (!$pl) { http_response_code(404); layout('مش موجود',
    '<main class="wrap page" id="main">' . empty_state('المحل دا مش موجود', 'يمكن اتقفل أو اتشال من الدليل.', 'search', 'كل المحلات', url('places')) . '</main>',
    ['nav' => 'places']); return; }

$msg = null; $err = null;

if (is_post()) {
    switch (post('do')) {
        case 'rate':
            $stars = max(1, min(5, (int) post('stars', 2)));
            q('INSERT INTO ratings (id, user_id, place_id, stars, comment, author_name) VALUES (?,?,?,?,?,?)',
              [new_id(), uid(), $pl['id'], $stars, post('comment', 320) ?: null,
               post('author_name', 40) ?: (current_user()['name'] ?? null)]);
            if ($pl['user_id']) notify($pl['user_id'], 'تقييم جديد ⭐', $stars . ' من ٥', url('dashboard/merchant'), 'star');
            $msg = 'شكرًا. تقييمك اتسجّل.';
            break;

        case 'report':
            $reason = post('reason', 20);
            if (!isset(REPORT_REASONS[$reason])) { $err = 'اختار سبب البلاغ.'; break; }
            q('INSERT INTO reports (id, place_id, reason, details, reporter_phone) VALUES (?,?,?,?,?)',
              [new_id(), $pl['id'], $reason, post('details', 500) ?: null, post('reporter_phone', 20) ?: null]);
            notify_admins('بلاغ جديد', REPORT_REASONS[$reason] . ' — عن ' . $pl['name_ar'], url('admin/reports'), 'alert');
            $msg = 'البلاغ وصل للإدارة.';
            break;
    }
    $pl = one_place($pl['id']);
}

$open = (int) ($pl['is_open'] ?? 1) === 1;
$revs = reviews_for(null, $pl['id']);

ob_start(); ?>
<main class="wrap page" id="main">

  <section class="profile card glass <?= $open ? 'is-live' : '' ?>">
    <div class="pf-top">
      <?= avatar($pl['name_ar'], $pl['photo_id'], false, 84) ?>
      <div class="pf-id">
        <h1><?= e($pl['name_ar']) ?></h1>
        <p class="muted"><?= icon($pl['category'], 13) ?> <?= e(CATEGORIES[$pl['category']] ?? 'محل') ?>
          · <?= icon('pin', 13) ?> <?= e($pl['zone_name']) ?></p>
        <div class="pf-badges">
          <span class="state <?= $open ? 'on' : 'off' ?>"><span class="beacon"><i></i><i></i></span><?= $open ? 'فاتح دلوقتي' : 'قافل' ?></span>
          <?php if (!empty($pl['rating_count'])): ?>
            <span class="badge"><?= icon('star', 12) ?> <?= e((string) $pl['rating_avg']) ?> (<?= (int) $pl['rating_count'] ?>)</span>
          <?php endif; ?>
        </div>
      </div>
      <?= fav_button(null, $pl['id']) ?>
    </div>

    <ul class="infolist">
      <?php if ($pl['address_note']): ?><li><?= icon('pin', 15) ?> <?= e($pl['address_note']) ?></li><?php endif; ?>
      <?php if ($pl['hours_note']): ?><li><?= icon('clock', 15) ?> <?= e($pl['hours_note']) ?></li><?php endif; ?>
      <?php if ($pl['note']): ?><li><?= icon('info', 15) ?> <?= e($pl['note']) ?></li><?php endif; ?>
    </ul>

    <?php if ($pl['phone']): ?><?= tel_box($pl['phone']) ?><?php endif; ?>
    <?php if ($pl['whatsapp']): ?>
      <a class="btn wa wide" target="_blank" rel="noopener" href="<?= e(wa_link($pl['whatsapp'])) ?>">
        <?= icon('wa', 18) ?> كلّمه على واتساب
      </a>
    <?php endif; ?>
  </section>

  <?= flash($msg, $err) ?>

  <section class="card">
    <h2><?= icon('star', 18) ?> التقييمات</h2>
    <?= reviews_block($revs) ?>
  </section>

  <?= rating_form(url('m/' . e($pl['id'])), null, $pl['id']) ?>

  <details class="card soft rep">
    <summary><?= icon('alert', 16) ?> فيه مشكلة؟ بلّغ الإدارة</summary>
    <form method="post" class="form tight">
      <?= csrf_field() ?><input type="hidden" name="do" value="report">
      <div class="pickchips radio small">
        <?php foreach (REPORT_REASONS as $k => $label): ?>
          <label class="pchip"><input type="radio" name="reason" value="<?= e($k) ?>" required><span><?= e($label) ?></span></label>
        <?php endforeach; ?>
      </div>
      <textarea name="details" rows="3" maxlength="500" placeholder="احكيلنا التفاصيل"></textarea>
      <input type="tel" name="reporter_phone" placeholder="رقمك (اختياري)">
      <button class="btn ghost danger"><?= icon('send', 16) ?> ابعت البلاغ</button>
    </form>
  </details>

  <?= site_footer() ?>
</main>
<?php
layout($pl['name_ar'], (string) ob_get_clean(), ['nav' => 'places', 'back' => true]);
