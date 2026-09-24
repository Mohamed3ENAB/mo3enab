<?php
declare(strict_types=1);

$rates  = vehicle_rates();
$guide  = price_guide();

ob_start(); ?>
<?= hero('money', 'الأسعار', 'اعرف السعر العادل قبل ما تتفق', 'دي متوسط اللي بيتدفع في البلد.', false) ?>
<main class="wrap page" style="padding-top:22px">
  <div class="note warn"><?= icon('alert', 19, 'ic') ?>
    <span><strong>دي مش تسعيرة ملزمة.</strong> إحنا مش بنحدد أسعار ومش بناخد عمولة.
      الاتفاق النهائي بينك وبين السائق.</span></div>

  <h2 style="margin-top:0">يبدأ من</h2>
  <div class="rates">
    <?php foreach ($rates as $r): ?>
      <div class="rate-row">
        <span class="bubble <?= bubble_class($r['kind']) ?>"><?= icon($r['kind'], 22) ?></span>
        <span class="t"><b><?= e(SERVICES[$r['kind']] ?? $r['kind']) ?></b>
          <?= $r['note_ar'] ? '<span>' . e($r['note_ar']) . '</span>' : '' ?></span>
        <span class="v"><em>من</em><b><?= e(money($r['starts_from'])) ?> ج</b></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="note" style="margin-top:16px"><?= icon('info', 19, 'ic') ?>
    <span>العجلة رخيصة عن قصد. الطلب القريب جوه البلد مش محتاج موتوسيكل، وده بيخلي
      أي حد عنده عجلة يقدر يشتغل ويكسب.
      <a href="<?= url('join') ?>" style="font-weight:800">اعرف إزاي تشتغل معانا</a>.</span></div>

  <h2>الأسعار حسب المشوار</h2>
  <div class="card">
    <?php foreach ($guide as $g): ?>
      <div class="price">
        <span class="bubble <?= bubble_class((string) $g['kind']) ?>"><?= icon((string) $g['kind'], 20) ?></span>
        <span class="t"><b><?= e($g['from_zone'] === $g['to_zone']
              ? 'جوه ' . $g['from_zone'] : $g['from_zone'] . ' ← ' . $g['to_zone']) ?></b>
          <span><?= e(SERVICES[$g['kind']] ?? '') ?><?= $g['note_ar'] ? ' · ' . e($g['note_ar']) : '' ?></span></span>
        <span class="v"><?= e(money($g['typical_min'])) ?>–<?= e(money($g['typical_max'])) ?> ج</span>
      </div>
    <?php endforeach; ?>
  </div>

  <h2>لو محتاج حاجة من المحلة</h2>
  <div class="card"><p class="lede" style="margin:0">
    مشوار المحلة ذهاب وعودة تكلفته الحقيقية على السائق ٣٥–٤٥ جنيه بنزين وصيانة، قبل ما ياخد مليم.
    لو تلات ناس محتاجين حاجة من المحلة في نفس اليوم، مشوار واحد يخدمكم كلكم: كل واحد يدفع أقل
    والسائق ياخد أكتر. اكتبوا في لوحة الطلبات واتفقوا.</p></div>

  <?= site_footer() ?>
</main>
<?php
layout('الأسعار', (string) ob_get_clean(), bottom_nav('prices'));
