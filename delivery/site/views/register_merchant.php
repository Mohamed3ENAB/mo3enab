<?php
declare(strict_types=1);

if (is_logged_in()) redirect(dash_path());

$errors = [];
if (is_post()) {
    $b = read_signup_basics();
    $errors = $b['errors'];

    $shopName = post('shop_name', 80);
    $category = post('category', 20);
    if (mb_strlen($shopName) < 2)             $errors[] = 'اكتب اسم المحل.';
    if (!isset(CATEGORIES[$category]))        $errors[] = 'اختار نوع المحل.';

    $files = ['avatar' => null, 'docs' => []];
    if (!$errors) {
        // صورة المحل + بطاقة صاحبه
        $files = read_signup_files(['national_id']);
        $errors = array_merge($errors, $files['errors']);
    }

    if (!$errors) {
        $d = $b['data'];
        $userId = create_account('merchant', $d, $files['avatar'], 'pending');

        $placeId = new_id();
        q('INSERT INTO places
             (id, user_id, name_ar, category, zone_id, phone, whatsapp, address_note, hours_note, note,
              photo_id, is_active)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,0)',
          [$placeId, $userId, $shopName, $category, $d['zone_id'], $d['phone'],
           post('whatsapp', 20) ?: null, post('address_note', 140) ?: null,
           post('hours_note', 60) ?: null, post('note', 160) ?: null, $files['avatar']]);
        q('UPDATE users SET place_id = ? WHERE id = ?', [$placeId, $userId]);

        foreach ($files['docs'] as $kind => $imgId) attach_doc($userId, $kind, $imgId);

        $sd = store_doc('shop_doc');
        if (!empty($sd['id'])) attach_doc($userId, 'shop_doc', $sd['id']);

        notify($userId, 'طلبك وصلنا ✅',
               'الإدارة هتراجع بيانات المحل. أول ما نوافق هيظهر في الدليل.',
               url('dashboard/pending'), 'clock');
        notify_admins('محل جديد طالب ينضم', $shopName . ' — ' . $d['phone'], url('admin/approvals'), 'store');

        login_as($userId);
        set_flash('ok', 'استلمنا طلبك. الإدارة هتراجعه قريب.');
        redirect('dashboard/pending');
    }
}

$old = fn(string $k) => e((string) ($_POST[$k] ?? ''));
$cat = (string) ($_POST['category'] ?? '');

ob_start(); ?>
<main class="wrap auth wide-auth" id="main">
  <div class="auth-art" aria-hidden="true"><span class="orb o2"></span><span class="orb o3"></span></div>

  <div class="auth-card card glass pop">
    <span class="step-badge c-shop"><?= icon('store', 18) ?> تسجيل محل</span>
    <h1>حط محلك في الدليل</h1>
    <p class="lede">صورة ومواعيد ورقم — والناس تلاقيك.</p>

    <?= errors_block($errors) ?>

    <form method="post" class="form wizard" enctype="multipart/form-data" novalidate data-wizard>
      <?= csrf_field() ?>

      <ol class="steps" aria-hidden="true">
        <li class="on"><i>١</i> المحل</li>
        <li><i>٢</i> حسابك</li>
        <li><i>٣</i> صورك</li>
      </ol>

      <fieldset data-step="1">
        <?= avatar_field('photo', null, 'صورة المحل أو اللوجو', true) ?>
        <?= field('اسم المحل', '<input type="text" name="shop_name" maxlength="80" required placeholder="مثال: مطعم البركة" value="' . $old('shop_name') . '">', '', true) ?>
        <span class="lbl">نوع المحل <i class="req">*</i></span>
        <div class="pickchips radio">
          <?php foreach (CATEGORIES as $k => $label): ?>
            <label class="pchip">
              <input type="radio" name="category" value="<?= e($k) ?>" <?= $cat === $k ? 'checked' : '' ?> required>
              <span><?= icon($k, 18) ?> <?= e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <?= field('المنطقة', zones_select('zone_id', (int) ($_POST['zone_id'] ?? 0)), '', true) ?>
        <?= field('العنوان', '<input type="text" name="address_note" maxlength="140" placeholder="مثال: شارع المحطة، جنب الجامع" value="' . $old('address_note') . '">') ?>
        <?= field('المواعيد', '<input type="text" name="hours_note" maxlength="60" placeholder="مثال: من ٩ الصبح لـ ١٢ بالليل" value="' . $old('hours_note') . '">') ?>
        <?= field('كلمة عن المحل', '<textarea name="note" maxlength="160" rows="2" placeholder="مثال: بنوصّل لحد البيت في القيصرية.">' . $old('note') . '</textarea>') ?>
        <div class="wz-nav"><button type="button" class="btn call" data-wz-next>التالي <?= icon('back', 16) ?></button></div>
      </fieldset>

      <fieldset data-step="2">
        <?= field('اسم صاحب المحل', '<input type="text" name="name" maxlength="80" required placeholder="اسمك بالكامل" value="' . $old('name') . '">', '', true) ?>
        <?= field('رقم الموبايل', '<input type="tel" name="phone" inputmode="numeric" required placeholder="01xxxxxxxxx" value="' . $old('phone') . '">', 'دا رقم الدخول ورقم المحل في الدليل.', true) ?>
        <?= field('رقم واتساب', '<input type="tel" name="whatsapp" inputmode="numeric" placeholder="لو مختلف" value="' . $old('whatsapp') . '">') ?>
        <div class="two">
          <?= field('كلمة السر', password_field('password', '٨ حروف على الأقل'), '', true) ?>
          <?= field('تأكيد كلمة السر', password_field('password2', 'اكتبها تاني'), '', true) ?>
        </div>
        <div class="wz-nav">
          <button type="button" class="btn ghost" data-wz-prev>السابق</button>
          <button type="button" class="btn call" data-wz-next>التالي <?= icon('back', 16) ?></button>
        </div>
      </fieldset>

      <fieldset data-step="3">
        <div class="note-box">
          <?= icon('shield', 18) ?>
          <div><b>صور المستندات للإدارة بس.</b>
          <span>مش بتظهر في صفحة المحل ولا لأي حد تاني.</span></div>
        </div>
        <?= doc_field('national_id', 'صورة بطاقة صاحب المحل',
              'عشان نتأكد إن المحل بتاعك فعلًا. صور بس — مش ملفات.', null, true) ?>
        <?= doc_field('shop_doc', 'ورقة تثبت المحل (اختيارية)',
              'سجل تجاري أو رخصة أو حتى إيصال كهربا باسم المحل.') ?>
        <div class="wz-nav">
          <button type="button" class="btn ghost" data-wz-prev>السابق</button>
          <button class="btn call big" data-sfx="ok"><?= icon('send', 18) ?> ابعت الطلب</button>
        </div>
      </fieldset>
    </form>

    <div class="auth-alt"><a class="linkbtn" href="<?= url('register') ?>">نوع حساب تاني</a></div>
  </div>
</main>
<?php
layout('تسجيل محل', (string) ob_get_clean(), ['nav' => 'login']);
