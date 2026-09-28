<?php
declare(strict_types=1);

$u = require_login();
if ($u['status'] === 'active') redirect(dash_path($u['role']));

$msg = null; $err = null;

// يقدر يغيّر أي صورة وهو مستني — أغلب الرفض بيبقى «الصورة مش واضحة»
if (is_post() && post('do') === 'redoc') {
    $kind = post('kind', 20);
    if (!isset(DOC_KINDS[$kind])) {
        $err = 'نوع المستند مش معروف.';
    } else {
        $r = store_doc('file');
        if (isset($r['error']))   $err = $r['error'];
        elseif (empty($r['id']))  $err = 'اختار صورة الأول.';
        else {
            attach_doc($u['id'], $kind, $r['id']);
            if ($u['status'] === 'rejected') {
                q("UPDATE users SET status = 'pending', reject_reason = NULL WHERE id = ?", [$u['id']]);
                notify_admins('صور جديدة من ' . $u['name'], 'رفع ' . DOC_KINDS[$kind] . ' تاني بعد الرفض.', url('admin/approvals'), 'image');
            }
            $msg = 'الصورة اتحدّثت. الإدارة هتشوفها.';
        }
    }
    $u = row('SELECT * FROM users WHERE id = ?', [$u['id']]);
}

if (is_post() && post('do') === 'photo') {
    $a = store_avatar('photo');
    if (isset($a['error']))  $err = $a['error'];
    elseif (empty($a['id'])) $err = 'اختار صورة الأول.';
    else {
        delete_image($u['avatar_id']);
        q('UPDATE users SET avatar_id = ? WHERE id = ?', [$a['id'], $u['id']]);
        if ($u['provider_id']) q('UPDATE providers SET photo_id = ? WHERE id = ?', [$a['id'], $u['provider_id']]);
        if ($u['place_id'])    q('UPDATE places SET photo_id = ? WHERE id = ?', [$a['id'], $u['place_id']]);
        $msg = 'صورتك اتغيّرت.';
        $u = row('SELECT * FROM users WHERE id = ?', [$u['id']]);
    }
}

$docs = [];
foreach (docs_of($u['id']) as $d) $docs[$d['kind']] = $d;
$rejected = $u['status'] === 'rejected';

ob_start(); ?>
<main class="wrap page" id="main">

  <div class="wait-card card glass pop">
    <div class="wait-ic <?= $rejected ? 'bad' : '' ?>">
      <?= icon($rejected ? 'alert' : 'clock', 34) ?>
      <?php if (!$rejected): ?><span class="ring"></span><span class="ring d2"></span><?php endif; ?>
    </div>

    <?php if ($rejected): ?>
      <h1>الطلب اترفض</h1>
      <p class="lede"><?= $u['reject_reason'] ? e($u['reject_reason']) : 'الإدارة مراجعتش البيانات كويس.' ?></p>
      <p class="muted">غيّر الصورة أو الورق وابعت تاني — الطلب هيرجع للمراجعة لوحده.</p>
    <?php else: ?>
      <h1>طلبك تحت المراجعة</h1>
      <p class="lede">الإدارة بتشوف بياناتك دلوقتي. عادة بتاخد من ساعة لـ يوم.</p>
      <p class="muted">أول ما نوافق هتلاقي الداش بورد بتاعتك اتفتحت، وهيوصلك إشعار هنا.</p>
    <?php endif; ?>

    <div class="wait-meta">
      <?= avatar($u['name'], $u['avatar_id'], false, 56) ?>
      <div>
        <b><?= e($u['name']) ?></b>
        <span class="muted"><?= e(ROLE_NAMES[$u['role']] ?? '') ?> · <?= e($u['phone']) ?></span>
      </div>
      <?= status_badge($u['status']) ?>
    </div>
  </div>

  <?= flash($msg, $err) ?>

  <section class="card">
    <h2><?= icon('image', 18) ?> صورك ومستنداتك</h2>
    <p class="muted small">تقدر تغيّر أي صورة دلوقتي. المستندات محدش بيشوفها غير الإدارة.</p>

    <form method="post" enctype="multipart/form-data" class="form tight">
      <?= csrf_field() ?><input type="hidden" name="do" value="photo">
      <?= avatar_field('photo', $u['avatar_id'], 'صورة الحساب') ?>
      <button class="btn ghost sm" data-sfx="ok"><?= icon('refresh', 15) ?> حدّث الصورة</button>
    </form>

    <ul class="doclist">
      <?php foreach (DOC_KINDS as $kind => $label):
        $have = $docs[$kind] ?? null;
        if (!$have && !in_array($kind, ['national_id', 'license'], true)) continue; ?>
        <li>
          <span class="dl-ic"><?= icon($kind === 'license' ? 'license' : 'idcard', 20) ?></span>
          <div class="dl-main">
            <b><?= e($label) ?></b>
            <?php if ($have): ?>
              <span class="muted small">اترفعت <?= e(since_ar($have['created_at'])) ?> · <?= status_badge($have['status']) ?></span>
            <?php else: ?>
              <span class="muted small">مترفعتش لسه</span>
            <?php endif; ?>
          </div>
          <?php if ($have): ?>
            <a class="btn ghost sm" href="<?= url('img.php?id=' . e($have['image_id'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 15) ?></a>
          <?php endif; ?>
          <form method="post" enctype="multipart/form-data" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="do" value="redoc">
            <input type="hidden" name="kind" value="<?= e($kind) ?>">
            <label class="btn ghost sm">
              <?= icon('upload', 15) ?> <?= $have ? 'غيّر' : 'ارفع' ?>
              <input type="file" name="file" accept="image/png,image/jpeg,image/webp,image/gif" hidden data-autosubmit>
            </label>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <div class="row-btns">
    <a class="btn ghost" href="<?= url('') ?>"><?= icon('home', 16) ?> تصفّح الدليل</a>
    <a class="btn ghost" href="<?= url('logout') ?>"><?= icon('logout', 16) ?> خروج</a>
  </div>
</main>
<?php
layout('تحت المراجعة', (string) ob_get_clean(), ['nav' => 'dashboard']);
