<?php
declare(strict_types=1);

if (is_logged_in()) redirect(dash_path());

ob_start(); ?>
<main class="wrap page" id="main">
  <?= page_head('اعمل حساب', 'اختار نوع الحساب اللي يناسبك — كل نوع ليه داش بورد مختلفة.', 'userplus') ?>

  <div class="pick-grid">

    <a class="pick card tilt" href="<?= url('register/customer') ?>" data-sfx="tap">
      <span class="pick-ic c-cust"><?= icon('user', 26) ?></span>
      <h3>عميل</h3>
      <p>عايز سائق أو توك توك أو تطلب من محل. تسجيل في نص دقيقة والحساب بيشتغل على طول.</p>
      <span class="pick-go"><?= icon('back', 18) ?></span>
      <ul class="pick-list">
        <li><?= icon('check', 13) ?> اطلب وتابع طلبك</li>
        <li><?= icon('check', 13) ?> المفضلة والتقييمات</li>
        <li><?= icon('check', 13) ?> تخفي رقمك لو حبيت</li>
      </ul>
    </a>

    <a class="pick card tilt" href="<?= url('register/driver') ?>" data-sfx="tap">
      <span class="pick-ic c-drv"><?= icon('tuktuk', 26) ?></span>
      <h3>سائق</h3>
      <p>عندك عربية أو توك توك أو موتوسيكل. سجّل بياناتك وصورتك والبطاقة والرخصة، والإدارة تراجع.</p>
      <span class="pick-go"><?= icon('back', 18) ?></span>
      <ul class="pick-list">
        <li><?= icon('check', 13) ?> تفتح وتقفل التوفر</li>
        <li><?= icon('check', 13) ?> تشوف الطلبات وتستلمها</li>
        <li><?= icon('check', 13) ?> علامة «موثّق» بعد المراجعة</li>
      </ul>
    </a>

    <a class="pick card tilt" href="<?= url('register/merchant') ?>" data-sfx="tap">
      <span class="pick-ic c-shop"><?= icon('store', 26) ?></span>
      <h3>محل أو مطعم</h3>
      <p>حط محلك في الدليل بصورة ومواعيد ورقم. الناس تلاقيك وتطلب منك.</p>
      <span class="pick-go"><?= icon('back', 18) ?></span>
      <ul class="pick-list">
        <li><?= icon('check', 13) ?> صفحة للمحل بصورته</li>
        <li><?= icon('check', 13) ?> تفتح وتقفل المحل</li>
        <li><?= icon('check', 13) ?> تقييمات زباينك</li>
      </ul>
    </a>

  </div>

  <p class="center muted pad">عندك حساب؟ <a href="<?= url('login') ?>">سجّل دخولك</a></p>
</main>
<?php
layout('حساب جديد', (string) ob_get_clean(), ['nav' => 'login']);
