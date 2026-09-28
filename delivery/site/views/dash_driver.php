<?php
declare(strict_types=1);

$u = require_role('driver');
$p = provider_of_user($u['id']);
if (!$p) { set_flash('bad', 'ملف السائق بتاعك مش موجود. كلّم الإدارة.'); redirect('dashboard/pending'); }

$msg = null; $err = null;

if (is_post()) {
    switch (post('do')) {
        case 'avail':
            $on = post('v', 3) === '1';
            q('UPDATE providers SET is_available = ?, available_at = ' . ($on ? 'NOW()' : 'available_at') . ' WHERE id = ?',
              [$on ? 1 : 0, $p['id']]);
            $msg = $on ? 'تمام — اسمك دلوقتي في المتاحين.' : 'قفلت التوفر. مش هتظهر كمتاح.';
            break;

        case 'profile':
            $services = array_values(array_intersect(
                array_keys(SERVICES), array_map('strval', (array) ($_POST['services'] ?? []))
            ));
            if (!$services) { $err = 'اختار خدمة واحدة على الأقل.'; break; }
            $photoId = $p['photo_id'];
            $a = store_avatar('photo');
            if (isset($a['error'])) { $err = $a['error']; break; }
            if (!empty($a['id'])) { delete_image($p['photo_id']); $photoId = $a['id']; }

            q('UPDATE providers SET whatsapp = ?, zone_id = ?, services = ?, vehicle_note = ?, note = ?, photo_id = ?
                WHERE id = ?',
              [post('whatsapp', 20) ?: null, (int) post('zone_id', 6) ?: $p['zone_id'],
               implode(',', $services), post('vehicle_note', 60) ?: null, post('note', 160) ?: null,
               $photoId, $p['id']]);
            if (!empty($a['id'])) q('UPDATE users SET avatar_id = ? WHERE id = ?', [$photoId, $u['id']]);
            $msg = 'بياناتك اتحدّثت.';
            break;

        case 'take':
            $rid = post('rid', 32);
            $r = one_request($rid);
            if (!$r || $r['status'] !== 'open') { $err = 'الطلب دا اتاخد أو اتقفل.'; break; }
            q("UPDATE requests SET status = 'taken', taken_by = ?, taken_at = NOW()
                WHERE id = ? AND status = 'open'", [$p['id'], $rid]);
            if ($r['user_id']) {
                notify($r['user_id'], 'سائق استلم طلبك 🚗',
                       $p['display_name'] . ' — ' . $p['phone'], url('dashboard/customer'), 'tuktuk');
            }
            $msg = 'استلمت الطلب. كلّم صاحبه واتفقوا.';
            break;

        case 'finish':
            q("UPDATE requests SET status = 'done' WHERE id = ? AND taken_by = ?", [post('rid', 32), $p['id']]);
            $msg = 'تمام، اتقفل.';
            break;

        case 'read_notifs':
            q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$u['id']]);
            break;
    }
    $p = provider_of_user($u['id']);
}

$live  = is_live($p);
$st    = driver_stats($p['id']);
$feed  = open_requests();
$mine  = requests_taken_by($p['id']);
$revs  = ratings_of_owner($p['id'], null);
$notes = notifications_for($u['id']);
$sSel  = services_of($p);
$docs  = [];
foreach (docs_of($u['id']) as $d) $docs[$d['kind']] = $d;

