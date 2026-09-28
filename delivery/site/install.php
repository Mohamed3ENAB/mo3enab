<?php
declare(strict_types=1);
/**
 * التنصيب — بيتفتح مرة واحدة وبعدين بيتمسح.
 * بيشتغل على قاعدة فاضية، وكمان على قاعدة قديمة فيها بيانات
 * من نسخة قبل الحسابات: الجداول الناقصة بتتعمل، والأعمدة
 * الناقصة بتتضاف، والبيانات القديمة بتفضل زي ما هي.
 */

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/auth.php';

$flagFile = __DIR__ . '/.installed';
$steps = [];
$fatal = null;
$done  = false;

/* ── ١. فحص السيرفر ───────────────────────────────────────── */
$checks = [
    'PHP 8.1 أو أحدث'     => version_compare(PHP_VERSION, '8.1.0', '>='),
    'إضافة PDO MySQL'      => extension_loaded('pdo_mysql'),
    'مكتبة الصور GD'       => function_exists('imagecreatefromstring'),
    'تحويل الصور لـ WebP'  => function_exists('imagewebp'),
    'قراءة اتجاه الصورة'   => function_exists('exif_read_data'),
];
$mustHave = ['PHP 8.1 أو أحدث', 'إضافة PDO MySQL', 'مكتبة الصور GD'];
foreach ($mustHave as $k) {
    if (!$checks[$k]) $fatal = 'السيرفر ناقصه: ' . $k . '. كلّم الاستضافة عشان يفعّلوها.';
}

/* ── ٢. الاتصال بقاعدة البيانات ───────────────────────────── */
if (!$fatal) {
    try { db(); $steps[] = 'الاتصال بقاعدة البيانات تمام.'; }
    catch (Throwable $e) { $fatal = 'مقدرناش نوصل لقاعدة البيانات. راجع config.php.'; }
}

$hasAdmin = false;
if (!$fatal) {
    try { $hasAdmin = (bool) val("SELECT COUNT(*) FROM users WHERE role = 'admin'"); }
    catch (Throwable) { $hasAdmin = false; }
}

/* ── ٣. تنفيذ الجداول والترقيات ───────────────────────────── */
function run_schema(array &$steps): void {
    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    if ($sql === false) throw new RuntimeException('ملف sql/schema.sql مش موجود.');

    // 🔴 التعليقات بتتشال الأول. لو سبناها، أي جملة جاية بعد كتلة
    //    تعليق بتبدأ بـ "--" فتتشال معاها ويضيع جدول كامل.
    $clean = preg_replace('/^\s*--.*$/m', '', $sql);

    $n = 0;
    foreach (explode(';', (string) $clean) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;
        db()->exec($stmt);
        $n++;
    }
    $steps[] = "الجداول اتعملت أو اتأكدنا إنها موجودة ($n خطوة).";
}

/** أعمدة اتضافت بعد النسخة الأولى — بتتضاف بس لو ناقصة. */
function run_migrations(array &$steps): void {
    $add = [
        ['images',   'width',      'SMALLINT NOT NULL DEFAULT 0'],
        ['images',   'height',     'SMALLINT NOT NULL DEFAULT 0'],
        ['images',   'is_private', 'TINYINT(1) NOT NULL DEFAULT 0'],
        ['providers','user_id',    'CHAR(32) NULL'],
        ['places',   'user_id',    'CHAR(32) NULL'],
        ['places',   'is_open',    'TINYINT(1) NOT NULL DEFAULT 1'],
        ['requests', 'user_id',    'CHAR(32) NULL'],
        ['requests', 'status',     "ENUM('open','taken','done','cancelled') NOT NULL DEFAULT 'open'"],
        ['requests', 'taken_by',   'CHAR(32) NULL'],
        ['requests', 'taken_at',   'DATETIME NULL'],
        ['messages', 'is_read',    'TINYINT(1) NOT NULL DEFAULT 0'],
        ['ratings',  'user_id',    'CHAR(32) NULL'],
    ];
    $n = 0;
    foreach ($add as [$table, $col, $type]) {
        if (!table_exists($table) || column_exists($table, $col)) continue;
        db()->exec("ALTER TABLE `$table` ADD COLUMN `$col` $type");
        $n++;
    }
    $steps[] = $n ? "اتضاف $n عمود للجداول القديمة." : 'مفيش أعمدة ناقصة.';
}

function seed(array &$steps): void {
    if (!val('SELECT COUNT(*) FROM zones')) {
        $zones = ['القيصرية', 'بطينة', 'محلة أبو علي', 'محلة زياد', 'المحلة الكبرى'];
        foreach ($zones as $i => $z) {
            q('INSERT INTO zones (name_ar, sort_order) VALUES (?, ?)', [$z, $i]);
        }
        $steps[] = 'المناطق الافتراضية اتحطّت (' . count($zones) . ').';
    }
    if (!val('SELECT COUNT(*) FROM vehicle_rates')) {
        $rates = [
            ['tuktuk', 15, 'داخل القرية', 1],
            ['delivery', 20, 'حسب المسافة', 2],
            ['bicycle', 10, 'مشاوير قريبة', 3],
            ['goods', 60, 'حسب الحمولة', 4],
            ['mahalla_run', 70, 'رايح جاي بالاتفاق', 5],
        ];
        foreach ($rates as [$k, $v, $note, $o]) {
            q('INSERT INTO vehicle_rates (kind, starts_from, note_ar, sort_order) VALUES (?,?,?,?)',
              [$k, $v, $note, $o]);
        }
        $steps[] = 'أسعار البداية التقريبية اتحطّت.';
    }
}

