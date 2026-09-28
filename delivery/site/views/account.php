<?php
declare(strict_types=1);

$u = require_login();
$msg = null; $err = null;

if (is_post()) {
    switch (post('do')) {
        case 'profile':
            $name = post('name', 80);
            if (mb_strlen($name) < 3) { $err = 'اكتب اسمك كامل.'; break; }

            $avatarId = $u['avatar_id'];
            $a = store_avatar('photo');
            if (isset($a['error'])) { $err = $a['error']; break; }
            if (!empty($a['id'])) {
                delete_image($u['avatar_id']);
                $avatarId = $a['id'];
            }
            q('UPDATE users SET name = ?, avatar_id = ?, hide_phone = ? WHERE id = ?',
              [$name, $avatarId, isset($_POST['hide_phone']) ? 1 : 0, $u['id']]);
            // الصورة تمشي مع ملف السائق أو المحل كمان
            if ($u['provider_id']) q('UPDATE providers SET display_name = ?, photo_id = ? WHERE id = ?', [$name, $avatarId, $u['provider_id']]);
            if ($u['place_id'] && !empty($a['id'])) q('UPDATE places SET photo_id = ? WHERE id = ?', [$avatarId, $u['place_id']]);
            $msg = 'البيانات اتحفظت.';
            break;

        case 'password':
            $cur = (string) ($_POST['current'] ?? '');
            $new = (string) ($_POST['password'] ?? '');
            if (!password_verify($cur, $u['pass_hash']))     { $err = 'كلمة السر الحالية غلط.'; break; }
            if ($p = password_problem($new))                 { $err = $p; break; }
            if ($new !== (string) ($_POST['password2'] ?? '')) { $err = 'كلمتين السر مش زي بعض.'; break; }
            q('UPDATE users SET pass_hash = ? WHERE id = ?', [hash_password($new), $u['id']]);
            $msg = 'كلمة السر اتغيّرت.';
            break;
    }
    $u = row('SELECT * FROM users WHERE id = ?', [$u['id']]);
}

ob_start(); ?>
<main class="wrap page" id="main">
  <?= page_head('إعدادات الحساب', ROLE_NAMES[$u['role']] . ' · ' . $u['phone'], 'settings') ?>
  <?= flash($msg, $err) ?>

  <section class="card">
    <h2><?= icon('user', 18) ?> بياناتك</h2>
    <form method="post" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?><input type="hidden" name="do" value="profile">
      <?= avatar_field('photo', $u['avatar_id'], 'صورة الحساب') ?>
      <?= field('الاسم', '<input type="text" name="name" maxlength="80" required value="' . e($u['name']) . '">', '', true) ?>
      <?= field('رقم الموبايل', '<input type="tel" value="' . e($u['phone']) . '" disabled>', 'الرقم مش بيتغيّر من هنا — كلّم الإدارة.') ?>
      <?php if ($u['role'] === 'customer'): ?>
      <label class="switch-row">
        <input type="checkbox" name="hide_phone" <?= (int) $u['hide_phone'] ? 'checked' : '' ?>>
        <span class="sw"></span>
        <span><b>اخفي رقمي في الطلبات</b><i>هيبان مقنّع والسواقين يكلّموك من جوه التطبيق.</i></span>
      </label>
      <?php endif; ?>
      <button class="btn call" data-sfx="ok"><?= icon('check', 17) ?> احفظ</button>
    </form>
  </section>

  <section class="card">
    <h2><?= icon('lock', 18) ?> كلمة السر</h2>
    <form method="post" class="form">
      <?= csrf_field() ?><input type="hidden" name="do" value="password">
      <?= field('كلمة السر الحالية',
          '<span class="pw"><input type="password" name="current" required autocomplete="current-password">'
        . '<button type="button" class="pw-eye" data-pw-toggle aria-label="إظهار">' . icon('eye', 18) . '</button></span>', '', true) ?>
      <div class="two">
        <?= field('كلمة السر الجديدة', password_field('password', '٨ حروف على الأقل'), '', true) ?>
        <?= field('تأكيدها', password_field('password2', 'اكتبها تاني'), '', true) ?>
      </div>
      <button class="btn ghost" data-sfx="ok"><?= icon('refresh', 16) ?> غيّر كلمة السر</button>
    </form>
  </section>

  <div class="row-btns">
    <a class="btn ghost" href="<?= dash_url($u['role']) ?>"><?= icon('dash', 16) ?> الداش بورد</a>
    <a class="btn ghost" href="<?= url('logout') ?>"><?= icon('logout', 16) ?> خروج</a>
  </div>
</main>
<?php
layout('إعدادات الحساب', (string) ob_get_clean(), ['nav' => dash_path($u['role'])]);
