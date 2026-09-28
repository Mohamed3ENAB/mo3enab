<?php
declare(strict_types=1);
/** @var string $ptoken @var string $reqId */
$me = provider_by_token($ptoken);
$r  = $me ? row('SELECT id, body, expires_at FROM requests WHERE id = ? AND is_hidden = 0', [$reqId]) : null;
if (!$me || !$r) { http_response_code(404); layout('مش موجودة',
  '<main class="wrap page"><h1>المحادثة مش موجودة</h1><p class="lede">اتأكد من اللينك.</p></main>'); exit; }

$expired = strtotime($r['expires_at']) < time();
$bad = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$expired) {
    $body = post('body', 1000);
    if ($body === '') $bad = 'اكتب رسالة الأول.';
    else {
        q('INSERT INTO messages (request_id, from_owner, provider_id, body) VALUES (?, 0, ?, ?)',
          [$r['id'], $me['id'], $body]);
        redirect('t/p/' . $ptoken . '/' . $reqId);
    }
}
$msgs = messages_for($r['id']);

ob_start(); ?>
<main class="wrap page">
  <a class="linkish" href="<?= url('d/' . e($ptoken)) ?>" style="margin-bottom:12px">
    <?= icon('home', 16) ?> رجوع لصفحتك</a>
  <h1 style="display:flex;align-items:center;gap:9px"><?= icon('chat', 22) ?> محادثة على طلب</h1>
  <p class="lede">«<?= e($r['body']) ?>»</p>
  <?= flash(null, $bad) ?>

  <?php if (!$msgs): ?>
    <p class="chat-empty"><?= icon('chat', 26) ?><br>لسه مفيش رسايل. ابدأ الكلام.</p>
  <?php else: ?>
    <div class="chat"><?php foreach ($msgs as $m): ?>
      <div class="bubble-msg <?= $m['from_owner'] ? 'theirs' : 'mine' ?>">
        <?= $m['from_owner'] ? '<span class="who">صاحب الطلب</span>' : '' ?>
        <?= e($m['body']) ?><span class="at"><?= e(since_ar($m['created_at'])) ?></span></div>
    <?php endforeach; ?></div>
  <?php endif; ?>

  <?php if ($expired): ?>
    <p class="lede">الطلب انتهى والمحادثة اتقفلت.</p>
  <?php else: ?>
    <form method="post" style="display:flex;gap:9px;align-items:flex-end">
      <label class="field" style="flex:1;margin:0">
        <textarea name="body" rows="2" maxlength="1000" placeholder="اكتب رسالتك"
          style="min-height:56px" aria-label="رسالتك" required></textarea></label>
      <button class="btn call" type="submit" aria-label="ابعت"><?= icon('send', 20) ?></button>
    </form>
  <?php endif; ?>
</main>
<?php
layout('محادثة على طلب', (string) ob_get_clean());