/* ── ٤. الشغل الفعلي ──────────────────────────────────────── */
$err = null;
if (!$fatal && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name  = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
    $phone = preg_replace('/\D/', '', (string) ($_POST['phone'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');

    if (mb_strlen($name) < 3)        $err = 'اكتب اسمك.';
    elseif (!valid_phone($phone))    $err = 'الرقم لازم يبدأ بصفر ويكون ١١ رقم.';
    elseif (mb_strlen($pass) < 8)    $err = 'كلمة السر لازم ٨ حروف على الأقل.';
    elseif ($pass !== (string) ($_POST['password2'] ?? '')) $err = 'كلمتين السر مش زي بعض.';

    if (!$err) {
        try {
            run_schema($steps);
            run_migrations($steps);
            seed($steps);

            if (val('SELECT COUNT(*) FROM users WHERE phone = ?', [$phone])) {
                $err = 'الرقم دا مسجّل قبل كده. اختار رقم تاني أو ادخل من صفحة الدخول.';
            } else {
                q('INSERT INTO users (id, role, name, phone, pass_hash, status) VALUES (?,?,?,?,?,?)',
                  [new_id(), 'admin', $name, $phone, password_hash($pass, PASSWORD_DEFAULT), 'active']);
                $steps[] = 'حساب الإدارة اتعمل باسم ' . $name . '.';
                @file_put_contents($flagFile, date('c'));
                $done = true;
            }
        } catch (Throwable $e) {
            error_log('install failed: ' . $e->getMessage());
            $err = 'حصلت مشكلة وإحنا بنجهّز الجداول: ' . $e->getMessage();
        }
    }
}

$installedAlready = $hasAdmin && is_file($flagFile) && !$done;
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تنصيب · في السكة</title>
<link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;600;700&display=swap">
<link rel="stylesheet" href="assets/app.css?v=3">
</head>
<body>
<main class="wrap auth" id="main">
  <div class="auth-art" aria-hidden="true"><span class="orb o1"></span><span class="orb o2"></span></div>

  <div class="auth-card card glass pop">
    <div class="auth-logo">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="4" width="4" height="16" rx="2"/><rect x="10" y="4" width="11" height="3.6" rx="1.8"/><rect x="10" y="10.2" width="8" height="3.6" rx="1.8" opacity=".7"/><rect x="10" y="16.4" width="5" height="3.6" rx="1.8" opacity=".45"/></svg>
    </div>
    <h1>تنصيب «في السكة»</h1>

<?php if ($fatal): ?>
    <p class="msg bad"><?= e($fatal) ?></p>

<?php elseif ($installedAlready): ?>
    <p class="msg ok">التطبيق متنصّب خلاص.</p>
    <div class="note-box">
      <div><b>خطوة أخيرة مهمة: امسح ملف install.php.</b>
      <span>سيبانه مفتوح خطر. امسحه من مدير الملفات في هوستنجر.</span></div>
    </div>
    <a class="btn call wide" href="./login">روح لصفحة الدخول</a>

<?php elseif ($done): ?>
    <p class="msg ok">تمام! التطبيق جاهز.</p>
    <ul class="tips">
      <?php foreach ($steps as $s): ?><li><?= e($s) ?></li><?php endforeach; ?>
    </ul>
    <div class="note-box">
      <div><b>🔴 امسح ملف install.php دلوقتي.</b>
      <span>من مدير الملفات في هوستنجر. سيبانه معناه إن أي حد يقدر يفتحه.</span></div>
    </div>
    <a class="btn call wide big" href="./login">ادخل بحساب الإدارة</a>

<?php else: ?>
    <p class="lede">آخر خطوة: اعمل حساب الإدارة. الجداول كلها هتتعمل لوحدها.</p>

    <ul class="doclist">
      <?php foreach ($checks as $label => $ok): ?>
        <li>
          <span class="dl-ic" style="color:<?= $ok ? 'var(--good)' : 'var(--warn)' ?>">
            <?= $ok ? '✓' : '!' ?>
          </span>
          <div class="dl-main"><b><?= e($label) ?></b>
            <span class="muted small"><?= $ok ? 'موجودة' : (in_array($label, $mustHave, true) ? 'ناقصة — لازم تتفعّل' : 'مش موجودة — التطبيق هيشتغل بدونها') ?></span>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>

    <?= $err ? '<p class="msg bad">' . e($err) . '</p>' : '' ?>

    <form method="post" class="form">
      <label class="fld"><span class="lbl">اسمك</span>
        <input type="text" name="name" required maxlength="80" placeholder="اسم مدير التطبيق"
               value="<?= e((string) ($_POST['name'] ?? '')) ?>"></label>
      <label class="fld"><span class="lbl">رقم الموبايل (هو اسم الدخول)</span>
        <input type="tel" name="phone" required inputmode="numeric" placeholder="01xxxxxxxxx"
               value="<?= e((string) ($_POST['phone'] ?? '')) ?>"></label>
      <div class="two">
        <label class="fld"><span class="lbl">كلمة السر</span>
          <input type="password" name="password" required minlength="8" placeholder="٨ حروف على الأقل"></label>
        <label class="fld"><span class="lbl">تأكيدها</span>
          <input type="password" name="password2" required minlength="8" placeholder="اكتبها تاني"></label>
      </div>
      <button class="btn call wide big">جهّز التطبيق</button>
    </form>
<?php endif; ?>
  </div>
</main>
</body>
</html>
