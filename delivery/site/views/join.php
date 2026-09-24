<?php
declare(strict_types=1);
$rates = vehicle_rates();
$phone = (string) cfg('support_phone');
$needed = [
    'بطاقة الرقم القومي سارية',
    'رخصة قيادة ورخصة مركبة (مش مطلوبة للعجلة)',
    'موبايل فيه نت',
    'موافقتك على نشر اسمك ورقمك في الدليل',
];
$steps = [
    ['كلّمنا وابعت بياناتك', 'الاسم والرقم ونوع المركبة والقرية اللي بتشتغل فيها.'],
    ['نشوف ورقك', 'البطاقة والرخصة. ده اللي بيخلي الناس تطمن وتكلمك.'],
    ['تاخد لينك خاص بيك', 'تفتحه وتضغط زرار واحد «أنا متاح» لما تبقى فاضي، و«مش متاح» لما تخلص. وبس.'],
    ['الناس تكلمك على طول', 'مفيش وسيط ومفيش نسبة. تتفق مع العميل وتاخد فلوسك كاملة.'],
];

ob_start(); ?>
<?= hero('bicycle', 'اشتغل معانا', 'عندك عجلة؟ تقدر تبدأ من النهاردة', 'مفيش عمولة ومفيش اشتراك. الفلوس كلها تروح لك.', false) ?>
<main class="wrap page" style="padding-top:22px">
  <div class="note"><?= icon('info', 19, 'ic') ?>
    <span>إحنا <strong>مش شركة توصيل</strong> ومش بناخد ولا جنيه. إحنا بنعرض اسمك ورقمك في دليل،
      والناس بتكلمك على طول وتتفق معاك. اللي تتفق عليه ياخده انت كله.</span></div>

  <h2>الطلب بياخد كام؟</h2>
  <div class="rates"><?php foreach ($rates as $r): ?>
    <div class="rate-row">
      <span class="bubble <?= bubble_class($r['kind']) ?>"><?= icon($r['kind'], 22) ?></span>
      <span class="t"><b><?= e(SERVICES[$r['kind']] ?? $r['kind']) ?></b>
        <?= $r['note_ar'] ? '<span>' . e($r['note_ar']) . '</span>' : '' ?></span>
      <span class="v"><em>من</em><b><?= e(money($r['starts_from'])) ?> ج</b></span></div>
  <?php endforeach; ?></div>

  <h2>محتاج إيه</h2>
  <div class="card"><?php foreach ($needed as $n): ?>
    <div class="mini"><span style="width:24px;height:24px;border-radius:50%;flex:0 0 auto;
      background:var(--brand-tint);color:var(--brand-600);display:grid;place-items:center">
      <?= icon('check', 13) ?></span>
      <span class="t"><b style="font-weight:700;font-size:14.5px"><?= e($n) ?></b></span></div>
  <?php endforeach; ?></div>

  <h2>بيشتغل إزاي</h2>
  <div class="card"><?php foreach ($steps as $i => [$t, $d]): ?>
    <div class="step"<?= $i === count($steps) - 1 ? ' style="margin-bottom:0"' : '' ?>>
      <span class="n"><?= $i + 1 ?></span>
      <div class="c"><b><?= e($t) ?></b><p><?= e($d) ?></p></div></div>
  <?php endforeach; ?></div>

  <a class="btn call wide" href="<?= url('join/apply?kind=driver') ?>" style="margin-bottom:10px">
    <?= icon('userplus', 19) ?> سجّل نفسك كسائق</a>
  <?php if ($phone): ?>
    <a class="btn ghost wide" target="_blank" rel="noreferrer" style="margin-bottom:10px"
       href="<?= e(wa_link($phone, 'السلام عليكم، عايز أشتغل مع «في السكة». معايا ')) ?>">
      <?= icon('wa', 20) ?> أو ابعتلنا واتساب</a>
  <?php endif; ?>

  <h2>عندك محل أو مطعم؟</h2>
  <div class="card">
    <p class="lede" style="margin-top:0">نضيف اسم محلك ورقمك في
      <a href="<?= url('places') ?>" style="font-weight:800">دليل المحلات</a> ببلاش،
      والناس تطلب منك مباشرة.</p>
    <a class="btn ghost wide" href="<?= url('join/apply?kind=place') ?>">سجّل محلك</a>
  </div>
  <?= site_footer() ?>
</main>
<?php
layout('اشتغل معانا', (string) ob_get_clean(), bottom_nav(''));
