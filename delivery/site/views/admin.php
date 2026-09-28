<?php
declare(strict_types=1);
require_admin();

$ok = $bad = null;
$tab = get('tab', 20) ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $bad = 'الجلسة انتهت. حدّث الصفحة وجرّب تاني.'; }
    else {
        $act = post('action', 30);
        $id  = post('id', 40);
        switch ($act) {

        case 'add_provider': {
            $name = post('display_name', 80);
            $phone = preg_replace('/\s/', '', post('phone', 20));
            $wa = preg_replace('/\s/', '', post('whatsapp', 20));
            $zid = (int) post('zone_id', 10);
            $svc = array_values(array_intersect((array) ($_POST['services'] ?? []), array_keys(SERVICES)));
            if (mb_strlen($name) < 2)      $bad = 'اكتب اسم السائق.';
            elseif (!valid_phone($phone))  $bad = 'رقم الموبايل مش صح.';
            elseif (!$zid)                 $bad = 'اختار القرية.';
            elseif (!$svc)                 $bad = 'اختار خدمة واحدة على الأقل.';
            else {
                $img = store_image('photo');
                if (isset($img['error'])) $bad = $img['error'];
                else {
                    q('INSERT INTO providers (id, display_name, phone, whatsapp, zone_id, services,
                         vehicle_note, note, photo_id, token) VALUES (?,?,?,?,?,?,?,?,?,?)',
                      [new_id(), $name, $phone, $wa ?: null, $zid, implode(',', $svc),
                       post('vehicle_note', 60) ?: null, post('note', 160) ?: null, $img['id'], new_id()]);
                    $ok = 'السائق اتضاف.'; $tab = 'drivers';
                }
            }
            break;
        }
        case 'add_place': {
            $name = post('name_ar', 80);
            $cat  = post('category', 20);
            $zid  = (int) post('zone_id', 10);
            $phone = preg_replace('/\s/', '', post('phone', 20));
            $wa    = preg_replace('/\s/', '', post('whatsapp', 20));
            if (mb_strlen($name) < 2)          $bad = 'اكتب اسم المحل.';
            elseif (!isset(CATEGORIES[$cat]))  $bad = 'اختار التصنيف.';
            elseif (!$zid)                     $bad = 'اختار القرية.';
            elseif (!$phone && !$wa)           $bad = 'لازم رقم تليفون أو واتساب على الأقل.';
            else {
                $img = store_image('photo');
                if (isset($img['error'])) $bad = $img['error'];
                else {
                    q('INSERT INTO places (id, name_ar, category, zone_id, phone, whatsapp,
                         address_note, hours_note, note, photo_id) VALUES (?,?,?,?,?,?,?,?,?,?)',
                      [new_id(), $name, $cat, $zid, $phone ?: null, $wa ?: null,
                       post('address_note', 140) ?: null, post('hours_note', 60) ?: null,
                       post('note', 160) ?: null, $img['id']]);
                    $ok = 'المحل اتضاف.'; $tab = 'places';
                }
            }
            break;
        }
        case 'verify':   q('UPDATE providers SET is_verified = 1 - is_verified,
                              verified_on = IF(is_verified = 1, CURDATE(), NULL) WHERE id = ?', [$id]);
                         $ok = 'اتحدّث.'; $tab = 'drivers'; break;
        case 'suspend':  q('UPDATE providers SET is_active = 1 - is_active,
                              is_available = IF(is_active = 1, is_available, 0) WHERE id = ?', [$id]);
                         $ok = 'اتحدّث.'; $tab = 'drivers'; break;
        case 'place_toggle': q('UPDATE places SET is_active = 1 - is_active WHERE id = ?', [$id]);
                         $ok = 'اتحدّث.'; $tab = 'places'; break;
        case 'hide_request': q('UPDATE requests SET is_hidden = 1 - is_hidden WHERE id = ?', [$id]);
                         $ok = 'اتحدّث.'; $tab = 'requests'; break;
        case 'handle_report': q('UPDATE reports SET handled_at = NOW() WHERE id = ?', [$id]);
                         $ok = 'اتعلّم.'; $tab = 'reports'; break;

        case 'approve': {
            $a = row("SELECT * FROM applications WHERE id = ? AND status = 'pending'", [$id]);
            if (!$a) { $bad = 'الطلب مش موجود أو اتراجع خلاص.'; break; }
            if ($a['kind'] === 'driver') {
                q('INSERT INTO providers (id, display_name, phone, whatsapp, zone_id, services,
                     vehicle_note, note, photo_id, token) VALUES (?,?,?,?,?,?,?,?,?,?)',
                  [new_id(), $a['name'], $a['phone'], $a['whatsapp'], (int) $a['zone_id'],
                   (string) $a['services'], $a['vehicle_note'], $a['note'], $a['photo_id'], new_id()]);
            } else {
                q('INSERT INTO places (id, name_ar, category, zone_id, phone, whatsapp,
                     address_note, hours_note, note, photo_id) VALUES (?,?,?,?,?,?,?,?,?,?)',
                  [new_id(), $a['name'], (string) $a['category'], (int) $a['zone_id'],
                   $a['phone'], $a['whatsapp'], $a['address_note'], $a['hours_note'],
                   $a['note'], $a['photo_id']]);
            }
            q("UPDATE applications SET status = 'approved', reviewed_at = NOW() WHERE id = ?", [$id]);
            $ok = $a['name'] . ' اتفعّل في الدليل.'; $tab = 'apps';
            break;
        }
        case 'reject':
            q("UPDATE applications SET status = 'rejected', reviewed_at = NOW(), reject_reason = ?
                WHERE id = ? AND status = 'pending'", [post('reason', 200) ?: null, $id]);
            $ok = 'الطلب اترفض.'; $tab = 'apps'; break;
        }
    }
}

