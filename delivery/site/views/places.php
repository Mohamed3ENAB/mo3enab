<?php
declare(strict_types=1);

$all   = all_places();
$zones = all_zones();
$used  = array_unique(array_column($all, 'category'));

ob_start(); ?>
<main class="wrap page" id="main">
  <?= page_head('المحلات والمطاعم', 'أرقام ومواعيد محلات المنطقة في مكان واحد.', 'store') ?>

  <div class="filters" data-filters>
    <label class="searchbox">
      <?= icon('search', 18) ?>
      <input type="search" placeholder="دوّر باسم المحل" data-search>
    </label>

    <div class="chiprow" role="group" aria-label="النوع">
      <button class="fchip on" data-f="cat" data-v=""><?= icon('all', 15) ?> الكل</button>
      <?php foreach (CATEGORIES as $k => $label): if (!in_array($k, $used, true)) continue; ?>
        <button class="fchip" data-f="cat" data-v="<?= e($k) ?>"><?= icon($k, 15) ?> <?= e($label) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="chiprow" role="group" aria-label="المنطقة">
      <button class="fchip on" data-f="zone" data-v=""><?= icon('pin', 15) ?> كل المناطق</button>
      <?php foreach ($zones as $z): ?>
        <button class="fchip" data-f="zone" data-v="<?= (int) $z['id'] ?>"><?= e($z['name_ar']) ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="cards" data-list>
    <?php foreach ($all as $pl) echo place_card($pl); ?>
  </div>

  <?php if (!$all): ?>
    <?= empty_state('لسه مفيش محلات', 'عندك محل؟ سجّله وهيظهر هنا بعد المراجعة.', 'store', 'سجّل محلك', url('register/merchant')) ?>
  <?php endif; ?>
  <p class="noresult" data-noresult hidden><?= icon('search', 22) ?> مفيش نتايج بالفلاتر دي.</p>

  <?= site_footer() ?>
</main>
<?php
layout('المحلات', (string) ob_get_clean(), ['nav' => 'places']);
