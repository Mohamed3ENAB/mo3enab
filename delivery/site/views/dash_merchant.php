<?php
declare(strict_types=1);

$u  = require_role('merchant');
$pl = place_of_user($u['id']);
if (!$pl) { set_flash('bad', 'ملف المحل بتاعك مش موجود. كلّم الإدارة.'); redirect('dashboard/pending'); }

$msg = null; $err = null;

if (is_post()) {
    switch (post('do')) {
        case 'open':
            q('UPDATE places SET is_open = ? WHERE id = ?', [post('v', 3) === '1' ? 1 : 0, $pl['id']]);
            $msg = post('v', 3) === '1' ? 'المحل دلوقتي فاتح.' : 'قفلت المحل. هيبان «قافل» في الدليل.';
            break;

        case 'profile':
            $name = post('name_ar', 80);
            $cat  = post('category', 20);
            if (mb_strlen($name) < 2)      { $err = 'اكتب اسم المحل.'; break; }
            if (!isset(CATEGORIES[$cat]))  { $err = 'اختار نوع المحل.'; break; }

            $photoId = $pl['photo_id'];
            $a = store_avatar('photo');
            if (isset($a['error'])) { $err = $a['error']; break; }
            if (!empty($a['id'])) { delete_image($pl['photo_id']); $photoId = $a['id']; }

            q('UPDATE places SET name_ar = ?, category = ?, zone_id = ?, whatsapp = ?, address_note = ?,
                      hours_note = ?, note = ?, photo_id = ? WHERE id = ?',
              [$name, $cat, (int) post('zone_id', 6) ?: $pl['zone_id'], post('whatsapp', 20) ?: null,
               post('address_note', 140) ?: null, post('hours_note', 60) ?: null,
               post('note', 160) ?: null, $photoId, $pl['id']]);
            if (!empty($a['id'])) q('UPDATE users SET avatar_id = ? WHERE id = ?', [$photoId, $u['id']]);
            $msg = 'بيانات المحل اتحدّثت.';
            break;

        case 'read_notifs':
            q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$u['id']]);
            break;
    }
    $pl = place_of_user($u['id']);
}

$open  = (int) $pl['is_open'] === 1;
$sum   = rating_summary(null, $pl['id']);
$revs  = ratings_of_owner(null, $pl['id']);
$notes = notifications_for($u['id']);

ob_start(); ?>
<main class="wrap page dash" id="main">

  <header class="dash-hero card glass <?= $open ? 'is-live' : '' ?>">
    <div class="dh-user">
      <?= avatar($pl['name_ar'], $pl['photo_id'], false, 62) ?>
      <div>
        <span class="muted small"><?= e(CATEGORIES[$pl['category']] ?? 'محل') ?></span>
        <h1><?= e($pl['name_ar']) ?></h1>
        <span class="badge <?= $open ? 'good' : 'muted' ?>"><?= icon('clock', 12) ?> <?= $open ? 'فاتح' : 'قافل' ?></span>
      </div>
    </div>
    <div class="dh-acts">
      <a class="btn ghost sm" href="<?= url('m/' . e($pl['id'])) ?>"><?= icon('eye', 15) ?> صفحتي</a>
      <a class="btn ghost sm" href="<?= url('account') ?>"><?= icon('settings', 15) ?></a>
      <a class="btn ghost sm" href="<?= url('logout') ?>"><?= icon('logout', 15) ?></a>
    </div>
  </header>

  <?= flash($msg, $err) ?>

  <section class="card avail <?= $open ? 'on' : 'off' ?>">
    <div class="av-txt">
      <span class="state <?= $open ? 'on' : 'off' ?>"><span class="beacon"><i></i><i></i></span><?= $open ? 'المحل فاتح' : 'المحل قافل' ?></span>
      <p class="muted small">الناس بتشوف الحالة دي في الدليل على طول.</p>
    </div>
    <form method="post" class="inline">
      <?= csrf_field() ?><input type="hidden" name="do" value="open">
      <input type="hidden" name="v" value="<?= $open ? '0' : '1' ?>">
      <button class="btn <?= $open ? 'ghost' : 'call' ?> big" data-sfx="<?= $open ? 'tap' : 'ok' ?>">
        <?= icon($open ? 'sleep' : 'bolt', 18) ?> <?= $open ? 'اقفل المحل' : 'افتح المحل' ?>
      </button>
    </form>
  </section>

  <div class="stats">
    <?= stat_card('التقييم', $sum['avg'] ?: '—', 'star', 'a') ?>
    <?= stat_card('عدد التقييمات', $sum['n'], 'chat', 'b') ?>
    <?= stat_card('الحالة', $open ? 'فاتح' : 'قافل', 'clock', 'c') ?>
    <?= stat_card('إشعارات', unread_count($u['id']), 'bell', 'd') ?>
  </div>

  <section class="card accent-a">
    <h2><?= icon('store', 18) ?> بيانات المحل</h2>
    <form method="post" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?><input type="hidden" name="do" value="profile">
      <?= avatar_field('photo', $pl['photo_id'], 'صورة المحل') ?>
      <?= field('اسم المحل', '<input type="text" name="name_ar" maxlength="80" required value="' . e($pl['name_ar']) . '">', '', true) ?>
      <span class="lbl">نوع المحل</span>
      <div class="pickchips radio small">
        <?php foreach (CATEGORIES as $k => $label): ?>
          <label class="pchip">
            <input type="radio" name="category" value="<?= e($k) ?>" <?= $pl['category'] === $k ? 'checked' : '' ?>>
            <span><?= icon($k, 17) ?> <?= e($label) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <?= field('المنطقة', zones_select('zone_id', (int) $pl['zone_id'])) ?>
      <?= field('رقم واتساب', '<input type="tel" name="whatsapp" inputmode="numeric" value="' . e((string) $pl['whatsapp']) . '">') ?>
      <?= field('العنوان', '<input type="text" name="address_note" maxlength="140" value="' . e((string) $pl['address_note']) . '">') ?>
      <?= field('المواعيد', '<input type="text" name="hours_note" maxlength="60" value="' . e((string) $pl['hours_note']) . '" placeholder="مثال: من ٩ ص لـ ١٢ م">') ?>
      <?= field('كلمة عن المحل', '<textarea name="note" rows="2" maxlength="160">' . e((string) $pl['note']) . '</textarea>') ?>
      <button class="btn call" data-sfx="ok"><?= icon('check', 17) ?> احفظ</button>
    </form>
  </section>

  <section class="card">
    <h2><?= icon('star', 18) ?> تقييمات الزباين</h2>
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
layout('داش بورد المحل', (string) ob_get_clean(), ['nav' => 'dashboard/merchant']);
