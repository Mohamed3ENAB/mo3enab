<?php
declare(strict_types=1);

if (is_logged_in()) redirect(dash_path());

$err = null;
if (is_post()) {
    $phone = preg_replace('/\D/', '', post('phone', 20));
    $pass  = (string) ($_POST['password'] ?? '');
    $res   = attempt_login($phone, $pass);
    if (isset($res['user'])) {
        $u = $res['user'];
        set_flash('ok', 'أهلًا يا ' . $u['name'] . ' 👋');
        $next = $_SESSION['after_login'] ?? '';
        unset($_SESSION['after_login']);
        if ($u['status'] !== 'active') redirect('dashboard/pending');
        if ($next && str_starts_with($next, '/')) { header('Location: ' . $next); exit; }
        redirect(dash_path($u['role']));
    }
    $err = $res['error'];
}

ob_start(); ?>
<main class="wrap auth" id="main">

  <div class="auth-art" aria-hidden="true">
    <span class="orb o1"></span><span class="orb o2"></span><span class="orb o3"></span>
  </div>

  <div class="auth-card card glass pop">
    <div class="auth-logo"><?= icon('logo', 26) ?></div>
    <h1>أهلًا بيك تاني</h1>
    <p class="lede">سجّل دخولك وشوف حسابك.</p>

    <?= $err ? '<p class="msg bad">' . icon('alert', 16) . ' ' . e($err) . '</p>' : '' ?>

    <form method="post" class="form" novalidate>
      <?= csrf_field() ?>
      <?= field('رقم الموبايل',
            '<input type="tel" name="phone" inputmode="numeric" autocomplete="username"
                    placeholder="01xxxxxxxxx" required value="' . e((string)($_POST['phone'] ?? '')) . '">',
            '', true) ?>
      <?= field('كلمة السر',
            '<span class="pw"><input type="password" name="password" placeholder="كلمة السر" required
                     autocomplete="current-password">
             <button type="button" class="pw-eye" data-pw-toggle aria-label="إظهار كلمة السر">' . icon('eye', 18) . '</button></span>',
            '', true) ?>
      <button class="btn call wide big" data-sfx="ok"><?= icon('login', 18) ?> دخول</button>
    </form>

    <div class="auth-alt">
      <p>لسه معندكش حساب؟</p>
      <a class="btn ghost wide" href="<?= url('register') ?>"><?= icon('userplus', 17) ?> اعمل حساب جديد</a>
      <a class="linkbtn" href="<?= url('') ?>">تصفّح الدليل من غير حساب</a>
    </div>
  </div>

</main>
<?php
layout('تسجيل الدخول', (string) ob_get_clean(), ['nav' => 'login']);
