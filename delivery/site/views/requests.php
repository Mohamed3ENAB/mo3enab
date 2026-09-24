<?php
declare(strict_types=1);

$ok = $bad = null;
$threadToken = null;
$zones = all_zones();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body  = post('body', 500);
    $phone = preg_replace('/\s/', '', post('contact_phone', 20));
    $hide  = !empty($_POST['hide_phone']);
    $zid   = ctype_digit(post('zone_id', 10)) ? (int) post('zone_id', 10) : null;
    $kind  = post('kind', 20);
    $kind  = isset(SERVICES[$kind]) ? $kind : null;

    if (mb_strlen($body) < 5)        $bad = 'اكتب طلبك بتفصيل شوية (٥ حروف على الأقل).';
    elseif (!valid_phone($phone))    $bad = 'اكتب رقم موبايل صح، يبدأ بصفر.';
    else {
        $id = new_id();
        $threadToken = new_id();
        q('INSERT INTO requests (id, zone_id, kind, body, contact_phone, hide_phone, owner_token, expires_at)
           VALUES (?,?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL 6 HOUR))',
          [$id, $zid, $kind, $body, $phone, $hide ? 1 : 0, $threadToken]);
        $ok = 'طلبك اتنشر. السواقين هيشوفوه دلوقتي.';
    }
}

$reqs = open_requests();

ob_start(); ?>
<?= hero('board', 'لوحة الطلبات', 'مش لاقي حد متاح؟', 'اكتب اللي محتاجه، واللي هيفتح هيشوفه ويكلمك.', false) ?>
<main class="wrap page" style="padding-top:22px">
  <?php if ($threadToken): ?>
    <div class="card">
      <?= flash($ok, null) ?>
      <a class="btn call wide" href="<?= url('t/' . e($threadToken)) ?>" style="margin-bottom:12px">
        <?= icon('chat', 19) ?> افتح محادثة طلبك</a>
      <div class="note warn"><?= icon('alert', 19, 'ic') ?>
        <span><strong>احفظ اللينك ده.</strong> ده الطريقة الوحيدة ترجع للمحادثة،
          وأي حد معاه يقدر يقراها ويرد باسمك.</span></div>
    </div>
  <?php else: ?>
    <form class="card" method="post" novalidate>
      <?= flash(null, $bad) ?>
      <label class="field" for="body"><span>محتاج إيه؟</span>
        <textarea id="body" name="body" maxlength="500" required
          placeholder="مثال: محتاج حد يجيبلي دوا من صيدلية في المحلة النهاردة بالليل"><?= e(post('body', 500)) ?></textarea></label>

      <label class="field" for="contact_phone"><span>رقم موبايلك</span>
        <input id="contact_phone" name="contact_phone" type="tel" inputmode="tel" required
          placeholder="01xxxxxxxxx" value="<?= e(post('contact_phone', 20)) ?>"></label>

      <div class="checks" style="margin-bottom:10px">
        <label><input type="checkbox" id="hide_phone" name="hide_phone" value="1" checked> اخفي رقمي</label>
      </div>
      <p class="lede" id="hide-hint" style="font-size:13.5px">
        رقمك هيظهر ناقص كده 0101••••78، والسواقين هيكلموك من جوه التطبيق.</p>

      <label class="field" for="zone_id"><span>القرية</span>
        <select id="zone_id" name="zone_id"><option value="">مش مهم</option>
          <?php foreach ($zones as $z): ?>
            <option value="<?= (int) $z['id'] ?>"><?= e($z['name_ar']) ?></option>
          <?php endforeach; ?></select></label>

      <label class="field" for="kind"><span>نوع الخدمة</span>
        <select id="kind" name="kind"><option value="">مش مهم</option>
          <?php foreach (SERVICES as $k => $v): ?>
            <option value="<?= e($k) ?>"><?= e($v) ?></option>
          <?php endforeach; ?></select></label>

      <button class="btn call wide" type="submit">انشر الطلب</button>
    </form>
  <?php endif; ?>

  <h2>الطلبات المفتوحة</h2>
  <?php if (!$reqs): ?>
    <div class="empty"><h3>مفيش طلبات دلوقتي</h3><p>أول واحد يكتب.</p></div>
  <?php else: foreach ($reqs as $r): ?>
    <div class="req">
      <p><?= e($r['body']) ?></p>
      <div class="when">
        <?= $r['zone_name'] ? icon('pin', 13) . ' ' . e($r['zone_name']) : '' ?>
        <?= $r['kind'] ? '<span class="pill info">' . e(SERVICES[$r['kind']] ?? $r['kind']) . '</span>' : '' ?>
        <?= icon('clock', 13) ?> <?= e(since_ar($r['created_at'])) ?>
        <?= $r['msg_count'] ? '<span class="pill info">' . (int) $r['msg_count'] . ' رسالة</span>' : '' ?>
      </div>
      <?php if ($r['contact_phone']): ?>
        <?= tel_box($r['contact_phone'], 'اتصل بصاحب الطلب') ?>
      <?php else: ?>
        <p class="hidden-note"><?= icon('shield', 14) ?> <?= e($r['masked_phone']) ?> — صاحب الطلب مخفي رقمه</p>
        <p class="lede" style="margin:6px 0 0;font-size:13.5px">
          لو انت سائق، افتح لينكك الخاص وهتلاقي زرار الرد على الطلب ده.</p>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>

  <div class="note" style="margin-top:18px"><?= icon('info', 19, 'ic') ?>
    <span>الطلب بيختفي لوحده بعد <strong>٦ ساعات</strong>. متكتبش بيانات أكتر من اللازم —
      الصفحة دي مفتوحة لأي حد.</span></div>
  <?= site_footer() ?>
</main>
<?php
layout('لوحة الطلبات', (string) ob_get_clean(), bottom_nav('requests'));
