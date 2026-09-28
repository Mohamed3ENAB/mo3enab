<?php
declare(strict_types=1);
if (is_admin()) redirect('admin');
$bad = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_password(post('password', 200))) { $bad = 'كلمة السر غلط.'; usleep(400000); }
    else { login_admin(); redirect('admin'); }
}
ob_start(); ?>
<main class="wrap page" style="padding-top:60px">
  <div style="width:62px;height:62px;border-radius:20px;background:var(--hero);color:#fff;
    display:grid;place-items:center;margin-bottom:18px"><?= icon('shield', 28) ?></div>
  <h1>لوحة الإدارة</h1>
  <p class="lede">الصفحة دي لمسؤول الخدمة بس.</p>
  <form class="card" method="post">
    <?= flash(null, $bad) ?>
    <label class="field" for="password"><span>كلمة السر</span>
      <input id="password" name="password" type="password" required autocomplete="current-password"></label>
    <button class="btn call wide" type="submit">ادخل</button>
  </form>
</main>
<?php
layout('دخول الإدارة', (string) ob_get_clean());
