<?php
declare(strict_types=1);

$me = require_role('admin');
$section = $section ?? 'overview';
$msg = null; $err = null;

/** بتفعّل الحساب وتفتح ملفه في الدليل. */
function admin_approve(string $userId): string {
    $u = row('SELECT * FROM users WHERE id = ?', [$userId]);
    if (!$u) return 'الحساب مش موجود.';
    q("UPDATE users SET status = 'active', reject_reason = NULL WHERE id = ?", [$userId]);
    q("UPDATE user_docs SET status = 'approved' WHERE user_id = ?", [$userId]);
    if ($u['provider_id']) {
        q('UPDATE providers SET is_active = 1, is_verified = 1, verified_on = CURDATE() WHERE id = ?', [$u['provider_id']]);
    }
    if ($u['place_id']) {
        q('UPDATE places SET is_active = 1 WHERE id = ?', [$u['place_id']]);
    }
    notify($userId, 'مبروك، اتوافق على حسابك 🎉',
           'دخّل على الداش بورد بتاعتك وابدأ.', dash_url($u['role']), 'sparkle');
    return '';
}

if (is_post()) {
    $id = post('id', 32);
    switch (post('do')) {
        case 'approve':
            $e = admin_approve($id);
            if ($e) $err = $e; else $msg = 'اتوافق على الحساب.';
            break;

        case 'reject':
            $reason = post('reason', 200) ?: 'البيانات أو الصور مش واضحة.';
            q("UPDATE users SET status = 'rejected', reject_reason = ? WHERE id = ?", [$reason, $id]);
            q("UPDATE user_docs SET status = 'rejected' WHERE user_id = ?", [$id]);
            notify($id, 'طلبك اترفض', $reason . ' — تقدر تغيّر الصور وتبعت تاني.', url('dashboard/pending'), 'alert');
            $msg = 'اترفض، وصاحبه وصله السبب.';
            break;

        case 'suspend':
            q("UPDATE users SET status = 'suspended' WHERE id = ? AND role <> 'admin'", [$id]);
            $u = row('SELECT provider_id, place_id FROM users WHERE id = ?', [$id]);
            if ($u['provider_id'] ?? null) q('UPDATE providers SET is_active = 0, is_available = 0 WHERE id = ?', [$u['provider_id']]);
            if ($u['place_id'] ?? null)    q('UPDATE places SET is_active = 0 WHERE id = ?', [$u['place_id']]);
            $msg = 'الحساب اتوقف.';
            break;

        case 'unsuspend':
            $e = admin_approve($id);
            $msg = $e ?: 'الحساب رجع يشتغل.';
            break;

        case 'reset_pass':
            $new = 'sekka' . random_int(1000, 9999);
            q('UPDATE users SET pass_hash = ?, fail_count = 0, locked_until = NULL WHERE id = ?',
              [hash_password($new), $id]);
            $msg = 'كلمة السر المؤقتة: ' . $new . ' — بلّغها لصاحب الحساب وخليه يغيّرها.';
            break;

        case 'verify':
            q('UPDATE providers SET is_verified = ?, verified_on = CURDATE() WHERE id = ?',
              [post('v', 3) === '1' ? 1 : 0, $id]);
            $msg = 'اتحدّث التوثيق.';
            break;

        case 'prov_active':
            q('UPDATE providers SET is_active = ?, is_available = 0 WHERE id = ?', [post('v', 3) === '1' ? 1 : 0, $id]);
            $msg = 'اتحدّثت حالة السائق.';
            break;

        case 'place_active':
            q('UPDATE places SET is_active = ? WHERE id = ?', [post('v', 3) === '1' ? 1 : 0, $id]);
            $msg = 'اتحدّثت حالة المحل.';
            break;

        case 'req_hide':
            q('UPDATE requests SET is_hidden = ? WHERE id = ?', [post('v', 3) === '1' ? 1 : 0, $id]);
            $msg = 'اتحدّث الطلب.';
            break;

        case 'rating_hide':
            q('UPDATE ratings SET is_hidden = 1 WHERE id = ?', [$id]);
            $msg = 'التقييم اتخفى.';
            break;

        case 'report_done':
            q('UPDATE reports SET handled_at = NOW() WHERE id = ?', [$id]);
            $msg = 'البلاغ اتقفل.';
            break;

        case 'zone_add':
            $n = post('name_ar', 80);
            if (mb_strlen($n) < 2) { $err = 'اكتب اسم المنطقة.'; break; }
            try {
                q('INSERT INTO zones (name_ar, sort_order) VALUES (?, ?)', [$n, (int) post('sort_order', 4)]);
                $msg = 'المنطقة اتضافت.';
            } catch (Throwable) { $err = 'المنطقة دي موجودة.'; }
            break;

        case 'zone_toggle':
            q('UPDATE zones SET is_active = ? WHERE id = ?', [post('v', 3) === '1' ? 1 : 0, (int) $id]);
            $msg = 'اتحدّثت المنطقة.';
            break;

        case 'rate_save':
            $kind = post('kind', 20);
            if (!isset(SERVICES[$kind])) { $err = 'نوع غلط.'; break; }
            q('INSERT INTO vehicle_rates (kind, starts_from, note_ar, sort_order)
               VALUES (?,?,?,?)
               ON DUPLICATE KEY UPDATE starts_from = VALUES(starts_from), note_ar = VALUES(note_ar)',
              [$kind, (float) post('starts_from', 10), post('note_ar', 120) ?: null, (int) post('sort_order', 4)]);
            $msg = 'السعر اتحفظ.';
            break;

        case 'guide_add':
            q('INSERT INTO price_guide (from_zone_id, to_zone_id, kind, typical_min, typical_max, note_ar)
               VALUES (?,?,?,?,?,?)',
              [(int) post('from_zone_id', 6) ?: null, (int) post('to_zone_id', 6) ?: null,
               post('kind', 20) ?: null, (float) post('typical_min', 10), (float) post('typical_max', 10),
               post('note_ar', 140) ?: null]);
            $msg = 'الخط اتضاف.';
            break;

        case 'guide_del':
            q('DELETE FROM price_guide WHERE id = ?', [(int) $id]);
            $msg = 'الخط اتمسح.';
            break;

        case 'read_notifs':
            q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$me['id']]);
            break;
    }
}

