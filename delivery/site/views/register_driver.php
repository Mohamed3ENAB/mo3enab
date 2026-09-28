<?php
declare(strict_types=1);

if (is_logged_in()) redirect(dash_path());

$errors = [];
if (is_post()) {
    $b = read_signup_basics();
    $errors = $b['errors'];

    $services = array_values(array_intersect(
        array_keys(SERVICES),
        array_map('strval', (array) ($_POST['services'] ?? []))
    ));
    if (!$services) $errors[] = 'اختار الخدمات اللي بتشتغلها (واحدة على الأقل).';

    $files = ['avatar' => null, 'docs' => []];
    if (!$errors) {
        // الصورة والبطاقة والرخصة — التلاتة مطلوبين للسائق
        $files = read_signup_files(['national_id', 'license']);
        $errors = array_merge($errors, $files['errors']);
    }

    if (!$errors) {
        $d = $b['data'];
        $userId = create_account('driver', $d, $files['avatar'], 'pending');

        // ملف السائق بيتعمل من دلوقتي بس مقفول لحد ما الإدارة توافق
        $provId = new_id();
        q('INSERT INTO providers
             (id, user_id, display_name, phone, whatsapp, zone_id, services, vehicle_note, note,
              photo_id, is_active, token)
           VALUES (?,?,?,?,?,?,?,?,?,?,0,?)',
          [$provId, $userId, $d['name'], $d['phone'], post('whatsapp', 20) ?: null, $d['zone_id'],
           implode(',', $services), post('vehicle_note', 60) ?: null, post('note', 160) ?: null,
           $files['avatar'], new_id()]);
        q('UPDATE users SET provider_id = ? WHERE id = ?', [$provId, $userId]);

        foreach ($files['docs'] as $kind => $imgId) attach_doc($userId, $kind, $imgId);

        // رخصة المركبة اختيارية
        $vl = store_doc('vehicle_license');
        if (!empty($vl['id'])) attach_doc($userId, 'vehicle_license', $vl['id']);

        notify($userId, 'طلبك وصلنا ✅',
               'الإدارة هتراجع بياناتك وصورك. أول ما نوافق هتقدر تفتح التوفر وتستلم طلبات.',
               url('dashboard/pending'), 'clock');
        notify_admins('سائق جديد طالب يشتغل', $d['name'] . ' — ' . $d['phone'], url('admin/approvals'), 'tuktuk');

        login_as($userId);
        set_flash('ok', 'استلمنا طلبك. الإدارة هتراجعه قريب.');
        redirect('dashboard/pending');
    }
}

$old  = fn(string $k) => e((string) ($_POST[$k] ?? ''));
$sSel = (array) ($_POST['services'] ?? []);

ob_start(); ?>
<main class="wrap auth wide-auth" id="main">
  <div class="auth-art" aria-hidden="true"><span class="orb o1"></span><span class="orb o3"></span></div>

  <div class="auth-card card glass pop">
    <span class="step-badge c-drv"><?= icon('tuktuk', 18) ?> تسجيل سائق</span>
    <h1>سجّل كسائق</h1>
    <p class="lede">املا البيانات وارفع صورك. الإدارة بتراجع الطلب قبل ما اسمك يظهر في الدليل.</p>

    <?= errors_block($errors) ?>

    <form method="post" class="form wizard" enctype="multipart/form-data" novalidate data-wizard>
      <?= csrf_field() ?>

      <ol class="steps" aria-hidden="true">
        <li class="on"><i>١</i> بياناتك</li>
        <li><i>٢</i> شغلك</li>
        <li><i>٣</i> صورك</li>
      </ol>

      <!-- ١ — البيانات -->
      <fieldset data-step="1">
        <?= avatar_field('photo', null, 'صورة الحساب', true) ?>
        <?= field('الاسم', '<input type="text" name="name" maxlength="80" required placeholder="اسمك زي ما هو في البطاقة" value="' . $old('name') . '">', '', true) ?>
        <?= field('رقم الموبايل', '<input type="tel" name="phone" inputmode="numeric" required placeholder="01xxxxxxxxx" value="' . $old('phone') . '">', 'دا رقم الدخول، والناس هتشوفه عشان تكلّمك.', true) ?>
        <?= field('رقم واتساب', '<input type="tel" name="whatsapp" inputmode="numeric" placeholder="لو مختلف عن رقمك" value="' . $old('whatsapp') . '">') ?>
        <?= field('المنطقة', zones_select('zone_id', (int) ($_POST['zone_id'] ?? 0)), 'المنطقة اللي بتشتغل فيها أكتر.', true) ?>
        <div class="two">
          <?= field('كلمة السر', password_field('password', '٨ حروف على الأقل'), '', true) ?>
          <?= field('تأكيد كلمة السر', password_field('password2', 'اكتبها تاني'), '', true) ?>
        </div>
        <div class="wz-nav"><button type="button" class="btn call" data-wz-next>التالي <?= icon('back', 16) ?></button></div>
      </fieldset>

      <!-- ٢ — الشغل -->
      <fieldset data-step="2">
        <span class="lbl">بتشتغل إيه؟ <i class="req">*</i></span>
        <div class="pickchips">
          <?php foreach (SERVICES as $k => $label): ?>
            <label class="pchip <?= bubble_class($k) ?>">
              <input type="checkbox" name="services[]" value="<?= e($k) ?>" <?= in_array($k, $sSel, true) ? 'checked' : '' ?>>
              <span><?= icon($k, 18) ?> <?= e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <?= field('العربية أو المركبة', '<input type="text" name="vehicle_note" maxlength="60" placeholder="مثال: توك توك أحمر · سوزوكي نص نقل" value="' . $old('vehicle_note') . '">') ?>
        <?= field('كلمة عنك', '<textarea name="note" maxlength="160" rows="2" placeholder="مثال: متاح من ٧ الصبح لـ ١١ بالليل، وبوصّل للمحلة.">' . $old('note') . '</textarea>') ?>
        <div class="wz-nav">
          <button type="button" class="btn ghost" data-wz-prev>السابق</button>
          <button type="button" class="btn call" data-wz-next>التالي <?= icon('back', 16) ?></button>
        </div>
      </fieldset>

      <!-- ٣ — المستندات -->
      <fieldset data-step="3">
        <div class="note-box">
          <?= icon('shield', 18) ?>
          <div><b>صورك دي محدش بيشوفها غير الإدارة.</b>
          <span>مش بتظهر في صفحتك ولا لأي مستخدم تاني، وبتتحفظ مقفولة. بنطلبها عشان نحطّ علامة «موثّق» جنب اسمك.</span></div>
        </div>

        <?= doc_field('national_id', 'صورة البطاقة الشخصية',
              'صوّر البطاقة من الوجهين لو تقدر، والكلام يبان واضح. صور بس — مش ملفات PDF.', null, true) ?>
        <?= doc_field('license', 'صورة رخصة القيادة',
              'رخصة سارية. لو توك توك أو عجلة ومعندكش رخصة قيادة، ارفع البطاقة تاني واكتبلنا في «كلمة عنك».', null, true) ?>
        <?= doc_field('vehicle_license', 'صورة رخصة المركبة (اختيارية)',
              'بتخلّي التوثيق أسرع.') ?>

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
layout('تسجيل سائق', (string) ob_get_clean(), ['nav' => 'login']);
