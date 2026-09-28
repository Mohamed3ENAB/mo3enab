<?php
declare(strict_types=1);

$rates = vehicle_rates();
$guide = price_guide();

ob_start(); ?>
<main class="wrap page" id="main">
  <?= page_head('الأسعار', 'أرقام تقريبية بيتفق عليها الناس في المنطقة — مش أسعار ملزمة.', 'money') ?>

  <div class="note-box">
    <?= icon('info', 18) ?>
    <div><b>إحنا مش بنحدد السعر.</b>
    <span>السعر بينتفق عليه بينك وبين السائق. الأرقام دي بس عشان تعرف المعتاد وتبقى مطمّن.</span></div>
  </div>

  <?php if ($rates): ?>
  <section class="sec">
    <h2 class="sec-h"><?= icon('bolt', 18) ?> يبدأ من</h2>
    <div class="ratecards">
      <?php foreach ($rates as $r): ?>
        <div class="ratecard tilt <?= bubble_class($r['kind']) ?>">
          <span class="rc-ic"><?= icon($r['kind'], 24) ?></span>
          <b><?= e(SERVICES[$r['kind']] ?? $r['kind']) ?></b>
          <span class="rc-price"><?= e(money($r['starts_from'])) ?> <i>جنيه</i></span>
          <?php if ($r['note_ar']): ?><span class="muted small"><?= e($r['note_ar']) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($guide): ?>
  <section class="sec">
    <h2 class="sec-h"><?= icon('pin', 18) ?> بين المناطق</h2>
    <div class="table-wrap card">
      <table class="tbl">
        <thead><tr><th>من</th><th>إلى</th><th>النوع</th><th>المعتاد</th></tr></thead>
        <tbody>
          <?php foreach ($guide as $g): ?>
            <tr>
              <td><?= e($g['from_zone'] ?: 'أي منطقة') ?></td>
              <td><?= e($g['to_zone'] ?: 'أي منطقة') ?></td>
              <td><?= e(SERVICES[$g['kind']] ?? 'الكل') ?></td>
              <td class="num"><?= e(money($g['typical_min'])) ?>–<?= e(money($g['typical_max'])) ?> ج
                <?php if ($g['note_ar']): ?><i class="muted small"><?= e($g['note_ar']) ?></i><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!$rates && !$guide): ?>
    <?= empty_state('الأسعار لسه متحطّتش', 'الإدارة بتحدّثها من لوحة التحكم.', 'money') ?>
  <?php endif; ?>

  <section class="card soft">
    <h3><?= icon('shield', 17) ?> نصايح قبل ما تتفق</h3>
    <ul class="tips">
      <li>اتفق على السعر <b>قبل</b> ما تركب.</li>
              <li>لو المشوار بعيد، قول المكان بالتفصيل عشان السعر ميتغيّرش في النص.</li>
      <li>لو حصل خلاف، بلّغ من صفحة السائق — الإدارة بتتابع.</li>
    </ul>
  </section>

  <?= site_footer() ?>
</main>
<?php
layout('الأسعار', (string) ob_get_clean(), ['nav' => 'prices']);
