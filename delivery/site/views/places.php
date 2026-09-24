<?php
declare(strict_types=1);

$places = all_places();
$zones  = all_zones();
$liveD  = live_providers(all_providers());
$cats   = array_values(array_unique(array_column($places, 'category')));
$catMap = array_intersect_key(CATEGORIES, array_flip($cats));

ob_start(); ?>
<?= hero('store', 'المحلات والمطاعم', 'اطلب من أي محل في البلد', 'كلّم المحل واطلب، وبعدين كلّم سائق يستلم منهم.') ?>
<main class="wrap">
  <?= search_box('دوّر على محل أو مطعم') ?>
  <?= $catMap ? chips_services(array_keys($catMap), $catMap, 'التصنيفات') : '' ?>
  <?= chips_zones($zones) ?>

  <div data-filterable data-zone="" data-kind="">
    <section class="sec" data-group>
      <div class="sec-head"><h2>كل المحلات</h2><span class="side" data-count><?= count($places) ?></span></div>
      <?php if ($places): ?>
        <div class="cards"><?php foreach ($places as $pl) echo place_card($pl, $liveD); ?></div>
      <?php else: ?>
        <div class="empty"><div class="ill"><?= icon('store', 30) ?></div>
          <h3>لسه مفيش محلات مضافة</h3>
          <p>تعرف محل أو مطعم بيوصّل؟ قولنا عليه ونضيفه.</p>
          <a class="btn call wide" href="<?= url('join/apply?kind=place') ?>">سجّل محلك</a></div>
      <?php endif; ?>
    </section>
    <section class="sec">
      <div class="empty" id="no-results" hidden>
        <h3>مفيش نتايج</h3><p>مفيش محل مطابق للاختيار ده.</p>
        <button class="btn ghost wide" type="button" id="clear-filters">شيل الفلاتر</button></div>
    </section>
  </div>
  <?= site_footer() ?>
</main>
<?php
layout('المحلات والمطاعم', (string) ob_get_clean(), bottom_nav('places'));
