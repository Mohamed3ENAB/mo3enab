<?php
declare(strict_types=1);

if (is_logged_in()) redirect(dash_path());

$errors = [];
if (is_post()) {
    $b = read_signup_basics();
    $errors = $b['errors'];
    $avatarId = null;

    if (!$errors) {
        // صورة الحساب اختيارية للعميل — لو رفع نقصّها، ولو لأ ياخد حرف اسمه
        $a = store_avatar('photo');
        if (isset($a['error'])) $errors[] = 'صورة الحساب: ' . $a['error'];
        else $avatarId = $a['id'];
    }

    if (!$errors) {
        $uid = create_account('customer', $b['data'], $avatarId, 'active');
        q('UPDATE users SET hide_phone = ? WHERE id = ?', [isset($_POST['hide_phone']) ? 1 : 0, $uid]);
        notify($uid, 'أهلًا بيك في السكة 🎉', 'حسابك اتفتح. تقدر تطلب سائق أو تشوف المحلات.', url('dashboard/customer'), 'sparkle');
        login_as($uid);
        set_flash('ok', 'حسابك جاهز. أهلًا بيك!');
        redirect('dashboard/customer');
    }
}

$old = fn(string $k) => e((string) ($_POST[$k] ?? ''));

ob_start(); ?>
<main class="wrap auth" id="main">
  <div class="auth-art" aria-hidden="true"><span class="orb o1"></span><span class="orb o2"></span></div>

  <div class="auth-card card glass pop">
    <span class="step-badge c-cust"><?= icon('user', 18) ?> حساب عميل</span>
    <h1>اعمل حسابك</h1>
    <p class="lede">بياناتك عندنا بتتستخدم للتواصل بس.</p>

    <?= errors_block($errors) ?>

    <form method="post" class="form" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <?= avatar_field('photo', null, 'صورة الحساب (اختيارية)') ?>
      <?= field('الاسم', '<input type="text" name="name" maxlength="80" required placeholder="اسمك بالكامل" value="' . $old('name') . '">', '', true) ?>
      <?= field('رقم الموبايل', '<input type="tel" name="phone" inputmode="numeric" required placeholder="01xxxxxxxxx" value="' . $old('phone') . '">', 'الرقم دا هو اسم الدخول بتاعك.', true) ?>
      <?= field('المنطقة', zones_select('zone_id', (int) ($_POST['zone_id'] ?? 0)), '', true) ?>
      <div class="two">
        <?= field('كلمة السر', password_field('password', '٨ حروف على الأقل'), '', true) ?>
        <?= field('تأكيد كلمة السر', password_field('password2', 'اكتبها تاني'), '', true) ?>
      </div>

      <label class="switch-row">
        <input type="checkbox" name="hide_phone" <?= isset($_POST['hide_phone']) ? 'checked' : '' ?>>
        <span class="sw"></span>
        <span><b>اخفي رقمي في الطلبات</b><i>هيبان مقنّع (٠١٢٣••••٨٩) والسواقين يكلّموك من جوه التطبيق.</i></span>
      </label>

      <button class="btn call wide big" data-sfx="ok"><?= icon('userplus', 18) ?> افتح الحساب</button>
      <p class="center muted small">بتسجيلك بتوافق إن «في السكة» وسيط مجاني ومش طرف في أي اتفاق.</p>
    </form>

    <div class="auth-alt"><a class="linkbtn" href="<?= url('register') ?>">أنا سائق أو صاحب محل</a></div>
  </div>
</main>
<?php
layout('حساب عميل', (string) ob_get_clean(), ['nav' => 'login']);
