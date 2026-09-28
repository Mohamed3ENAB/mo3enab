<?php
declare(strict_types=1);

$r = $token ? request_by_token($token) : null;
if (!$r) { http_response_code(404); layout('مش موجود',
    '<main class="wrap page" id="main">' . empty_state('اللينك دا مش شغال', 'يمكن الطلب انتهى أو اتلغى.', 'search', 'لوحة الطلبات', url('requests')) . '</main>',
    ['nav' => 'requests']); return; }

$msg = null; $err = null;

if (is_post()) {
    switch (post('do')) {
        case 'say':
            $body = post('body', 1000);
            if (mb_strlen($body) < 1) { $err = 'اكتب حاجة الأول.'; break; }
            q('INSERT INTO messages (request_id, from_owner, body) VALUES (?,1,?)', [$r['id'], $body]);
            if ($r['taken_by']) {
                $pr = row('SELECT user_id FROM providers WHERE id = ?', [$r['taken_by']]);
                if ($pr && $pr['user_id']) notify($pr['user_id'], 'رد جديد على طلب استلمته', mb_substr($body, 0, 80), url('dashboard/driver'), 'chat');
            }
            $msg = 'رسالتك اتبعتت.';
            break;

        case 'close':
            q("UPDATE requests SET status = 'cancelled' WHERE id = ?", [$r['id']]);
            $msg = 'الطلب اتقفل.';
            break;
    }
    $r = request_by_token($token);
}

$msgs = messages_for($r['id']);
$live = $r['expires_at'] > date('Y-m-d H:i:s') && $r['status'] === 'open';

ob_start(); ?>
<main class="wrap page" id="main">
  <?= page_head('طلبك والردود', 'اللينك دا بتاعك — احتفظ بيه عشان تشوف الردود.', 'chat') ?>
  <?= flash($msg, $err) ?>

  <section class="card">
    <div class="req-top">
      <span class="chip <?= bubble_class((string) $r['kind']) ?>"><?= icon((string) $r['kind'], 14) ?> <?= e(SERVICES[$r['kind']] ?? 'طلب') ?></span>
      <?= status_badge($r['status']) ?>
      <span class="muted time"><?= e(since_ar($r['created_at'])) ?></span>
    </div>
    <p class="req-body"><?= nl2br(e($r['body'])) ?></p>
    <p class="muted small">
      <?= icon('lock', 13) ?>
      <?= (int) $r['hide_phone'] ? 'رقمك مخفي — بيبان ' . e(mask_phone($r['contact_phone'])) : 'رقمك ظاهر للسواقين' ?>
    </p>
    <?php if ($live): ?>
      <form method="post" class="inline" data-confirm="تقفل الطلب؟"><?= csrf_field() ?>
        <input type="hidden" name="do" value="close">
        <button class="btn ghost sm danger"><?= icon('close', 15) ?> اقفل الطلب</button>
      </form>
    <?php endif; ?>
  </section>

  <section class="card chatcard">
    <h2><?= icon('chat', 18) ?> الردود (<?= count($msgs) ?>)</h2>

    <?php if (!$msgs): ?>
      <?= empty_state('لسه مفيش ردود', 'السواقين المتاحين بيشوفوا الطلب. استنى شوية.', 'chat') ?>
    <?php else: ?>
      <ul class="chat">
        <?php foreach ($msgs as $m): ?>
          <li class="<?= (int) $m['from_owner'] ? 'me' : 'them' ?>">
            <?php if (!(int) $m['from_owner']): ?>
              <span class="ch-who"><?= avatar((string) ($m['sender_name'] ?: 'سائق'), $m['photo_id'], false, 26) ?> <?= e($m['sender_name'] ?: 'سائق') ?></span>
            <?php endif; ?>
            <p><?= nl2br(e($m['body'])) ?></p>
            <i class="muted"><?= e(since_ar($m['created_at'])) ?></i>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($live || $r['status'] === 'taken'): ?>
      <form method="post" class="chatbar">
        <?= csrf_field() ?><input type="hidden" name="do" value="say">
        <input type="text" name="body" maxlength="1000" required placeholder="اكتب رسالتك" autocomplete="off">
        <button class="btn call" data-sfx="tap"><?= icon('send', 18) ?></button>
      </form>
    <?php else: ?>
      <p class="muted center pad">الطلب اتقفل، فالمحادثة اتوقفت.</p>
    <?php endif; ?>
  </section>

  <?= site_footer() ?>
</main>
<?php
layout('طلبك', (string) ob_get_clean(), ['nav' => 'requests', 'back' => true]);
