<?php
declare(strict_types=1);

$p = $id ? one_provider($id) : null;
if (!$p) { http_response_code(404); layout('مش موجود',
    '<main class="wrap page" id="main">' . empty_state('السائق دا مش موجود', 'يمكن اتوقف أو اتشال من الدليل.', 'search', 'رجوع للدليل', url('')) . '</main>',
    ['nav' => 'home']); return; }

$msg = null; $err = null;

if (is_post()) {
    switch (post('do')) {
        case 'rate':
            $stars = max(1, min(5, (int) post('stars', 2)));
            q('INSERT INTO ratings (id, user_id, provider_id, stars, comment, author_name) VALUES (?,?,?,?,?,?)',
              [new_id(), uid(), $p['id'], $stars, post('comment', 320) ?: null,
               post('author_name', 40) ?: (current_user()['name'] ?? null)]);
            if ($p['user_id']) notify($p['user_id'], 'تقييم جديد ⭐', $stars . ' من ٥', url('dashboard/driver'), 'star');
            $msg = 'شكرًا. تقييمك اتسجّل.';
            break;

        case 'report':
            $reason = post('reason', 20);
            if (!isset(REPORT_REASONS[$reason])) { $err = 'اختار سبب البلاغ.'; break; }
            q('INSERT INTO reports (id, provider_id, reason, details, reporter_phone) VALUES (?,?,?,?,?)',
              [new_id(), $p['id'], $reason, post('details', 500) ?: null, post('reporter_phone', 20) ?: null]);
            notify_admins('بلاغ جديد', REPORT_REASONS[$reason] . ' — عن ' . $p['display_name'], url('admin/reports'), 'alert');
            $msg = 'البلاغ وصل للإدارة. شكرًا إنك بلّغت.';
            break;
    }
    $p = one_provider($p['id']);
}

$live  = is_live($p);
$revs  = reviews_for($p['id'], null);
$servs = services_of($p);

ob_start(); ?>
<main class="wrap page" id="main">

  <section class="profile card glass <?= $live ? 'is-live' : '' ?>">
    <div class="pf-top">
      <?= avatar($p['display_name'], $p['photo_id'], (bool) $p['is_verified'], 84) ?>
      <div class="pf-id">
        <h1><?= e($p['display_name']) ?></h1>
        <p class="muted"><?= icon('pin', 13) ?> <?= e($p['zone_name']) ?>
          <?= $p['vehicle_note'] ? ' · ' . e($p['vehicle_note']) : '' ?></p>
        <div class="pf-badges">
          <?= state_pill($live, $p['available_at']) ?>
          <?php if ($p['is_verified']): ?><span class="badge info"><?= icon('shield', 12) ?> موثّق</span><?php endif; ?>
          <?php if (!empty($p['rating_count'])): ?>
            <span class="badge"><?= icon('star', 12) ?> <?= e((string) $p['rating_avg']) ?> (<?= (int) $p['rating_count'] ?>)</span>
          <?php endif; ?>
        </div>
      </div>
      <?= fav_button($p['id'], null) ?>
    </div>

    <?php if ($servs): ?>
      <div class="chips">
        <?php foreach ($servs as $s): if (!isset(SERVICES[$s])) continue; ?>
          <span class="chip <?= bubble_class($s) ?>"><?= icon($s, 14) ?> <?= e(SERVICES[$s]) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($p['note']): ?><p class="note big"><?= icon('info', 14) ?> <?= e($p['note']) ?></p><?php endif; ?>

    <?= tel_box($p['phone']) ?>
    <?php if ($p['whatsapp']): ?>
      <a class="btn wa wide" target="_blank" rel="noopener"
         href="<?= e(wa_link($p['whatsapp'], 'السلام عليكم، لقيت رقمك في تطبيق «في السكة».')) ?>">
        <?= icon('wa', 18) ?> كلّمه على واتساب
      </a>
    <?php endif; ?>
  </section>

  <?= flash($msg, $err) ?>

  <section class="card">
    <h2><?= icon('star', 18) ?> التقييمات</h2>
    <?= reviews_block($revs) ?>
  </section>

  <?= rating_form(url('p/' . e($p['id'])), $p['id'], null) ?>

  <details class="card soft rep">
    <summary><?= icon('alert', 16) ?> فيه مشكلة؟ بلّغ الإدارة</summary>
    <form method="post" class="form tight">
      <?= csrf_field() ?><input type="hidden" name="do" value="report">
      <span class="lbl">إيه اللي حصل؟</span>
      <div class="pickchips radio small">
        <?php foreach (REPORT_REASONS as $k => $label): ?>
          <label class="pchip"><input type="radio" name="reason" value="<?= e($k) ?>" required><span><?= e($label) ?></span></label>
        <?php endforeach; ?>
      </div>
      <textarea name="details" rows="3" maxlength="500" placeholder="احكيلنا التفاصيل"></textarea>
      <input type="tel" name="reporter_phone" placeholder="رقمك (اختياري — لو محتاجين نكلّمك)">
      <button class="btn ghost danger"><?= icon('send', 16) ?> ابعت البلاغ</button>
    </form>
  </details>

  <?= site_footer() ?>
</main>
<?php
layout($p['display_name'], (string) ob_get_clean(), ['nav' => 'home', 'back' => true]);