$st = admin_stats();

$sections = [
    'overview'  => ['نظرة عامة', 'dash'],
    'approvals' => ['طلبات الانضمام', 'userplus'],
    'drivers'   => ['السواقين', 'tuktuk'],
    'places'    => ['المحلات', 'store'],
    'users'     => ['الحسابات', 'users'],
    'requests'  => ['الطلبات', 'board'],
    'reports'   => ['البلاغات', 'alert'],
    'settings'  => ['المناطق والأسعار', 'settings'],
];
if (!isset($sections[$section])) $section = 'overview';

ob_start(); ?>
<main class="wrap page dash admin" id="main">

  <header class="dash-hero card glass admin-hero">
    <div class="dh-user">
      <?= avatar($me['name'], $me['avatar_id'], true, 56) ?>
      <div>
        <span class="muted small">لوحة الإدارة</span>
        <h1><?= e($me['name']) ?></h1>
      </div>
    </div>
    <div class="dh-acts">
      <a class="btn ghost sm" href="<?= url('') ?>"><?= icon('home', 15) ?> الموقع</a>
      <a class="btn ghost sm" href="<?= url('account') ?>"><?= icon('settings', 15) ?></a>
      <a class="btn ghost sm" href="<?= url('logout') ?>"><?= icon('logout', 15) ?></a>
    </div>
  </header>

  <nav class="adminnav">
    <?php foreach ($sections as $key => [$label, $ic]): ?>
      <a class="anav <?= $key === $section ? 'on' : '' ?>" href="<?= url('admin/' . $key) ?>">
        <?= icon($ic, 17) ?> <?= e($label) ?>
        <?php if ($key === 'approvals' && $st['pending']): ?><i class="cnt"><?= (int) $st['pending'] ?></i><?php endif; ?>
        <?php if ($key === 'reports' && $st['reports']): ?><i class="cnt"><?= (int) $st['reports'] ?></i><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <?= flash($msg, $err) ?>