ob_start(); ?>
<main class="wrap page dash" id="main">

  <header class="dash-hero card glass <?= $live ? 'is-live' : '' ?>">
    <div class="dh-user">
      <?= avatar($p['display_name'], $p['photo_id'], (bool) $p['is_verified'], 62) ?>
      <div>
        <span class="muted small">سائق</span>
        <h1><?= e($p['display_name']) ?></h1>
        <span class="badge <?= $p['is_verified'] ? 'good' : 'warn' ?>">
          <?= icon($p['is_verified'] ? 'shield' : 'clock', 12) ?>
          <?= $p['is_verified'] ? 'موثّق' : 'مش موثّق لسه' ?>
        </span>
      </div>
    </div>
    <div class="dh-acts">
      <a class="btn ghost sm" href="<?= url('p/' . e($p['id'])) ?>"><?= icon('eye', 15) ?> صفحتي</a>
      <a class="btn ghost sm" href="<?= url('account') ?>"><?= icon('settings', 15) ?></a>
      <a class="btn ghost sm" href="<?= url('logout') ?>"><?= icon('logout', 15) ?></a>
    </div>
  </header>

  <?= flash($msg, $err) ?>

  <!-- مفتاح التوفر — أهم زرار في الصفحة -->
  <section class="card avail <?= $live ? 'on' : 'off' ?>">
    <div class="av-txt">
      <span class="state <?= $live ? 'on' : 'off' ?>"><span class="beacon"><i></i><i></i></span><?= $live ? 'متاح دلوقتي' : 'مش متاح' ?></span>
      <p class="muted small">
        <?= $live
          ? 'اسمك في أول الدليل. التوفر بينتهي لوحده بعد ' . AVAILABILITY_HOURS . ' ساعات.'
          : 'افتح التوفر لما تكون جاهز تستلم مشاوير.' ?>
      </p>
    </div>
    <form method="post" class="inline">
      <?= csrf_field() ?><input type="hidden" name="do" value="avail">
      <input type="hidden" name="v" value="<?= $live ? '0' : '1' ?>">
      <button class="btn <?= $live ? 'ghost' : 'call' ?> big" data-sfx="<?= $live ? 'tap' : 'ok' ?>">
        <?= icon($live ? 'sleep' : 'bolt', 18) ?> <?= $live ? 'اقفل التوفر' : 'افتح التوفر' ?>
      </button>
    </form>
  </section>

  <div class="stats">
    <?= stat_card('طلبات مفتوحة', $st['open'], 'board', 'a') ?>
    <?= stat_card('استلمتها', $st['taken'], 'check', 'b') ?>
    <?= stat_card('خلصت', $st['done'], 'bolt', 'c') ?>
    <?= stat_card('تقييمك', $st['stars'] ?: '—', 'star', 'd') ?>
  </div>

  <section class="card accent-a">
    <h2><?= icon('board', 18) ?> طلبات مفتوحة دلوقتي</h2>
    <?php if (!$feed): ?>
      <?= empty_state('مفيش طلبات دلوقتي', 'أول ما حد يطلب هتلاقيه هنا. سيب التوفر مفتوح.', 'board') ?>
    <?php else: ?>
      <div class="cards">
        <?php foreach ($feed as $r) echo request_card($r, true, $p, url('dashboard/driver')); ?>
      </div>
    <?php endif; ?>
  </section>

  <?php if ($mine): ?>
  <section class="card">
    <h2><?= icon('check', 18) ?> الطلبات اللي استلمتها</h2>
    <ul class="myreqs">
      <?php foreach ($mine as $r): ?>
        <li class="mr <?= e($r['status']) ?>">
          <div class="mr-top">
            <span class="chip <?= bubble_class((string) $r['kind']) ?>"><?= icon((string) $r['kind'], 14) ?> <?= e(SERVICES[$r['kind']] ?? 'طلب') ?></span>
            <?= status_badge($r['status']) ?>
            <span class="muted time"><?= e(since_ar($r['taken_at'] ?: $r['created_at'])) ?></span>
          </div>
          <p><?= nl2br(e($r['body'])) ?></p>
          <div class="mr-acts">
            <?php if ($r['contact_phone']): ?>
              <a class="btn call sm" href="<?= e(tel_link($r['contact_phone'])) ?>" data-sfx="call"><?= icon('phone', 14) ?> اتصال</a>
            <?php endif; ?>
            <a class="btn ghost sm" href="<?= url('t/p/' . e($p['token']) . '/' . e($r['id'])) ?>"><?= icon('chat', 15) ?> المحادثة</a>
            <?php if ($r['status'] === 'taken'): ?>
              <form method="post" class="inline"><?= csrf_field() ?>
                <input type="hidden" name="do" value="finish"><input type="hidden" name="rid" value="<?= e($r['id']) ?>">
                <button class="btn ghost sm" data-sfx="ok"><?= icon('check', 15) ?> خلّصت</button>
              </form>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <section class="card">
    <h2><?= icon('edit', 18) ?> ملفي في الدليل</h2>
    <form method="post" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?><input type="hidden" name="do" value="profile">
      <?= avatar_field('photo', $p['photo_id'], 'صورتك في الدليل') ?>
      <span class="lbl">بتشتغل إيه؟</span>
      <div class="pickchips">
        <?php foreach (SERVICES as $k => $label): ?>
          <label class="pchip <?= bubble_class($k) ?>">
            <input type="checkbox" name="services[]" value="<?= e($k) ?>" <?= in_array($k, $sSel, true) ? 'checked' : '' ?>>
            <span><?= icon($k, 17) ?> <?= e($label) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <?= field('المنطقة', zones_select('zone_id', (int) $p['zone_id'])) ?>
      <?= field('رقم واتساب', '<input type="tel" name="whatsapp" inputmode="numeric" value="' . e((string) $p['whatsapp']) . '" placeholder="اختياري">') ?>
      <?= field('العربية', '<input type="text" name="vehicle_note" maxlength="60" value="' . e((string) $p['vehicle_note']) . '" placeholder="مثال: توك توك أحمر">') ?>
      <?= field('كلمة عنك', '<textarea name="note" rows="2" maxlength="160" placeholder="مواعيدك أو المناطق اللي بتوصّلها">' . e((string) $p['note']) . '</textarea>') ?>
      <button class="btn call" data-sfx="ok"><?= icon('check', 17) ?> احفظ</button>
    </form>
  </section>

  <section class="card">
    <h2><?= icon('idcard', 18) ?> مستنداتي</h2>
    <p class="muted small">محدش بيشوف الصور دي غير الإدارة. لو الإدارة طلبت صورة أوضح، غيّرها من هنا.</p>
    <ul class="doclist">
      <?php foreach (['national_id', 'license', 'vehicle_license'] as $kind):
        $have = $docs[$kind] ?? null; ?>
        <li>
          <span class="dl-ic"><?= icon($kind === 'national_id' ? 'idcard' : 'license', 20) ?></span>
          <div class="dl-main">
            <b><?= e(DOC_KINDS[$kind]) ?></b>
            <span class="muted small"><?= $have ? 'اترفعت ' . e(since_ar($have['created_at'])) : 'مترفعتش' ?></span>
          </div>
          <?= $have ? status_badge($have['status']) : '' ?>
          <?php if ($have): ?>
            <a class="btn ghost sm" href="<?= url('img.php?id=' . e($have['image_id'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 15) ?></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <a class="btn ghost sm" href="<?= url('dashboard/pending') ?>"><?= icon('upload', 15) ?> غيّر المستندات</a>
  </section>

  <section class="card">
    <h2><?= icon('star', 18) ?> تقييمات الناس</h2>
    <?= reviews_block($revs) ?>
  </section>

  <section class="card" id="notifs">
    <h2><?= icon('bell', 18) ?> الإشعارات
      <?php if (unread_count($u['id'])): ?>
        <form method="post" class="inline mr-auto"><?= csrf_field() ?>
          <input type="hidden" name="do" value="read_notifs"><button class="linkbtn">علّم الكل مقروء</button>
        </form>
      <?php endif; ?>
    </h2>
    <?= notifications_block($notes) ?>
  </section>

</main>
<?php
layout('داش بورد السائق', (string) ob_get_clean(), ['nav' => 'dashboard/driver']);
