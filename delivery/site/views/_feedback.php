<?php
/** نموذج التقييم والبلاغ — مشترك بين صفحة السائق وصفحة المحل. */
$name = $p['display_name'] ?? $p['name_ar'];
$first = explode(' ', trim($name))[0];
?>
<h2 style="display:flex;align-items:center;gap:8px"><?= icon('star', 17) ?> قيّم <?= e($first) ?></h2>
<form class="card" method="post">
  <input type="hidden" name="what" value="rate">
  <label class="field" for="stars"><span>التقييم</span>
    <select id="stars" name="stars">
      <option value="5">٥ — ممتاز</option><option value="4">٤ — كويس</option>
      <option value="3">٣ — عادي</option><option value="2">٢ — ضعيف</option>
      <option value="1">١ — وحش</option></select></label>
  <label class="field" for="comment"><span>رأيك (اختياري)</span>
    <textarea id="comment" name="comment" maxlength="320" placeholder="جه بسرعة والمعاملة كويسة"></textarea></label>
  <label class="field" for="author_name"><span>اسمك (اختياري)</span>
    <input id="author_name" name="author_name" maxlength="40" placeholder="هيظهر جنب رأيك"></label>
  <button class="btn call wide" type="submit">ابعت رأيك</button>
</form>

<h2 style="display:flex;align-items:center;gap:8px"><?= icon('alert', 17) ?> بلّغ عن مشكلة</h2>
<p class="lede">البلاغ بيوصل للإدارة بس، ومحدش تاني بيشوفه.</p>
<form class="card" method="post">
  <input type="hidden" name="what" value="report">
  <label class="field" for="reason"><span>إيه اللي حصل؟</span>
    <select id="reason" name="reason">
      <?php foreach (REPORT_REASONS as $k => $v): ?>
        <option value="<?= e($k) ?>"><?= e($v) ?></option>
      <?php endforeach; ?></select></label>
  <label class="field" for="details"><span>تفاصيل (اختياري)</span>
    <textarea id="details" name="details" maxlength="500"></textarea></label>
  <label class="field" for="reporter_phone"><span>رقمك لو محتاجين نكلمك (اختياري)</span>
    <input id="reporter_phone" name="reporter_phone" type="tel" inputmode="tel" placeholder="01xxxxxxxxx"></label>
  <button class="btn danger wide" type="submit">ابعت البلاغ</button>
</form>
