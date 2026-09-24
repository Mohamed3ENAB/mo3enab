<?php
declare(strict_types=1);

$providers = all_providers();
$zones     = all_zones();
$live      = live_providers($providers);
$off       = array_values(array_filter($providers, fn($p) => !is_live($p)));
$n         = count($live);

$h1 = match (true) {
    $n === 0 => 'مفيش حد متاح دلوقتي',
    $n === 1 => 'سائق واحد متاح دلوقتي',
    $n === 2 => 'سائقين متاحين دلوقتي',
    default  => "$n سواقين متاحين دلوقتي",
};

ob_start(); ?>
<?= hero('logo', 'في السكة', $h1, 'اضغط اتصال وكلّمه على طول — من غير عمولة ولا وسيط.') ?>
<main class="wrap">
  <?= search_box('دوّر على اسم سائق أو مركبة') ?>
  <?= install_bar() ?>
  <?= chips_services(array_keys(SERVICES), SERVICES, 'الخدمات') ?>
  <?= chips_zones($zones) ?>

  <div data-filterable data-zone="" data-kind="">
    <section class="sec" data-group>
      <div class="sec-head"><h2>متاحين دلوقتي</h2><span class="side" data-count><?= $n ?></span></div>
      <?php if ($live): ?>
        <div class="cards"><?php foreach ($live as $p) echo provider_card($p); ?></div>
      <?php else: ?>
        <div class="empty"><div class="ill"><?= icon('sleep', 30) ?></div>
          <h3>مفيش حد فاتح دلوقتي</h3>
          <p>جرّب قرية جنبك، أو اكتب طلبك واللي هيفتح هيشوفه.</p>
          <a class="btn call wide" href="<?= url('requests') ?>"><?= icon('board', 19) ?> اكتب طلبك</a></div>
      <?php endif; ?>
    </section>

    <?php if ($off): ?>
      <section class="sec" data-group>
        <div class="sec-head"><h2>مش متاحين دلوقتي</h2><span class="side"><?= count($off) ?></span></div>
        <div class="cards"><?php foreach ($off as $p) echo provider_card($p); ?></div>
      </section>
    <?php endif; ?>

    <section class="sec">
      <div class="empty" id="no-results" hidden>
        <h3>مفيش نتايج</h3><p>مفيش سائق مطابق للاختيار ده.</p>
        <button class="btn ghost wide" type="button" id="clear-filters">شيل الفلاتر</button>
      </div>
    </section>
  </div>

  <section class="sec">
    <div class="sec-head"><h2>كمان</h2></div>
    <div class="cards">
      <a class="tile" href="<?= url('places') ?>">
        <span class="bubble b-restaurant"><?= icon('store', 22) ?></span>
        <span class="t"><b>المحلات والمطاعم</b><span>اطلب من أي محل، وسائق يستلم منهم</span></span>
        <?= icon('chevron', 18, 'go') ?></a>
      <a class="tile" href="<?= url('join') ?>">
        <span class="bubble b-bicycle"><?= icon('bicycle', 22) ?></span>
        <span class="t"><b>اشتغل معانا</b><span>عندك عجلة؟ ابدأ من 5 جنيه للطلب</span></span>
        <?= icon('chevron', 18, 'go') ?></a>
    </div>
  </section>

  <?= site_footer() ?>
</main>
<?php
layout('', (string) ob_get_clean(), bottom_nav('home'));
