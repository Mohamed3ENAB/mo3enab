<?php
declare(strict_types=1);

$p = $ptoken ? provider_by_token($ptoken) : null;
$r = $reqId ? one_request($reqId) : null;
if (!$p || !$r) { http_response_code(404); layout('مش موجود',
    '<main class="wrap page" id="main">' . empty_state('اللينك دا مش شغال', 'يمكن الطلب انتهى.', 'search', 'لوحة الطلبات', url('requests')) . '</main>',
    ['nav' => 'requests']); return; }

$msg = null; $err = null;

if (is_post() && post('do') === 'say') {
    $body = post('body', 1000);
    if (mb_strlen($body) < 1) {
        $err = 'اكتب حاجة الأول.';
    } else {
        q('INSERT INTO messages (request_id, from_owner, provider_id, body) VALUES (?,0,?,?)',
          [$r['id'], $p['id'], $body]);
        if ($r['user_id']) {
            notify($r['user_id'], 'رد جديد على طلبك 💬',
                   $p['display_name'] . ': ' . mb_substr($body, 0, 60), url('t/' . $r['owner_token']), 'chat');
        }
        $msg = 'رسالتك اتبعتت لصاحب الطلب.';
    }
    $r = one_request($reqId);
}

$msgs = messages_for($r['id']);
$open = $r['status'] === 'open' || ($r['status'] === 'taken' && $r['taken_by'] === $p['id']);

ob_start(); ?>
<main class="wrap page" id="main">
  <?= page_head('رد على الطلب', 'بتكلّم صاحب الطلب من جوه التطبيق — رقمه محفوظ.', 'chat') ?>
  <?= flash($msg, $err) ?>

  <section class="card">
    <div class="req-top">
      <span class="chip <?= bubble_class((string) $r['kind']) ?>"><?= icon((string) $r['kind'], 14) ?> <?= e(SERVICES[$r['kind']] ?? 'طلب') ?></span>
      <?= status_badge($r['status']) ?>
      <span class="muted time"><?= e(since_ar($r['created_at'])) ?></span>
    </div>
    <p class="req-body"><?= nl2br(e($r['body'])) ?></p>
    <?php if (!(int) $r['hide_phone']): ?>
      <?= tel_box($r['contact_phone'], 'كلّم صاحب الطلب') ?>
    <?php else: ?>
      <div class="masked"><?= icon('lock', 14) ?> الرقم متخفي — <?= e(mask_phone($r['contact_phone'])) ?>. كلّمه من هنا.</div>
    <?php endif; ?>
  </section>

  <section class="card chatcard">
    <h2><?= icon('chat', 18) ?> المحادثة</h2>
    <?php if (!$msgs): ?>
      <?= empty_state('ابدأ الكلام', 'اكتب سعرك ومتى تقدر توصل.', 'send') ?>
    <?php else: ?>
      <ul class="chat">
        <?php foreach ($msgs as $m): $mine = !(int) $m['from_owner']; ?>
          <li class="<?= $mine ? 'me' : 'them' ?>">
            <?php if (!$mine): ?><span class="ch-who"><?= icon('user', 14) ?> صاحب الطلب</span><?php endif; ?>
            <p><?= nl2br(e($m['body'])) ?></p>
            <i class="muted"><?= e(since_ar($m['created_at'])) ?></i>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($open): ?>
      <form method="post" class="chatbar">
        <?= csrf_field() ?><input type="hidden" name="do" value="say">
        <input type="text" name="body" maxlength="1000" required placeholder="مثال: أنا جاهز، السعر ٤٠ جنيه، أوصل في ١٠ دقايق." autocomplete="off">
        <button class="btn call" data-sfx="tap"><?= icon('send', 18) ?></button>
      </form>
    <?php else: ?>
      <p class="muted center pad">الطلب اتقفل أو سائق تاني استلمه.</p>
    <?php endif; ?>
  </section>

  <?= site_footer() ?>
</main>
<?php
layout('رد على طلب', (string) ob_get_clean(), ['nav' => 'requests', 'back' => true]);