<?php if ($section === 'overview'): ?>
  <div class="stats">
    <?= stat_card('سواقين', $st['drivers'], 'tuktuk', 'a') ?>
    <?= stat_card('متاح دلوقتي', $st['live'], 'bolt', 'b') ?>
    <?= stat_card('محلات', $st['places'], 'store', 'c') ?>
    <?= stat_card('طلبات مفتوحة', $st['requests'], 'board', 'd') ?>
    <?= stat_card('حسابات', $st['users'], 'users', 'a') ?>
    <?= stat_card('عملاء', $st['customers'], 'user', 'b') ?>
    <?= stat_card('تحت المراجعة', $st['pending'], 'clock', 'c') ?>
    <?= stat_card('بلاغات', $st['reports'], 'alert', 'd') ?>
  </div>

  <?php if ($st['pending']): ?>
    <section class="card accent-a">
      <h2><?= icon('userplus', 18) ?> فيه <?= (int) $st['pending'] ?> طلب مستني مراجعة</h2>
      <p class="muted">الناس دي مستنية ردّك عشان تشتغل.</p>
      <a class="btn call" href="<?= url('admin/approvals') ?>">راجعهم دلوقتي</a>
    </section>
  <?php endif; ?>

  <section class="card">
    <h2><?= icon('bell', 18) ?> آخر الإشعارات</h2>
    <?= notifications_block(notifications_for($me['id'], 12)) ?>
  </section>

<?php elseif ($section === 'approvals'): ?>
  <?php $pend = pending_users(); ?>
  <section class="card">
    <h2><?= icon('userplus', 18) ?> طلبات الانضمام (<?= count($pend) ?>)</h2>
    <p class="muted small">افتح صور البطاقة والرخصة وتأكد إن الاسم والصورة متطابقين قبل الموافقة.</p>

    <?php if (!$pend): ?>
      <?= empty_state('مفيش طلبات مستنية', 'كل الطلبات اتراجعت. 👌', 'check') ?>
    <?php else: ?>
      <ul class="applist">
        <?php foreach ($pend as $a):
          $docs = docs_of($a['id']);
          $extra = $a['provider_id'] ? provider_of_user($a['id']) : ($a['place_id'] ? place_of_user($a['id']) : null); ?>
          <li class="app card soft">
            <div class="app-head">
              <?= avatar($a['name'], $a['avatar_id'], false, 58) ?>
              <div class="app-id">
                <h3><?= e($a['name']) ?> <?= badge(ROLE_NAMES[$a['role']] ?? '', 'info') ?></h3>
                <p class="muted"><?= icon('phone', 12) ?> <?= e($a['phone']) ?>
                  <?= $a['zone_name'] ? ' · ' . icon('pin', 12) . ' ' . e($a['zone_name']) : '' ?>
                  · <?= e(since_ar($a['created_at'])) ?></p>
                <?php if ($extra): ?>
                  <p class="muted small">
                    <?php if ($a['provider_id']): ?>
                      <?= e((string) $extra['vehicle_note']) ?>
                      <?php foreach (services_of($extra) as $s): ?>
                        <span class="chip tiny <?= bubble_class($s) ?>"><?= e(SERVICES[$s] ?? $s) ?></span>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <?= e($extra['name_ar']) ?> · <?= e(CATEGORIES[$extra['category']] ?? '') ?>
                      <?= $extra['address_note'] ? ' · ' . e($extra['address_note']) : '' ?>
                    <?php endif; ?>
                  </p>
                <?php endif; ?>
              </div>
            </div>

            <div class="app-docs">
              <?php if (!$docs): ?>
                <p class="msg bad"><?= icon('alert', 15) ?> مرفعش أي مستندات.</p>
              <?php else: foreach ($docs as $d): ?>
                <a class="docthumb" href="<?= url('img.php?id=' . e($d['image_id'])) ?>" target="_blank" rel="noopener">
                  <img src="<?= url('img.php?id=' . e($d['image_id'])) ?>" alt="" loading="lazy">
                  <span><?= e(DOC_KINDS[$d['kind']] ?? $d['kind']) ?></span>
                </a>
              <?php endforeach; endif; ?>
            </div>

            <div class="app-acts">
              <form method="post" class="inline"><?= csrf_field() ?>
                <input type="hidden" name="do" value="approve"><input type="hidden" name="id" value="<?= e($a['id']) ?>">
                <button class="btn call" data-sfx="ok"><?= icon('check', 16) ?> وافق</button>
              </form>
              <form method="post" class="inline grow"><?= csrf_field() ?>
                <input type="hidden" name="do" value="reject"><input type="hidden" name="id" value="<?= e($a['id']) ?>">
                <input type="text" name="reason" maxlength="200" placeholder="سبب الرفض (هيوصله)">
                <button class="btn ghost danger"><?= icon('close', 16) ?> ارفض</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

