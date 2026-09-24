<?php
declare(strict_types=1);
/** @var string $token */
$r = row('SELECT id, body, expires_at FROM requests WHERE owner_token = ?', [$token]);
if (!$r) { http_response_code(404); layout('مش موجودة',
  '<main class="wrap page"><h1>المحادثة مش موجودة</h1><p class="lede">اللينك مش صح أو الطلب انتهى.</p></main>'); exit; }

$expired = strtotime($r['expires_at']) < time();
$bad = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$expired) {
    $body = post('body', 1000);
    if ($body === '') $bad = 'اكتب رسالة الأول.';
    else {
        q('INSERT INTO messages (request_id, from_owner, body) VALUES (?, 1, ?)', [$r['id'], $body]);
        redirect('t/' . $token);
    }
}
$msgs = messages_for($r['id']);

ob_start(); ?>
<main class="wrap page">
  <h1 style="display:flex;align-items:center;gap:9px"><?= icon('chat', 22) ?> محادثة طلبك</h1>
  <p class="lede">«<?= e($r['body']) ?>»</p>
  <?= flash(null, $bad) ?>

  <?php if (!$msgs): ?>
    <p class="chat-empty"><?= icon('chat', 26) ?><br>لسه مفيش رسايل. ابدأ الكلام.</p>
  <?php else: ?>
    <div class="chat"><?php foreach ($msgs as $m): ?>
      <div class="bubble-msg <?= $m['from_owner'] ? 'mine' : 'theirs' ?>">
        <?= $m['from_owner'] ? '' : '<span class="who">' . e($m['sender_name'] ?: 'سائق') . '</span>' ?>
        <?= e($m['body']) ?><span class="at"><?= e(since_ar($m['created_at'])) ?></span></div>
    <?php endforeach; ?></div>
  <?php endif; ?>

  <?php if ($expired): ?>
    <p class="lede">الطلب انتهى والمحادثة اتقفلت.</p>
  <?php else: ?>
    <form method="post" style="display:flex;gap:9px;align-items:flex-end">
      <label class="field" style="flex:1;margin:0"><span class="sr-only" hidden>رسالتك</span>
        <textarea name="body" rows="2" maxlength="1000" placeholder="اكتب رسالتك"
          style="min-height:56px" aria-label="رسالتك" required></textarea></label>
      <button class="btn call" type="submit" aria-label="ابعت"><?= icon('send', 20) ?></button>
    </form>
  <?php endif; ?>

  <div class="note warn" style="margin-top:22px"><?= icon('alert', 19, 'ic') ?>
    <span><strong>اللينك ده ليك انت بس.</strong> أي حد معاه يقدر يقرا المحادثة ويرد باسمك.
      والمحادثة بتقفل مع الطلب بعد ٦ ساعات.</span></div>
</main>
<?php
layout('محادثة طلبك', (string) ob_get_clean());
