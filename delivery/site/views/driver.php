<?php
declare(strict_types=1);

/**
 * لينك سريع للسائق: /d/{token}
 * بيفتح ويقفل التوفر من غير تسجيل دخول — للسواقين اللي بيفضّلوا
 * لينك محفوظ على الشاشة الرئيسية بدل ما يدخلوا بكلمة سر.
 */

$p = $token ? provider_by_token($token) : null;
if (!$p) { http_response_code(404); layout('مش موجود',
    '<main class="wrap page" id="main">' . empty_state('اللينك دا مش شغال', 'كلّم الإدارة عشان تبعتلك لينك جديد.', 'search', 'الصفحة الرئيسية', url('')) . '</main>',
    ['nav' => 'home']); return; }

$msg = null;
if (is_post()) {
    if (post('do') === 'avail') {
        $on = post('v', 3) === '1';
        q('UPDATE providers SET is_available = ?, available_at = ' . ($on ? 'NOW()' : 'available_at') . ' WHERE id = ?',
          [$on ? 1 : 0, $p['id']]);
        $msg = $on ? 'تمام — اسمك دلوقتي في المتاحين.' : 'قفلت التوفر.';
    } elseif (post('do') === 'take') {
        $rid = post('rid', 32);
        $req = one_request($rid);
        if ($req && $req['status'] === 'open') {
            q("UPDATE requests SET status = 'taken', taken_by = ?, taken_at = NOW() WHERE id = ? AND status = 'open'",
              [$p['id'], $rid]);
            if ($req['user_id']) {
                notify($req['user_id'], 'سائق استلم طلبك 🚗', $p['display_name'] . ' — ' . $p['phone'],
                       url('dashboard/customer'), 'tuktuk');
            }
            $msg = 'استلمت الطلب. كلّم صاحبه واتفقوا.';
        } else {
            $msg = 'الطلب دا اتاخد أو اتقفل.';
        }
    }
    $p = provider_by_token($token);
}

$live = is_live($p);
$feed = open_requests();

ob_start(); ?>
<main class="wrap page" id="main">

  <section class="profile card glass <?= $live ? 'is-live' : '' ?>">
    <div class="pf-top">
      <?= avatar($p['display_name'], $p['photo_id'], (bool) $p['is_verified'], 72) ?>
      <div class="pf-id">
        <h1><?= e($p['display_name']) ?></h1>
        <p class="muted"><?= icon('pin', 13) ?> <?= e($p['zone_name']) ?></p>
        <?= state_pill($live, $p['available_at']) ?>
      </div>
    </div>

    <?= flash($msg, null) ?>

    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="do" value="avail">
      <input type="hidden" name="v" value="<?= $live ? '0' : '1' ?>">
      <button class="btn <?= $live ? 'ghost' : 'call' ?> wide big" data-sfx="<?= $live ? 'tap' : 'ok' ?>">
        <?= icon($live ? 'sleep' : 'bolt', 20) ?> <?= $live ? 'اقفل التوفر' : 'افتح التوفر' ?>
      </button>
    </form>
    <p class="muted small center">التوفر بينتهي لوحده بعد <?= AVAILABILITY_HOURS ?> ساعات عشان القايمة تفضل صادقة.</p>

    <?php if ($p['user_id']): ?>
      <a class="linkbtn center" href="<?= url('login') ?>">عندك حساب — ادخل عليه لكل المزايا</a>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2><?= icon('board', 18) ?> طلبات مفتوحة</h2>
    <?php if (!$feed): ?>
      <?= empty_state('مفيش طلبات دلوقتي', 'سيب التوفر مفتوح وهتوصلك.', 'board') ?>
    <?php else: ?>
      <div class="cards">
        <?php foreach ($feed as $r) echo request_card($r, true, $p, url('d/' . e($token))); ?>
      </div>
    <?php endif; ?>
  </section>

  <?= site_footer() ?>
</main>
<?php
layout($p['display_name'], (string) ob_get_clean(), ['nav' => 'home']);