<?php elseif ($section === 'drivers'): ?>
  <?php $list = rows("SELECT p.*, z.name_ar AS zone_name, u.status AS user_status
                        FROM providers p JOIN zones z ON z.id = p.zone_id
                        LEFT JOIN users u ON u.id = p.user_id
                       ORDER BY p.is_active DESC, p.display_name"); ?>
  <section class="card">
    <h2><?= icon('tuktuk', 18) ?> السواقين (<?= count($list) ?>)</h2>
    <input type="search" class="tablefilter" placeholder="دوّر باسم أو رقم" data-filter=".adminrow">
    <ul class="adminlist">
      <?php foreach ($list as $p): $live = is_live($p); ?>
        <li class="adminrow" data-text="<?= e($p['display_name'] . ' ' . $p['phone']) ?>">
          <?= avatar($p['display_name'], $p['photo_id'], (bool) $p['is_verified'], 44) ?>
          <div class="ar-main">
            <b><?= e($p['display_name']) ?></b>
            <span class="muted small"><?= e($p['phone']) ?> · <?= e($p['zone_name']) ?>
              <?= $live ? ' · متاح' : '' ?></span>
          </div>
          <?= $p['is_active'] ? badge('ظاهر', 'good') : badge('مخفي', 'muted') ?>
          <?= $p['is_verified'] ? badge('موثّق', 'info') : '' ?>
          <div class="ar-acts">
            <a class="btn ghost sm" href="<?= url('p/' . e($p['id'])) ?>"><?= icon('eye', 14) ?></a>
            <form method="post" class="inline"><?= csrf_field() ?>
              <input type="hidden" name="do" value="verify"><input type="hidden" name="id" value="<?= e($p['id']) ?>">
              <input type="hidden" name="v" value="<?= $p['is_verified'] ? '0' : '1' ?>">
              <button class="btn ghost sm"><?= icon('shield', 14) ?> <?= $p['is_verified'] ? 'شيل التوثيق' : 'وثّق' ?></button>
            </form>
            <form method="post" class="inline"><?= csrf_field() ?>
              <input type="hidden" name="do" value="prov_active"><input type="hidden" name="id" value="<?= e($p['id']) ?>">
              <input type="hidden" name="v" value="<?= $p['is_active'] ? '0' : '1' ?>">
              <button class="btn ghost sm <?= $p['is_active'] ? 'danger' : '' ?>"><?= $p['is_active'] ? 'اخفي' : 'ظهّر' ?></button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

<?php elseif ($section === 'places'): ?>
  <?php $list = rows("SELECT pl.*, z.name_ar AS zone_name FROM places pl
                        JOIN zones z ON z.id = pl.zone_id
                       ORDER BY pl.is_active DESC, pl.name_ar"); ?>
  <section class="card">
    <h2><?= icon('store', 18) ?> المحلات (<?= count($list) ?>)</h2>
    <input type="search" class="tablefilter" placeholder="دوّر باسم المحل" data-filter=".adminrow">
    <ul class="adminlist">
      <?php foreach ($list as $pl): ?>
        <li class="adminrow" data-text="<?= e($pl['name_ar'] . ' ' . (string) $pl['phone']) ?>">
          <?= avatar($pl['name_ar'], $pl['photo_id'], false, 44) ?>
          <div class="ar-main">
            <b><?= e($pl['name_ar']) ?></b>
            <span class="muted small"><?= e(CATEGORIES[$pl['category']] ?? '') ?> · <?= e($pl['zone_name']) ?>
              <?= $pl['phone'] ? ' · ' . e($pl['phone']) : '' ?></span>
          </div>
          <?= (int) $pl['is_open'] ? badge('فاتح', 'good') : badge('قافل', 'muted') ?>
          <?= $pl['is_active'] ? badge('ظاهر', 'info') : badge('مخفي', 'muted') ?>
          <div class="ar-acts">
            <a class="btn ghost sm" href="<?= url('m/' . e($pl['id'])) ?>"><?= icon('eye', 14) ?></a>
            <form method="post" class="inline"><?= csrf_field() ?>
              <input type="hidden" name="do" value="place_active"><input type="hidden" name="id" value="<?= e($pl['id']) ?>">
              <input type="hidden" name="v" value="<?= $pl['is_active'] ? '0' : '1' ?>">
              <button class="btn ghost sm <?= $pl['is_active'] ? 'danger' : '' ?>"><?= $pl['is_active'] ? 'اخفي' : 'ظهّر' ?></button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

<?php elseif ($section === 'users'): ?>
  <?php $list = rows("SELECT u.*, z.name_ar AS zone_name FROM users u
                        LEFT JOIN zones z ON z.id = u.zone_id
                       ORDER BY FIELD(u.status,'pending','active','rejected','suspended'), u.created_at DESC
                       LIMIT 300"); ?>
  <section class="card">
    <h2><?= icon('users', 18) ?> الحسابات (<?= count($list) ?>)</h2>
    <input type="search" class="tablefilter" placeholder="دوّر باسم أو رقم" data-filter=".adminrow">
    <ul class="adminlist">
      <?php foreach ($list as $x): ?>
        <li class="adminrow" data-text="<?= e($x['name'] . ' ' . $x['phone']) ?>">
          <?= avatar($x['name'], $x['avatar_id'], $x['role'] === 'admin', 44) ?>
          <div class="ar-main">
            <b><?= e($x['name']) ?></b>
            <span class="muted small"><?= e(ROLE_NAMES[$x['role']] ?? $x['role']) ?> · <?= e($x['phone']) ?>
              <?= $x['last_login_at'] ? ' · آخر دخول ' . e(since_ar($x['last_login_at'])) : '' ?></span>
          </div>
          <?= status_badge($x['status']) ?>
          <?php if ($x['role'] !== 'admin'): ?>
          <div class="ar-acts">
            <form method="post" class="inline" data-confirm="تعمل كلمة سر مؤقتة؟"><?= csrf_field() ?>
              <input type="hidden" name="do" value="reset_pass"><input type="hidden" name="id" value="<?= e($x['id']) ?>">
              <button class="btn ghost sm"><?= icon('lock', 14) ?></button>
            </form>
            <?php if ($x['status'] === 'suspended'): ?>
              <form method="post" class="inline"><?= csrf_field() ?>
                <input type="hidden" name="do" value="unsuspend"><input type="hidden" name="id" value="<?= e($x['id']) ?>">
                <button class="btn ghost sm"><?= icon('check', 14) ?> رجّعه</button>
              </form>
            <?php else: ?>
              <form method="post" class="inline" data-confirm="توقف الحساب دا؟"><?= csrf_field() ?>
                <input type="hidden" name="do" value="suspend"><input type="hidden" name="id" value="<?= e($x['id']) ?>">
                <button class="btn ghost sm danger"><?= icon('close', 14) ?> وقف</button>
              </form>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

<?php elseif ($section === 'requests'): ?>
  <?php $list = rows("SELECT r.*, z.name_ar AS zone_name, u.name AS owner_name,
                             (SELECT COUNT(*) FROM messages m WHERE m.request_id = r.id) AS msg_count
                        FROM requests r LEFT JOIN zones z ON z.id = r.zone_id
                        LEFT JOIN users u ON u.id = r.user_id
                       ORDER BY r.created_at DESC LIMIT 120"); ?>
  <section class="card">
    <h2><?= icon('board', 18) ?> الطلبات</h2>
    <ul class="adminlist">
      <?php foreach ($list as $r): ?>
        <li class="adminrow col">
          <div class="ar-line">
            <span class="chip tiny <?= bubble_class((string) $r['kind']) ?>"><?= e(SERVICES[$r['kind']] ?? 'طلب') ?></span>
            <?= status_badge($r['status']) ?>
            <span class="muted small"><?= e($r['owner_name'] ?: 'زائر') ?> · <?= e(since_ar($r['created_at'])) ?>
              · <?= (int) $r['msg_count'] ?> رد</span>
            <form method="post" class="inline mr-auto"><?= csrf_field() ?>
              <input type="hidden" name="do" value="req_hide"><input type="hidden" name="id" value="<?= e($r['id']) ?>">
              <input type="hidden" name="v" value="<?= $r['is_hidden'] ? '0' : '1' ?>">
              <button class="btn ghost sm <?= $r['is_hidden'] ? '' : 'danger' ?>">
                <?= icon($r['is_hidden'] ? 'eye' : 'eyeoff', 14) ?> <?= $r['is_hidden'] ? 'رجّعه' : 'اخفي' ?>
              </button>
            </form>
          </div>
          <p class="ar-body"><?= e(mb_substr($r['body'], 0, 180)) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

<?php elseif ($section === 'reports'): ?>
  <?php $list = rows("SELECT rp.*, p.display_name AS prov_name, pl.name_ar AS place_name
                        FROM reports rp
                        LEFT JOIN providers p ON p.id = rp.provider_id
                        LEFT JOIN places pl ON pl.id = rp.place_id
                       ORDER BY rp.handled_at IS NOT NULL, rp.created_at DESC LIMIT 100");
        $badRatings = rows("SELECT r.*, p.display_name AS prov_name, pl.name_ar AS place_name
                              FROM ratings r
                              LEFT JOIN providers p ON p.id = r.provider_id
                              LEFT JOIN places pl ON pl.id = r.place_id
                             WHERE r.is_hidden = 0 AND r.comment IS NOT NULL AND r.comment <> ''
                             ORDER BY r.created_at DESC LIMIT 40"); ?>
  <section class="card">
    <h2><?= icon('alert', 18) ?> البلاغات</h2>
    <?php if (!$list): ?>
      <?= empty_state('مفيش بلاغات', 'الدنيا هادية.', 'shield') ?>
    <?php else: ?>
      <ul class="adminlist">
        <?php foreach ($list as $rp): ?>
          <li class="adminrow col <?= $rp['handled_at'] ? 'done' : '' ?>">
            <div class="ar-line">
              <b><?= e(REPORT_REASONS[$rp['reason']] ?? $rp['reason']) ?></b>
              <span class="muted small">عن <?= e($rp['prov_name'] ?: $rp['place_name'] ?: '—') ?>
                · <?= e(since_ar($rp['created_at'])) ?></span>
              <?= $rp['handled_at'] ? badge('اتقفل', 'muted') : badge('جديد', 'warn') ?>
              <?php if (!$rp['handled_at']): ?>
                <form method="post" class="inline mr-auto"><?= csrf_field() ?>
                  <input type="hidden" name="do" value="report_done"><input type="hidden" name="id" value="<?= e($rp['id']) ?>">
                  <button class="btn ghost sm"><?= icon('check', 14) ?> اتعامل معاه</button>
                </form>
              <?php endif; ?>
            </div>
            <?php if ($rp['details']): ?><p class="ar-body"><?= e($rp['details']) ?></p><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2><?= icon('star', 18) ?> آخر التقييمات</h2>
    <ul class="adminlist">
      <?php foreach ($badRatings as $r): ?>
        <li class="adminrow col">
          <div class="ar-line">
            <?= stars_row((int) $r['stars']) ?>
            <span class="muted small">عن <?= e($r['prov_name'] ?: $r['place_name'] ?: '—') ?>
              · <?= e($r['author_name'] ?: 'مجهول') ?> · <?= e(since_ar($r['created_at'])) ?></span>
            <form method="post" class="inline mr-auto" data-confirm="تخفي التقييم دا؟"><?= csrf_field() ?>
              <input type="hidden" name="do" value="rating_hide"><input type="hidden" name="id" value="<?= e($r['id']) ?>">
              <button class="btn ghost sm danger"><?= icon('eyeoff', 14) ?> اخفي</button>
            </form>
          </div>
          <p class="ar-body"><?= e((string) $r['comment']) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

<?php else: /* settings */ ?>
  <section class="card">
    <h2><?= icon('pin', 18) ?> المناطق</h2>
    <ul class="adminlist">
      <?php foreach (rows('SELECT * FROM zones ORDER BY sort_order, id') as $z): ?>
        <li class="adminrow">
          <span class="dl-ic"><?= icon('pin', 18) ?></span>
          <div class="ar-main"><b><?= e($z['name_ar']) ?></b></div>
          <?= $z['is_active'] ? badge('شغّالة', 'good') : badge('مقفولة', 'muted') ?>
          <form method="post" class="inline"><?= csrf_field() ?>
            <input type="hidden" name="do" value="zone_toggle"><input type="hidden" name="id" value="<?= (int) $z['id'] ?>">
            <input type="hidden" name="v" value="<?= $z['is_active'] ? '0' : '1' ?>">
            <button class="btn ghost sm"><?= $z['is_active'] ? 'اقفل' : 'افتح' ?></button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
    <form method="post" class="form row-form"><?= csrf_field() ?>
      <input type="hidden" name="do" value="zone_add">
      <input type="text" name="name_ar" maxlength="80" placeholder="اسم منطقة جديدة" required>
      <input type="number" name="sort_order" placeholder="الترتيب" value="0">
      <button class="btn call"><?= icon('plus', 16) ?> ضيف</button>
    </form>
  </section>

  <section class="card">
    <h2><?= icon('money', 18) ?> أسعار البداية</h2>
    <?php $rates = []; foreach (rows('SELECT * FROM vehicle_rates') as $r) $rates[$r['kind']] = $r; ?>
    <?php foreach (SERVICES as $k => $label): $r = $rates[$k] ?? null; ?>
      <form method="post" class="form row-form"><?= csrf_field() ?>
        <input type="hidden" name="do" value="rate_save"><input type="hidden" name="kind" value="<?= e($k) ?>">
        <span class="chip <?= bubble_class($k) ?>"><?= icon($k, 15) ?> <?= e($label) ?></span>
        <input type="number" step="0.5" name="starts_from" placeholder="يبدأ من" value="<?= e((string) ($r['starts_from'] ?? '')) ?>">
        <input type="text" name="note_ar" maxlength="120" placeholder="ملاحظة" value="<?= e((string) ($r['note_ar'] ?? '')) ?>">
        <button class="btn ghost"><?= icon('check', 15) ?></button>
      </form>
    <?php endforeach; ?>
  </section>

  <section class="card">
    <h2><?= icon('board', 18) ?> دليل الأسعار بين المناطق</h2>
    <ul class="adminlist">
      <?php foreach (price_guide() as $g): ?>
        <li class="adminrow">
          <div class="ar-main">
            <b><?= e($g['from_zone'] ?: 'أي منطقة') ?> ← <?= e($g['to_zone'] ?: 'أي منطقة') ?></b>
            <span class="muted small"><?= e(SERVICES[$g['kind']] ?? 'كل الأنواع') ?> ·
              <?= e(money($g['typical_min'])) ?>–<?= e(money($g['typical_max'])) ?> جنيه
              <?= $g['note_ar'] ? ' · ' . e($g['note_ar']) : '' ?></span>
          </div>
          <form method="post" class="inline" data-confirm="تمسح الخط دا؟"><?= csrf_field() ?>
            <input type="hidden" name="do" value="guide_del"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
            <button class="btn ghost sm danger"><?= icon('trash', 14) ?></button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
    <form method="post" class="form row-form wrap-form"><?= csrf_field() ?>
      <input type="hidden" name="do" value="guide_add">
      <?= zones_select('from_zone_id', 0, 'من', false) ?>
      <?= zones_select('to_zone_id', 0, 'إلى', false) ?>
      <select name="kind"><option value="">كل الأنواع</option>
        <?php foreach (SERVICES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?>
      </select>
      <input type="number" step="0.5" name="typical_min" placeholder="من" required>
      <input type="number" step="0.5" name="typical_max" placeholder="إلى" required>
      <input type="text" name="note_ar" maxlength="140" placeholder="ملاحظة">
      <button class="btn call"><?= icon('plus', 16) ?> ضيف</button>
    </form>
  </section>
<?php endif; ?>

</main>
<?php
layout('لوحة الإدارة', (string) ob_get_clean(), ['nav' => 'admin']);
