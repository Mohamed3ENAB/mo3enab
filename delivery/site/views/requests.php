<?php
declare(strict_types=1);

$u   = current_user();
$err = null;

if (is_post() && post('do') === 'new') {
    $body  = post('body', 500);
    $kind  = post('kind', 20);
    $zone  = (int) post('zone_id', 6);
    $phone = $u ? $u['phone'] : preg_replace('/\D/', '', post('phone', 20));
    $hide  = isset($_POST['hide_phone']) ? 1 : 0;

    if (mb_strlen($body) < 8)              $err = 'اكتب طلبك بوضوح شوية.';
    elseif ($kind && !isset(SERVICES[$kind])) $err = 'اختار نوع الطلب.';
    elseif (!valid_phone($phone))          $err = 'اكتب رقم موبايل صح يبدأ بصفر.';
    else {
        $token = new_id();
        q('INSERT INTO requests (id, user_id, zone_id, kind, body, contact_phone, hide_phone, owner_token, expires_at)
           VALUES (?,?,?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL 12 HOUR))',
          [new_id(), $u['id'] ?? null, $zone ?: null, $kind ?: null, $body, $phone, $hide, $token]);
        set_flash('ok', 'طلبك اتنشر. السواقين هيشوفوه.');
        redirect('t/' . $token);
    }
}

$list  = open_requests();
$zones = all_zones();

ob_start(); ?>
<main class="wrap page" id="main">
  <?= page_head('لوحة الطلبات', 'اكتب اللي محتاجه، والسواقين المتاحين يشوفوه ويردّوا عليك.', 'board') ?>

  <?= $err ? '<p class="msg bad">' . icon('alert', 16) . ' ' . e($err) . '</p>' : '' ?>

  <details class="card accent-a askbox" <?= $err ? 'open' : '' ?>>
    <summary class="ask-sum"><span class="ask-ic"><?= icon('plus', 20) ?></span>
      <span><b>اطلب دلوقتي</b><i>سواق، توك توك، أو نقل بضاعة</i></span>
    </summary>

    <form method="post" class="form tight">
      <?= csrf_field() ?><input type="hidden" name="do" value="new">

      <span class="lbl">محتاج إيه؟</span>
      <div class="pickchips radio small">
        <?php foreach (SERVICES as $k => $label): ?>
          <label class="pchip <?= bubble_class($k) ?>">
            <input type="radio" name="kind" value="<?= e($k) ?>" <?= $k === 'delivery' ? 'checked' : '' ?>>
            <span><?= icon($k, 17) ?> <?= e($label) ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <?= field('تفاصيل الطلب',
          '<textarea name="body" rows="3" maxlength="500" required placeholder="مثال: محتاج توك توك من القيصرية للمحلة الساعة ٦."></textarea>', '', true) ?>
      <?= field('المنطقة', zones_select('zone_id', (int) ($u['zone_id'] ?? 0), 'اختار المنطقة', false)) ?>

      <?php if ($u): ?>
        <p class="muted small"><?= icon('user', 13) ?> الطلب هيتنشر باسمك، ورقمك <?= e($u['phone']) ?>.</p>
      <?php else: ?>
        <?= field('رقمك', '<input type="tel" name="phone" inputmode="numeric" required placeholder="01xxxxxxxxx">',
              'محتاجينه عشان السائق يوصلك.', true) ?>
      <?php endif; ?>

      <label class="switch-row">
        <input type="checkbox" name="hide_phone" <?= $u ? ((int) $u['hide_phone'] ? 'checked' : '') : 'checked' ?>>
        <span class="sw"></span>
        <span><b>اخفي رقمي</b><i>هيبان مقنّع، والسواقين يكلّموك من جوه التطبيق.</i></span>
      </label>

      <button class="btn call wide" data-sfx="ok"><?= icon('send', 17) ?> انشر الطلب</button>
      <p class="muted small center">الطلب بيختفي لوحده بعد ١٢ ساعة.</p>
    </form>
  </details>

  <?php if (!$list): ?>
    <?= empty_state('مفيش طلبات مفتوحة', 'كن أول واحد يكتب طلب.', 'board') ?>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($list as $r) echo request_card($r); ?>
    </div>
  <?php endif; ?>

  <?php if (!$u): ?>
    <section class="joincta card glass">
      <div><h3><?= icon('bolt', 18) ?> بحساب بتتابع طلبك أحسن</h3>
      <p class="muted small">هتشوف مين استلم الطلب، وتقفله، وتقيّم السائق.</p></div>
      <a class="btn call sm" href="<?= url('register/customer') ?>">اعمل حساب</a>
    </section>
  <?php endif; ?>

  <?= site_footer() ?>
</main>
<?php
layout('الطلبات', (string) ob_get_clean(), ['nav' => 'requests']);
