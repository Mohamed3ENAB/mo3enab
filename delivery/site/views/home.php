<?php
declare(strict_types=1);

$all   = all_providers();
$live  = live_providers($all);
$zones = all_zones();
$u     = current_user();

ob_start(); ?>
<main class="wrap" id="main">

  <!-- الهيرو -->
  <section class="hero">
    <div class="hero-bg" aria-hidden="true">
      <span class="orb o1"></span><span class="orb o2"></span><span class="orb o3"></span>
      <svg class="hero-road" viewBox="0 0 400 220" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
        <defs>
          <linearGradient id="rd" x1="0" y1="1" x2="0" y2="0">
            <stop offset="0" stop-color="currentColor" stop-opacity=".22"/>
            <stop offset="1" stop-color="currentColor" stop-opacity="0"/>
          </linearGradient>
        </defs>
        <path d="M120 220 L185 40 L215 40 L280 220 Z" fill="url(#rd)"/>
        <g class="road-dashes" stroke="currentColor" stroke-width="5" stroke-linecap="round" opacity=".5">
          <line x1="200" y1="206" x2="200" y2="176"/>
          <line x1="200" y1="156" x2="200" y2="132"/>
          <line x1="200" y1="116" x2="200" y2="98"/>
          <line x1="200" y1="86"  x2="200" y2="72"/>
        </g>
      </svg>
    </div>

    <div class="hero-in">
      <span class="pill-top"><?= icon('sparkle', 14) ?> القيصرية والمناطق المجاورة</span>
      <h1 class="hero-h">محتاج سائق؟ <span class="grad">شوف مين متاح دلوقتي</span></h1>
      <p class="hero-p">دليل مجاني لسواقين التوك توك والدليفري والمحلات. الرقم قدامك، تكلّمه على طول، من غير وسيط ومن غير عمولة.</p>

      <div class="hero-stats">
        <div><b data-count="<?= count($live) ?>">0</b><span>متاح دلوقتي</span></div>
        <div><b data-count="<?= count($all) ?>">0</b><span>سائق</span></div>
        <div><b data-count="<?= count($zones) ?>">0</b><span>منطقة</span></div>
      </div>

      <div class="hero-acts">
        <?php if ($u && $u['role'] === 'customer'): ?>
          <a class="btn call big" href="<?= url('dashboard/customer') ?>" data-sfx="tap"><?= icon('plus', 18) ?> اطلب دلوقتي</a>
        <?php else: ?>
          <a class="btn call big" href="<?= url('requests') ?>" data-sfx="tap"><?= icon('board', 18) ?> لوحة الطلبات</a>
        <?php endif; ?>
        <a class="btn ghost big" href="<?= url('places') ?>"><?= icon('store', 18) ?> المحلات</a>
      </div>
    </div>
  </section>

  <!-- اختصارات -->
  <nav class="quick">
    <?php foreach (SERVICES as $k => $label): ?>
      <a class="qt <?= bubble_class($k) ?>" href="#list" data-quick="<?= e($k) ?>" data-sfx="tap">
        <span class="qt-ic"><?= icon($k, 22) ?></span><?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if (!$u): ?>
  <section class="joincta card glass">
    <div>
      <h3><?= icon('userplus', 18) ?> اعمل حساب في دقيقة</h3>
      <p class="muted small">تطلب وتتابع طلبك، وتحفظ سواقينك المفضلين. وإنت سائق أو صاحب محل؟ سجّل وابدأ.</p>
    </div>
    <div class="row-btns">
      <a class="btn call sm" href="<?= url('register') ?>">حساب جديد</a>
      <a class="btn ghost sm" href="<?= url('login') ?>">دخول</a>
    </div>
  </section>
  <?php endif; ?>

  <!-- المتاحين -->
  <?php if ($live): ?>
  <section class="sec">
    <h2 class="sec-h"><span class="dotlive"></span> متاح دلوقتي <i><?= count($live) ?></i></h2>
    <div class="rail">
      <?php foreach ($live as $p): ?>
        <a class="livecard" href="<?= url('p/' . e($p['id'])) ?>" data-sfx="tap">
          <?= avatar($p['display_name'], $p['photo_id'], (bool) $p['is_verified'], 54) ?>
          <b><?= e($p['display_name']) ?></b>
          <span class="muted small"><?= e($p['zone_name']) ?></span>
          <span class="lc-go"><?= icon('phone', 14) ?> كلّمه</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- الدليل -->
  <section class="sec" id="list">
    <h2 class="sec-h"><?= icon('tuktuk', 20) ?> كل السواقين <i><?= count($all) ?></i></h2>

    <div class="filters" data-filters>
      <label class="searchbox">
        <?= icon('search', 18) ?>
        <input type="search" placeholder="دوّر باسم السائق" data-search>
      </label>

      <div class="chiprow" role="group" aria-label="الخدمة">
        <button class="fchip on" data-f="service" data-v=""><?= icon('all', 15) ?> الكل</button>
        <?php foreach (SERVICES as $k => $label): ?>
          <button class="fchip <?= bubble_class($k) ?>" data-f="service" data-v="<?= e($k) ?>"><?= icon($k, 15) ?> <?= e($label) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="chiprow" role="group" aria-label="المنطقة">
        <button class="fchip on" data-f="zone" data-v=""><?= icon('pin', 15) ?> كل المناطق</button>
        <?php foreach ($zones as $z): ?>
          <button class="fchip" data-f="zone" data-v="<?= (int) $z['id'] ?>"><?= e($z['name_ar']) ?></button>
        <?php endforeach; ?>
      </div>

      <label class="switch-row tiny">
        <input type="checkbox" data-f="live"><span class="sw"></span>
        <span><b>المتاحين بس</b></span>
      </label>
    </div>

    <div class="cards" data-list>
      <?php foreach ($all as $p) echo provider_card($p); ?>
    </div>

    <?php if (!$all): ?>
      <?= empty_state('لسه مفيش سواقين', 'الدليل لسه بيتملّى. لو انت سائق، سجّل وابدأ.', 'tuktuk', 'سجّل كسائق', url('register/driver')) ?>
    <?php endif; ?>
    <p class="noresult" data-noresult hidden><?= icon('search', 22) ?> مفيش نتايج بالفلاتر دي.</p>
  </section>

  <?= site_footer() ?>
</main>
<?php
layout('', (string) ob_get_clean(), ['nav' => 'home', 'bar_title' => '']);