$s     = admin_stats();
$tab ??= $s['pending'] > 0 ? 'apps' : 'drivers';
$zones = all_zones();

$apps = rows("SELECT a.*, z.name_ar AS zone_name FROM applications a
               JOIN zones z ON z.id = a.zone_id
              ORDER BY (a.status = 'pending') DESC, a.created_at DESC LIMIT 60");
$drivers = rows("SELECT p.*, z.name_ar AS zone_name,
                   (SELECT COUNT(*) FROM reports r WHERE r.provider_id = p.id AND r.handled_at IS NULL) AS open_reports
                 FROM providers p JOIN zones z ON z.id = p.zone_id
                 ORDER BY p.is_active DESC, p.display_name");
$places = rows("SELECT pl.*, z.name_ar AS zone_name FROM places pl JOIN zones z ON z.id = pl.zone_id
                 ORDER BY pl.is_active DESC, pl.name_ar");
$reqs = rows('SELECT * FROM requests ORDER BY created_at DESC LIMIT 50');
$reports = rows("SELECT r.*, COALESCE(p.display_name, pl.name_ar) AS target
                   FROM reports r
                   LEFT JOIN providers p ON p.id = r.provider_id
                   LEFT JOIN places pl ON pl.id = r.place_id
                  ORDER BY r.handled_at IS NOT NULL, r.created_at DESC LIMIT 100");

$T = fn(string $k, string $label, int $n = 0) =>
    '<a class="zchip" href="' . url('admin?tab=' . $k) . '" aria-pressed="' . ($tab === $k ? 'true' : 'false') . '">'
    . e($label) . ($n ? ' (' . $n . ')' : '') . '</a>';

ob_start(); ?>
<main class="wrap page">
  <h1>لوحة الإدارة</h1>
  <div class="stats">
    <div class="stat<?= $s['pending'] ? ' alert' : '' ?>"><b><?= $s['pending'] ?></b><span>طلب مستني</span></div>
    <div class="stat good"><b><?= $s['live'] ?></b><span>سائق متاح</span></div>
    <div class="stat"><b><?= $s['drivers'] ?></b><span>إجمالي السواقين</span></div>
    <div class="stat"><b><?= $s['places'] ?></b><span>محل</span></div>
    <div class="stat<?= $s['reports'] ? ' alert' : '' ?>"><b><?= $s['reports'] ?></b><span>بلاغ مفتوح</span></div>
    <div class="stat"><b><?= $s['requests'] ?></b><span>طلب على اللوحة</span></div>
  </div>

  <div class="tabs">
    <?= $T('apps', 'طلبات الانضمام', $s['pending']) ?>
    <?= $T('drivers', 'السواقين', count($drivers)) ?>
    <?= $T('add_driver', 'إضافة سائق') ?>
    <?= $T('places', 'المحلات', count($places)) ?>
    <?= $T('add_place', 'إضافة محل') ?>
    <?= $T('reports', 'البلاغات', $s['reports']) ?>
    <?= $T('requests', 'الطلبات') ?>
  </div>

  <?= flash($ok, $bad) ?>

<?php if ($tab === 'apps'): ?>
  <?php if (!$apps): ?><p class="lede">مفيش طلبات انضمام.</p><?php endif; ?>
  <?php foreach ($apps as $a): $svc = array_filter(explode(',', (string) $a['services'])); ?>
    <div class="admin-row">
      <div style="display:flex;gap:12px;align-items:flex-start">
        <?= avatar($a['name'], $a['photo_id'], false, 46) ?>
        <div style="flex:1;min-width:0">
          <div class="who"><?= e($a['name']) ?>
            <span class="pill info"><?= $a['kind'] === 'driver' ? 'سائق' : 'محل' ?></span>
            <?php if ($a['status'] === 'pending'): ?><span class="pill warn">مستني</span>
            <?php elseif ($a['status'] === 'approved'): ?><span class="pill on">اتقبل</span>
            <?php else: ?><span class="pill">اترفض</span><?php endif; ?></div>
          <div class="sub"><?= e(implode(' · ', array_filter([
              $a['phone'],
              $a['whatsapp'] && $a['whatsapp'] !== $a['phone'] ? 'واتساب ' . $a['whatsapp'] : null,
              $a['zone_name'],
              $a['kind'] === 'driver' ? $a['vehicle_note'] : (CATEGORIES[$a['category']] ?? null),
              $a['kind'] === 'driver'
                ? implode('، ', array_map(fn($x) => SERVICES[$x] ?? $x, $svc))
                : $a['address_note'],
              $a['hours_note'], since_ar($a['created_at']),
            ]))) ?></div>
          <?php if ($a['note']): ?><div class="sub">«<?= e($a['note']) ?>»</div><?php endif; ?>
        </div>
      </div>
      <?php if ($a['status'] === 'pending'): ?>
        <div class="actions">
          <form method="post" style="flex:1"><?= csrf_field() ?>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="id" value="<?= e($a['id']) ?>">
            <button class="btn call" style="width:100%"><?= icon('check', 15) ?> اقبل وفعّل</button></form>
          <form method="post"><?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="id" value="<?= e($a['id']) ?>">
            <button class="btn quiet danger">ارفض</button></form>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'drivers'): ?>
  <?php foreach ($drivers as $d): $svc = array_filter(explode(',', (string) $d['services'])); ?>
    <div class="admin-row">
      <div style="display:flex;gap:11px;align-items:flex-start">
        <?= avatar($d['display_name'], $d['photo_id'], false, 40) ?>
        <div style="flex:1;min-width:0">
          <div class="who"><?= e($d['display_name']) ?>
            <?= $d['is_verified'] ? '<span class="pill on">موثّق</span>' : '' ?>
            <?= !$d['is_active'] ? '<span class="pill warn">موقوف</span>' : '' ?>
            <?= $d['open_reports'] ? '<span class="pill warn">' . (int) $d['open_reports'] . ' بلاغ</span>' : '' ?></div>
          <div class="sub"><?= e($d['phone']) ?> · <?= e($d['zone_name']) ?> ·
            <?= e(implode('، ', array_map(fn($x) => SERVICES[$x] ?? $x, $svc))) ?> ·
            <?= $d['available_at'] ? (is_live($d) ? 'فاتح ' : 'قافل ') . e(since_ar($d['available_at'])) : 'لسه مافتحش' ?></div>
          <details><summary class="sub" style="cursor:pointer;margin-top:6px">اللينك الخاص</summary>
            <div class="tokenbox"><?= e(rtrim((string)(($_SERVER['HTTP_HOST'] ?? '') ? 'https://' . $_SERVER['HTTP_HOST'] : ''), '/')) ?><?= e(url('d/' . $d['token'])) ?></div>
          </details>
        </div>
      </div>
      <div class="actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="verify">
          <input type="hidden" name="id" value="<?= e($d['id']) ?>">
          <button class="btn quiet"><?= $d['is_verified'] ? 'شيل التوثيق' : 'وثّق' ?></button></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="suspend">
          <input type="hidden" name="id" value="<?= e($d['id']) ?>">
          <button class="btn quiet<?= $d['is_active'] ? ' danger' : '' ?>"><?= $d['is_active'] ? 'أوقف' : 'رجّع' ?></button></form>
      </div>
    </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'add_driver'): ?>
  <form class="card" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
    <input type="hidden" name="action" value="add_provider">
    <div class="note warn"><?= icon('alert', 19, 'ic') ?>
      <span>متضيفش حد قبل ما تشوف بطاقته ورخصته، وتاخد منه موافقة مكتوبة على نشر رقمه.</span></div>
    <?= photo_field('صورة السائق', 'اختياري') ?>
    <label class="field" for="dn"><span>الاسم</span><input id="dn" name="display_name" required></label>
    <label class="field" for="dp"><span>الموبايل</span>
      <input id="dp" name="phone" type="tel" inputmode="tel" required placeholder="01xxxxxxxxx"></label>
    <label class="field" for="dw"><span>واتساب (لو مختلف)</span>
      <input id="dw" name="whatsapp" type="tel" inputmode="tel"></label>
    <label class="field" for="dz"><span>القرية</span>
      <select id="dz" name="zone_id" required><option value="" disabled selected>اختار</option>
        <?php foreach ($zones as $z): ?><option value="<?= (int) $z['id'] ?>"><?= e($z['name_ar']) ?></option><?php endforeach; ?>
      </select></label>
    <div class="field"><span>الخدمات</span><div class="checks">
      <?php foreach (SERVICES as $k => $v): ?>
        <label><input type="checkbox" name="services[]" value="<?= e($k) ?>"> <?= e($v) ?></label>
      <?php endforeach; ?></div></div>
    <label class="field" for="dv"><span>المركبة</span>
      <input id="dv" name="vehicle_note" placeholder="موتوسيكل / توك توك / عجلة"></label>
    <label class="field" for="dnote"><span>ملاحظة تظهر للناس</span>
      <input id="dnote" name="note" placeholder="بيشتغل من 2 لـ 10"></label>
    <button class="btn call wide" type="submit">ضيف السائق</button>
  </form>

<?php elseif ($tab === 'places'): ?>
  <?php if (!$places): ?><p class="lede">مفيش محلات لسه.</p><?php endif; ?>
  <?php foreach ($places as $pl): ?>
    <div class="admin-row">
      <div style="display:flex;gap:11px;align-items:flex-start">
        <?= avatar($pl['name_ar'], $pl['photo_id'], false, 40) ?>
        <div style="flex:1;min-width:0">
          <div class="who"><?= e($pl['name_ar']) ?>
            <span class="pill info"><?= e(CATEGORIES[$pl['category']] ?? $pl['category']) ?></span>
            <?= !$pl['is_active'] ? '<span class="pill warn">مخفي</span>' : '' ?></div>
          <div class="sub"><?= e(implode(' · ', array_filter([$pl['phone'], $pl['zone_name'], $pl['hours_note']]))) ?></div>
        </div>
      </div>
      <div class="actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="place_toggle">
          <input type="hidden" name="id" value="<?= e($pl['id']) ?>">
          <button class="btn quiet<?= $pl['is_active'] ? ' danger' : '' ?>"><?= $pl['is_active'] ? 'إخفاء' : 'رجّعه' ?></button></form>
      </div>
    </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'add_place'): ?>
  <form class="card" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
    <input type="hidden" name="action" value="add_place">
    <div class="note"><?= icon('info', 19, 'ic') ?>
      <span>رقم المحل رقم تجاري معلن عادةً، بس برضه استأذن صاحبه قبل ما تنشره.</span></div>
    <?= photo_field('صورة المحل', 'اختياري') ?>
    <label class="field" for="pn"><span>اسم المحل</span><input id="pn" name="name_ar" required></label>
    <label class="field" for="pc"><span>التصنيف</span>
      <select id="pc" name="category" required><option value="" disabled selected>اختار</option>
        <?php foreach (CATEGORIES as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
      </select></label>
    <label class="field" for="pz"><span>القرية</span>
      <select id="pz" name="zone_id" required><option value="" disabled selected>اختار</option>
        <?php foreach ($zones as $z): ?><option value="<?= (int) $z['id'] ?>"><?= e($z['name_ar']) ?></option><?php endforeach; ?>
      </select></label>
    <label class="field" for="pp"><span>التليفون</span><input id="pp" name="phone" type="tel" inputmode="tel"></label>
    <label class="field" for="pw"><span>واتساب</span><input id="pw" name="whatsapp" type="tel" inputmode="tel"></label>
    <label class="field" for="pa"><span>مكانه</span><input id="pa" name="address_note" placeholder="جنب الجامع الكبير"></label>
    <label class="field" for="ph"><span>المواعيد</span><input id="ph" name="hours_note" placeholder="من 10ص لـ 12 بالليل"></label>
    <label class="field" for="pnote"><span>ملاحظة</span><input id="pnote" name="note"></label>
    <button class="btn call wide" type="submit">ضيف المحل</button>
  </form>

<?php elseif ($tab === 'reports'): ?>
  <?php if (!$reports): ?><p class="lede">مفيش بلاغات.</p><?php endif; ?>
  <?php foreach ($reports as $r): ?>
    <div class="admin-row"><div>
      <div class="who"><?= e((string) $r['target']) ?> — <?= e(REPORT_REASONS[$r['reason']] ?? $r['reason']) ?>
        <?= $r['handled_at'] ? '<span class="pill on">اتعامل معاه</span>' : '' ?></div>
      <div class="sub"><?= e(implode(' · ', array_filter([
          $r['details'], $r['reporter_phone'] ?: 'من غير رقم', since_ar($r['created_at'])]))) ?></div>
    </div>
    <?php if (!$r['handled_at']): ?>
      <div class="actions"><form method="post"><?= csrf_field() ?>
        <input type="hidden" name="action" value="handle_report">
        <input type="hidden" name="id" value="<?= e($r['id']) ?>">
        <button class="btn quiet">علّم كمتعامَل معاه</button></form></div>
    <?php endif; ?></div>
  <?php endforeach; ?>

<?php else: ?>
  <?php if (!$reqs): ?><p class="lede">مفيش طلبات.</p><?php endif; ?>
  <?php foreach ($reqs as $r): ?>
    <div class="admin-row"><div>
      <div class="who"><?= e(mb_substr($r['body'], 0, 80)) ?><?= mb_strlen($r['body']) > 80 ? '…' : '' ?>
        <?= $r['is_hidden'] ? '<span class="pill warn">مخفي</span>' : '' ?>
        <?= $r['hide_phone'] ? '<span class="pill info">الرقم مخفي</span>' : '' ?></div>
      <div class="sub"><?= e($r['contact_phone']) ?> · <?= e(since_ar($r['created_at'])) ?></div>
    </div>
    <div class="actions"><form method="post"><?= csrf_field() ?>
      <input type="hidden" name="action" value="hide_request">
      <input type="hidden" name="id" value="<?= e($r['id']) ?>">
      <button class="btn quiet"><?= $r['is_hidden'] ? 'رجّعه' : 'إخفاء' ?></button></form></div></div>
  <?php endforeach; ?>
<?php endif; ?>

  <a class="btn ghost wide" href="<?= url('admin/logout') ?>" style="margin-top:36px">خروج</a>
</main>
<?php
layout('لوحة الإدارة', (string) ob_get_clean(), bottom_nav(''));
