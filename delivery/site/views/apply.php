<?php
declare(strict_types=1);

$kind  = get('kind', 10) === 'place' ? 'place' : 'driver';
$zones = all_zones();
$ok = $bad = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kind = post('kind', 10) === 'place' ? 'place' : 'driver';
    $name = post('name', 80);
    $phone = preg_replace('/\s/', '', post('phone', 20));
    $wa = preg_replace('/\s/', '', post('whatsapp', 20));
    $zid = (int) post('zone_id', 10);
    $note = post('note', 320);

    $services = array_values(array_intersect((array) ($_POST['services'] ?? []), array_keys(SERVICES)));
    $vehicle  = post('vehicle_note', 60);
    $cat      = post('category', 20);
    $addr     = post('address_note', 140);
    $hours    = post('hours_note', 60);

    if (mb_strlen($name) < 2)                      $bad = 'اكتب الاسم.';
    elseif (!valid_phone($phone, 9, 11))           $bad = 'رقم التليفون مش صح.';
    elseif ($wa !== '' && !valid_phone($wa))       $bad = 'رقم الواتساب مش صح.';
    elseif (!$zid)                                 $bad = 'اختار القرية.';
    elseif (empty($_POST['consent']))              $bad = 'لازم توافق على نشر اسمك ورقمك في الدليل.';
    elseif ($kind === 'driver' && !$services)      $bad = 'اختار نوع الخدمة اللي هتشتغلها.';
    elseif ($kind === 'place' && !isset(CATEGORIES[$cat])) $bad = 'اختار تصنيف المحل.';
    else {
        $img = store_image('photo');
        if (isset($img['error'])) { $bad = $img['error']; }
        else {
            $dup = val("SELECT 1 FROM applications WHERE phone = ? AND status = 'pending' LIMIT 1", [$phone]);
            if ($dup) { $bad = 'طلبك وصلنا خلاص وبنراجعه. هنكلمك قريب.'; }
            else {
                q('INSERT INTO applications
                     (id, kind, name, phone, whatsapp, zone_id, note, photo_id,
                      services, vehicle_note, category, address_note, hours_note)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                  [new_id(), $kind, $name, $phone, $wa ?: null, $zid, $note ?: null, $img['id'],
                   $kind === 'driver' ? implode(',', $services) : null,
                   $vehicle ?: null, $kind === 'place' ? $cat : null,
                   $addr ?: null, $hours ?: null]);
                $ok = 'sent';
            }
        }
    }
}

ob_start(); ?>
<?= hero('userplus', 'سجّل نفسك',
    $kind === 'driver' ? 'عايز تشتغل سائق؟' : 'عندك محل أو مطعم؟',
    'املا البيانات وهنراجعها ونكلمك.', false) ?>
<main class="wrap page" style="padding-top:22px">
<?php if ($ok === 'sent'): ?>
  <div class="empty"><div class="ill"><?= icon('check', 26) ?></div>
    <h3>طلبك وصلنا</h3>
    <p>هنراجعه ونكلمك على الرقم اللي كتبته. لو ورقك تمام، هتلاقي نفسك في الدليل.</p>
    <a class="btn call wide" href="<?= url('') ?>">رجوع للدليل</a></div>
<?php else: ?>
  <div class="zones" style="margin-bottom:18px">
    <a class="zchip" href="<?= url('join/apply?kind=driver') ?>" aria-pressed="<?= $kind === 'driver' ? 'true' : 'false' ?>">سائق</a>
    <a class="zchip" href="<?= url('join/apply?kind=place') ?>" aria-pressed="<?= $kind === 'place' ? 'true' : 'false' ?>">محل أو مطعم</a>
  </div>

  <div class="note"><?= icon('info', 19, 'ic') ?>
    <span>طلبك <strong>مش بيظهر في الدليل على طول</strong>. بنراجعه الأول ونشوف ورقك،
      وبعدها بنفعّله. ده اللي بيخلي الناس تثق في القايمة.</span></div>

  <?= flash(null, $bad) ?>

  <form method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="kind" value="<?= e($kind) ?>">
    <?= photo_field($kind === 'driver' ? 'صورتك' : 'صورة المحل', 'اختياري — بتظهر جنب اسمك في الدليل') ?>

    <label class="field" for="name"><span><?= $kind === 'driver' ? 'اسمك' : 'اسم المحل' ?></span>
      <input id="name" name="name" maxlength="80" required value="<?= e(post('name', 80)) ?>"></label>

    <label class="field" for="phone"><span>التليفون</span>
      <input id="phone" name="phone" type="tel" inputmode="tel" required placeholder="01xxxxxxxxx"
             value="<?= e(post('phone', 20)) ?>"></label>

    <label class="field" for="whatsapp"><span>واتساب (لو مختلف)</span>
      <input id="whatsapp" name="whatsapp" type="tel" inputmode="tel" placeholder="01xxxxxxxxx"
             value="<?= e(post('whatsapp', 20)) ?>"></label>

    <label class="field" for="zone_id"><span>القرية</span>
      <select id="zone_id" name="zone_id" required>
        <option value="" disabled selected>اختار</option>
        <?php foreach ($zones as $z): ?>
          <option value="<?= (int) $z['id'] ?>"><?= e($z['name_ar']) ?></option>
        <?php endforeach; ?></select></label>

    <?php if ($kind === 'driver'): ?>
      <div class="field"><span>هتشتغل إيه؟</span><div class="checks">
        <?php foreach (SERVICES as $k => $v): ?>
          <label><input type="checkbox" name="services[]" value="<?= e($k) ?>"> <?= e($v) ?></label>
        <?php endforeach; ?></div></div>
      <label class="field" for="vehicle_note"><span>المركبة</span>
        <input id="vehicle_note" name="vehicle_note" maxlength="60" placeholder="موتوسيكل / عجلة / توك توك"></label>
    <?php else: ?>
      <label class="field" for="category"><span>تصنيف المحل</span>
        <select id="category" name="category" required>
          <option value="" disabled selected>اختار</option>
          <?php foreach (CATEGORIES as $k => $v): ?>
            <option value="<?= e($k) ?>"><?= e($v) ?></option>
          <?php endforeach; ?></select></label>
      <label class="field" for="address_note"><span>مكان المحل</span>
        <input id="address_note" name="address_note" maxlength="140" placeholder="جنب الجامع الكبير"></label>
      <label class="field" for="hours_note"><span>المواعيد</span>
        <input id="hours_note" name="hours_note" maxlength="60" placeholder="من 10ص لـ 12 بالليل"></label>
    <?php endif; ?>

    <label class="field" for="note"><span>حاجة تحب تقولها؟ (اختياري)</span>
      <textarea id="note" name="note" maxlength="320"></textarea></label>

    <div class="checks" style="margin-bottom:18px">
      <label><input type="checkbox" name="consent" value="1" required>
        موافق على نشر اسمي ورقمي في الدليل</label></div>

    <button class="btn call wide" type="submit">ابعت الطلب</button>
  </form>
<?php endif; ?>
</main>
<?php
layout('سجّل نفسك', (string) ob_get_clean(), bottom_nav(''));
