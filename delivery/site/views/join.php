<?php
declare(strict_types=1);

ob_start(); ?>
<main class="wrap page" id="main">
  <?= page_head('اشتغل معانا', 'التطبيق مجاني، ومفيش عمولة على أي مشوار أو طلب.', 'userplus') ?>

  <div class="note-box">
    <?= icon('shield', 18) ?>
    <div><b>مفيش عمولة ومفيش اشتراك.</b>
    <span>دي خدمة مجتمعية لأهل المنطقة. إحنا بنعرض بياناتك بس، والاتفاق بينك وبين الزبون.</span></div>
  </div>

  <div class="pick-grid">
    <a class="pick card tilt" href="<?= url('register/driver') ?>" data-sfx="tap">
      <span class="pick-ic c-drv"><?= icon('tuktuk', 26) ?></span>
      <h3>سجّل كسائق</h3>
      <p>توك توك، عربية، موتوسيكل، أو عجلة. افتح التوفر لما تكون فاضي، والناس تلاقيك.</p>
      <ul class="pick-list">
        <li><?= icon('check', 13) ?> اسمك في أول الدليل وإنت متاح</li>
        <li><?= icon('check', 13) ?> تشوف الطلبات المفتوحة وتستلمها</li>
        <li><?= icon('check', 13) ?> علامة «موثّق» بعد مراجعة البطاقة والرخصة</li>
      </ul>
      <span class="pick-go"><?= icon('back', 18) ?></span>
    </a>

    <a class="pick card tilt" href="<?= url('register/merchant') ?>" data-sfx="tap">
      <span class="pick-ic c-shop"><?= icon('store', 26) ?></span>
      <h3>سجّل محلك</h3>
      <p>مطعم، سوبر ماركت، صيدلية، أي حاجة. صورة ومواعيد ورقم — والناس تطلب منك.</p>
      <ul class="pick-list">
        <li><?= icon('check', 13) ?> صفحة للمحل بصورته وتقييماته</li>
        <li><?= icon('check', 13) ?> تفتح وتقفل المحل بزرار</li>
        <li><?= icon('check', 13) ?> ظهور في بحث المنطقة</li>
      </ul>
      <span class="pick-go"><?= icon('back', 18) ?></span>
    </a>
  </div>

  <section class="card">
    <h2><?= icon('info', 18) ?> بيحصل إيه بعد ما تسجّل؟</h2>
    <ol class="steps-list">
      <li><b>تملا البيانات</b><span>اسمك ورقمك ومنطقتك، وترفع صورتك وصور البطاقة والرخصة.</span></li>
      <li><b>الإدارة تراجع</b><span>بنتأكد إن الصور واضحة والاسم مطابق. عادة من ساعة لـ يوم.</span></li>
      <li><b>حسابك يفتح</b><span>هتلاقي الداش بورد بتاعتك، وتقدر تفتح التوفر وتستلم طلبات.</span></li>
    </ol>
  </section>

  <section class="card soft">
    <h3><?= icon('lock', 17) ?> صورك في أمان</h3>
    <p class="muted">صور البطاقة والرخصة محدش بيشوفها غير الإدارة. مش بتظهر في صفحتك ولا لأي مستخدم،
    وبتتخزن مقفولة جوه قاعدة البيانات. اللي بيظهر للناس هو اسمك وصورتك الشخصية ورقمك بس.</p>
  </section>

  <?= site_footer() ?>
</main>
<?php
layout('اشتغل معانا', (string) ob_get_clean(), ['nav' => 'home']);
