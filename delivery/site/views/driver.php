<?php
declare(strict_types=1);

/** @var string $token */
$me = provider_by_token($token);
if (!$me) { http_response_code(404); layout('لينك مش صح',
    '<main class="wrap page"><h1>اللينك مش صح</h1>'
  . '<p class="lede">اللينك ده مش شغال، أو الحساب موقوف. كلّم الإدارة.</p></main>'); exit; }

$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $next = is_live($me) ? 0 : 1;
    q('UPDATE providers SET is_available = ?, available_at = NOW() WHERE id = ?', [$next, $me['id']]);
    $me = provider_by_token($token);
    $msg = $next ? 'تمام. اسمك ظاهر دلوقتي في الدليل.' : 'تمام. اسمك اتشال من المتاحين.';
}

$live = is_live($me);
$reqs = open_requests();
$svc  = services_of($me);

ob_start(); ?>
<main class="wrap page">
  <div style="display:flex;align-items:center;gap:13px;margin-bottom:24px">
    <?= avatar($me['display_name'], $me['photo_id'], (bool) $me['is_verified']) ?>
    <div><h1 style="margin:0">أهلاً <?= e(explode(' ', trim($me['display_name']))[0]) ?></h1>
      <p class="lede" style="margin:0;display:flex;align-items:center;gap:5px">
        <?= icon('pin', 13) ?> <?= e($me['zone_name']) ?> ·
        <?= e(implode('، ', array_map(fn($s) => SERVICES[$s] ?? $s, $svc))) ?></p></div>
  </div>

  <?= $msg ? flash($msg, null) : '' ?>

  <form method="post">
    <input type="hidden" name="action" value="toggle">
    <button class="toggle <?= $live ? 'on' : 'off' ?>" type="submit" aria-pressed="<?= $live ? 'true' : 'false' ?>">
      <?= $live ? '<span class="ring"></span>' : '' ?>
      <?= $live ? 'أنا متاح' : 'مش متاح' ?>
      <small><?= $live ? 'اضغط لما تخلص' : 'اضغط لما تبقى فاضي' ?></small>
    </button>
  </form>
  <p class="lede" style="margin-top:16px;text-align:center">آخر تحديث <?= e(since_ar($me['available_at'])) ?></p>

  <div class="note" style="margin-top:20px"><?= icon('info', 19, 'ic') ?>
    <span>بيتقفل لوحده بعد <strong>٤ ساعات</strong> لو نسيت. ده عشان القايمة تفضل صادقة —
      الناس بتبطل تثق في التطبيق لو لقت حد مكتوب إنه متاح وهو مش متاح.</span></div>

  <div class="note warn"><?= icon('alert', 19, 'ic') ?>
    <span><strong>اللينك ده ليك انت بس.</strong> متبعتهوش لحد ومتحطهوش في جروب.
      أي حد معاه اللينك يقدر يفتح ويقفل توفرك.</span></div>

  <h2>طلبات مفتوحة دلوقتي</h2>
  <?php if (!$reqs): ?>
    <p class="lede">مفيش طلبات دلوقتي.</p>
  <?php else: foreach ($reqs as $r): ?>
    <div class="req">
      <p><?= e($r['body']) ?></p>
      <div class="when">
        <?= $r['zone_name'] ? icon('pin', 13) . ' ' . e($r['zone_name']) : '' ?>
        <?= icon('clock', 13) ?> <?= e(since_ar($r['created_at'])) ?>
        <?= $r['msg_count'] ? '<span class="pill info">' . (int) $r['msg_count'] . ' رسالة</span>' : '' ?>
      </div>
      <a class="btn ghost wide" style="margin-top:12px"
         href="<?= url('t/p/' . e($token) . '/' . e($r['id'])) ?>"><?= icon('chat', 18) ?> رد داخل التطبيق</a>
    </div>
  <?php endforeach; endif; ?>
</main>
<?php
layout('صفحتك', (string) ob_get_clean());
