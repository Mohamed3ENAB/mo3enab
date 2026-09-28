<?php
declare(strict_types=1);

$u = require_role('customer', 'admin');
$msg = null; $err = null;

if (is_post()) {
    switch (post('do')) {
        case 'new_request':
            $body = post('body', 500);
            $kind = post('kind', 20);
            $zone = (int) post('zone_id', 6);
            if (mb_strlen($body) < 8)          { $err = 'اكتب طلبك بوضوح شوية (٨ حروف على الأقل).'; break; }
            if ($kind && !isset(SERVICES[$kind])) { $err = 'اختار نوع الطلب.'; break; }
            $open = (int) val("SELECT COUNT(*) FROM requests WHERE user_id = ? AND status = 'open' AND expires_at > NOW()", [$u['id']]);
            if ($open >= 5) { $err = 'عندك ٥ طلبات مفتوحة. اقفل واحد قبل ما تفتح جديد.'; break; }

            $rid = new_id();
            q('INSERT INTO requests (id, user_id, zone_id, kind, body, contact_phone, hide_phone, owner_token, expires_at)
               VALUES (?,?,?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL 12 HOUR))',
              [$rid, $u['id'], $zone ?: $u['zone_id'], $kind ?: null, $body, $u['phone'],
               isset($_POST['hide_phone']) ? 1 : 0, new_id()]);
            $msg = 'طلبك اتنشر. السواقين المتاحين هيشوفوه.';
            break;

        case 'cancel':
            q("UPDATE requests SET status = 'cancelled' WHERE id = ? AND user_id = ?", [post('rid', 32), $u['id']]);
            $msg = 'الطلب اتلغى.';
            break;

        case 'done':
            $rid = post('rid', 32);
            q("UPDATE requests SET status = 'done' WHERE id = ? AND user_id = ?", [$rid, $u['id']]);
            $msg = 'تمام. لو تحب قيّم السائق من صفحته.';
            break;

        case 'read_notifs':
            q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$u['id']]);
            break;
    }
}

$reqs  = my_requests($u['id']);
$favs  = favorites_of($u['id']);
$notes = notifications_for($u['id']);
$openN = count(array_filter($reqs, fn($r) => $r['status'] === 'open'));
$doneN = count(array_filter($reqs, fn($r) => $r['status'] === 'done'));

ob_start(); ?>
<main class="wrap page dash" id="main">

  <header class="dash-hero card glass">
    <div class="dh-user">
      <?= avatar($u['name'], $u['avatar_id'], false, 62) ?>
      <div>
        <span class="muted small">أهلًا بيك</span>
        <h1><?= e($u['name']) ?></h1>
        <span class="badge good"><?= icon('user', 12) ?> عميل</span>
      </div>
    </div>
    <div class="dh-acts">
      <a class="btn ghost sm" href="<?= url('account') ?>"><?= icon('settings', 15) ?> إعدادات</a>
      <a class="btn ghost sm" href="<?= url('logout') ?>"><?= icon('logout', 15) ?> خروج</a>
    </div>
  </header>

  <?= flash($msg, $err) ?>

  <div class="stats">
    <?= stat_card('طلبات مفتوحة', $openN, 'board', 'a') ?>
    <?= stat_card('طلبات خلصت', $doneN, 'check', 'b') ?>
    <?= stat_card('المفضلة', count($favs), 'heart', 'c') ?>
    <?= stat_card('إشعارات', unread_count($u['id']), 'bell', 'd') ?>
  </div>

  <section class="card accent-a">
    <h2><?= icon('plus', 18) ?> اطلب دلوقتي</h2>
    <form method="post" class="form tight">
      <?= csrf_field() ?><input type="hidden" name="do" value="new_request">
      <span class="lbl">محتاج إيه؟</span>
      <div class="pickchips radio small">
        <?php foreach (SERVICES as $k => $label): ?>
          <label class="pchip <?= bubble_class($k) ?>">
            <input type="radio" name="kind" value="<?= e($k) ?>" <?= $k === 'delivery' ? 'checked' : '' ?>>
            <span><?= icon($k, 17) ?> <?= e($label) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <?= field('تفاصيل الطلب',
          '<textarea name="body" rows="3" maxlength="500" required placeholder="مثال: محتاج توك توك من القيصرية للمحلة الساعة ٦، ومعايا شنطتين."></textarea>', '', true) ?>
      <?= field('المنطقة', zones_select('zone_id', (int) $u['zone_id']), 'مكان الاستلام.') ?>
      <label class="switch-row">
        <input type="checkbox" name="hide_phone" <?= (int) $u['hide_phone'] ? 'checked' : '' ?>>
        <span class="sw"></span>
        <span><b>اخفي رقمي</b><i>هيبان <?= e(mask_phone($u['phone'])) ?> والسواقين يردّوا من جوه التطبيق.</i></span>
      </label>
      <button class="btn call wide" data-sfx="ok"><?= icon('send', 17) ?> انشر الطلب</button>
    </form>
  </section>

  <section class="card">
    <h2><?= icon('board', 18) ?> طلباتي</h2>
    <?php if (!$reqs): ?>
      <?= empty_state('لسه مفتحتش أي طلب', 'اكتب طلبك فوق وهيوصل للسواقين المتاحين.', 'board') ?>
    <?php else: ?>
      <ul class="myreqs">
        <?php foreach ($reqs as $r): ?>
          <li class="mr <?= e($r['status']) ?>">
            <div class="mr-top">
              <span class="chip <?= bubble_class((string) $r['kind']) ?>"><?= icon((string) $r['kind'], 14) ?> <?= e(SERVICES[$r['kind']] ?? 'طلب') ?></span>
              <?= status_badge($r['status']) ?>
              <span class="muted time"><?= e(since_ar($r['created_at'])) ?></span>
            </div>
            <p><?= nl2br(e($r['body'])) ?></p>

            <?php if ($r['status'] === 'taken' && $r['driver_name']): ?>
              <div class="mr-driver">
                <?= icon('tuktuk', 16) ?>
                <b><?= e($r['driver_name']) ?></b> استلم طلبك
                <a class="btn call sm" href="<?= e(tel_link($r['driver_phone'])) ?>" data-sfx="call"><?= icon('phone', 14) ?> اتصال</a>
              </div>
            <?php endif; ?>

            <div class="mr-acts">
              <a class="btn ghost sm" href="<?= url('t/' . e($r['owner_token'])) ?>">
                <?= icon('chat', 15) ?> الردود<?= $r['msg_count'] ? ' (' . (int) $r['msg_count'] . ')' : '' ?>
              </a>
              <?php if (in_array($r['status'], ['open', 'taken'], true)): ?>
                <form method="post" class="inline"><?= csrf_field() ?>
                  <input type="hidden" name="do" value="done"><input type="hidden" name="rid" value="<?= e($r['id']) ?>">
                  <button class="btn ghost sm" data-sfx="ok"><?= icon('check', 15) ?> خلص</button>
                </form>
                <form method="post" class="inline" data-confirm="تلغي الطلب دا؟"><?= csrf_field() ?>
                  <input type="hidden" name="do" value="cancel"><input type="hidden" name="rid" value="<?= e($r['id']) ?>">
                  <button class="btn ghost sm danger"><?= icon('close', 15) ?> إلغاء</button>
                </form>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2><?= icon('heart', 18) ?> المفضلة</h2>
    <?php if (!$favs): ?>
      <?= empty_state('مفيش حاجة في المفضلة', 'دوس على القلب جنب أي سائق أو محل عشان تلاقيه هنا بسرعة.', 'heart', 'تصفّح الدليل', url('')) ?>
    <?php else: ?>
      <ul class="favlist">
        <?php foreach ($favs as $f):
          $isProv = !empty($f['provider_id']) && $f['p_name'];
          $isPl   = !empty($f['place_id']) && $f['l_name'];
          if (!$isProv && !$isPl) continue;
          $name  = $isProv ? $f['p_name'] : $f['l_name'];
          $photo = $isProv ? $f['p_photo'] : $f['l_photo'];
          $phone = $isProv ? $f['p_phone'] : $f['l_phone'];
          $href  = $isProv ? url('p/' . $f['provider_id']) : url('m/' . $f['place_id']);
          $live  = $isProv && is_live(['is_available' => $f['is_available'], 'available_at' => $f['available_at']]);
        ?>
          <li>
            <a class="fv-main" href="<?= e($href) ?>">
              <?= avatar($name, $photo, $isProv && (int) $f['is_verified'] === 1, 42) ?>
              <div><b><?= e($name) ?></b>
                <span class="muted small"><?= $isProv ? ($live ? 'متاح دلوقتي' : 'مش متاح') : e(CATEGORIES[$f['category']] ?? 'محل') ?></span>
              </div>
            </a>
            <?php if ($phone): ?><a class="btn call sm" href="<?= e(tel_link($phone)) ?>" data-sfx="call"><?= icon('phone', 14) ?></a><?php endif; ?>
            <form method="post" action="<?= url('fav') ?>" class="inline">
              <?= csrf_field() ?>
              <?= $isProv ? '<input type="hidden" name="provider_id" value="' . e((string) $f['provider_id']) . '">' : '<input type="hidden" name="place_id" value="' . e((string) $f['place_id']) . '">' ?>
              <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
              <button class="iconbtn" aria-label="شيل من المفضلة"><?= icon('close', 16) ?></button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card" id="notifs">
    <h2><?= icon('bell', 18) ?> الإشعارات
      <?php if (unread_count($u['id'])): ?>
        <form method="post" class="inline mr-auto"><?= csrf_field() ?>
          <input type="hidden" name="do" value="read_notifs">
          <button class="linkbtn">علّم الكل مقروء</button>
        </form>
      <?php endif; ?>
    </h2>
    <?= notifications_block($notes) ?>
  </section>

</main>
<?php
layout('حسابي', (string) ob_get_clean(), ['nav' => 'dashboard/customer']);
