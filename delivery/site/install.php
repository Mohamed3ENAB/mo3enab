<?php
/**
 * تنصيب لمرة واحدة: بيعمل الجداول ويحط القرى والأسعار.
 * 🔴 بعد ما يخلص، امسح الملف ده من السيرفر.
 */
declare(strict_types=1);
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/helpers.php';

$done = false; $err = null; $demo = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
        foreach (array_filter(array_map('trim', explode(';', (string) $sql))) as $stmt) {
            if ($stmt !== '') db()->exec($stmt);
        }

        // القرى
        $zones = ['القيصرية', 'بطينة', 'محلة أبو علي', 'محلة زياد', 'المحلة الكبرى'];
        foreach ($zones as $i => $z) {
            q('INSERT IGNORE INTO zones (name_ar, sort_order) VALUES (?,?)', [$z, $i + 1]);
        }
        $zid = [];
        foreach (rows('SELECT id, name_ar FROM zones') as $r) $zid[$r['name_ar']] = (int) $r['id'];

        // أسعار البداية لكل مركبة
        $rates = [
            ['bicycle', 5, 'للطلبات القريبة جوه البلد', 1],
            ['delivery', 10, 'موتوسيكل — جوه البلد والقرى القريبة', 2],
            ['tuktuk', 15, 'للحاجات اللي محتاجة مساحة', 3],
            ['goods', 20, 'حسب الحجم والوزن — اتفق قبل', 4],
            ['mahalla_run', 70, 'ذهاب وعودة للمحلة الكبرى', 5],
        ];
        foreach ($rates as [$k, $v, $n, $o]) {
            q('INSERT IGNORE INTO vehicle_rates (kind, starts_from, note_ar, sort_order) VALUES (?,?,?,?)',
              [$k, $v, $n, $o]);
        }

        // أسعار استرشادية
        if (!val('SELECT COUNT(*) FROM price_guide')) {
            $g = [
                [$zid['القيصرية'], $zid['القيصرية'], 'delivery', 20, 35, 'حسب المسافة جوه البلد'],
                [$zid['القيصرية'], $zid['بطينة'], 'delivery', 35, 50, 'بين القرى المتجاورة'],
                [$zid['القيصرية'], $zid['محلة أبو علي'], 'delivery', 35, 50, 'بين القرى المتجاورة'],
                [$zid['القيصرية'], $zid['محلة زياد'], 'delivery', 35, 55, 'بين القرى المتجاورة'],
                [$zid['القيصرية'], $zid['المحلة الكبرى'], 'mahalla_run', 70, 100, 'ذهاب وعودة — الانتظار الطويل بيزوّد'],
                [$zid['القيصرية'], $zid['القيصرية'], 'bicycle', 15, 25, 'للطلبات القريبة جوه البلد'],
            ];
            foreach ($g as $r) {
                q('INSERT INTO price_guide (from_zone_id, to_zone_id, kind, typical_min, typical_max, note_ar)
                   VALUES (?,?,?,?,?,?)', $r);
            }
        }

        // بيانات تجريبية اختيارية
        if (!empty($_POST['demo']) && !val('SELECT COUNT(*) FROM providers')) {
            $demo = true;
            $drivers = [
                ['محمود السيد','01021110001','01021110001','القيصرية','delivery,goods','موتوسيكل','بيشتغل من 12 لـ 10',1,1,14],
                ['كريم أبو زيد','01021110002',null,'القيصرية','tuktuk,goods','توك توك',null,1,1,41],
                ['أحمد فرغلي','01021110003','01021110003','القيصرية','delivery,mahalla_run','موتوسيكل','بيروح المحلة يوميًا',1,1,96],
                ['يوسف عبد الله','01021110004',null,'القيصرية','bicycle,delivery','عجلة','الطلبات القريبة بس',0,0,300],
                ['سيد الشناوي','01021110005','01021110005','بطينة','delivery','موتوسيكل',null,1,0,600],
            ];
            foreach ($drivers as [$n,$p,$w,$z,$s,$v,$note,$ver,$av,$ago]) {
                q('INSERT INTO providers (id, display_name, phone, whatsapp, zone_id, services, vehicle_note,
                     note, is_verified, verified_on, is_available, available_at, token)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?, DATE_SUB(NOW(), INTERVAL ? MINUTE), ?)',
                  [new_id(), $n, $p, $w, $zid[$z], $s, $v, $note, $ver, $ver ? date('Y-m-d') : null,
                   $av, $ago, new_id()]);
            }
            $places = [
                ['مطعم الشرقاوي','restaurant','القيصرية','01055550001','01055550001','شارع الجامع الكبير','من 12ظ لـ 1 بالليل','فراخ ولحمة مشوية'],
                ['كشري أم محمد','restaurant','القيصرية','01055550002',null,'أول شارع المحطة','من 11ص لـ 11م',null],
                ['سوبر ماركت النور','supermarket','القيصرية','01055550003','01055550003','قدام الوحدة الصحية','من 8ص لـ 12م',null],
                ['بقالة الحاج سيد','grocery','بطينة','01055550004',null,'وسط البلد','من 7ص لـ 11م',null],
                ['عطارة الشفاء','herbalist','القيصرية','01055550005','01055550005','جنب مسجد النصر','من 9ص لـ 10م','أعشاب وتوابل'],
                ['صيدلية د. هالة','pharmacy','القيصرية','01055550007','01055550007','ميدان البلد','24 ساعة',null],
            ];
            foreach ($places as [$n,$c,$z,$p,$w,$a,$h,$note]) {
                q('INSERT INTO places (id, name_ar, category, zone_id, phone, whatsapp, address_note, hours_note, note)
                   VALUES (?,?,?,?,?,?,?,?,?)', [new_id(), $n, $c, $zid[$z], $p, $w, $a, $h, $note]);
            }
        }

        @file_put_contents(__DIR__ . '/.installed', date('c'));
        $done = true;
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تنصيب · في السكة</title>
<link rel="icon" href="assets/icons/favicon.ico" sizes="any">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;600;700;800&display=swap">
<link rel="stylesheet" href="assets/app.css">
</head>
<body>
<main class="wrap page" style="padding-top:40px">
  <h1>تنصيب «في السكة»</h1>
<?php if ($done): ?>
  <p class="msg ok">تمام. قاعدة البيانات اتعملت<?= $demo ? ' مع بيانات تجريبية' : '' ?>.</p>
  <div class="note warn">
    <span><strong>امسح ملف install.php من السيرفر دلوقتي.</strong> سيبانه معناه إن أي حد
      يقدر يفتحه. من File Manager في هوستنجر: اختار الملف واضغط Delete.</span></div>
  <a class="btn call wide" href="./">افتح التطبيق</a>
  <p class="lede" style="margin-top:14px">لوحة الإدارة على <code>/admin</code> بكلمة السر اللي في config.php.</p>
<?php else: ?>
  <?php if ($err): ?><p class="msg bad">فيه مشكلة: <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <div class="note">
    <span>الصفحة دي بتعمل جداول قاعدة البيانات وتحط القرى والأسعار المبدئية.
      اتأكد إنك ظبطت <strong>config.php</strong> الأول.</span></div>
  <form method="post" class="card">
    <div class="checks" style="margin-bottom:16px">
      <label><input type="checkbox" name="demo" value="1" checked> حط بيانات تجريبية عشان أشوف الشكل</label>
    </div>
    <p class="lede" style="font-size:13.5px">البيانات التجريبية أسماء وأرقام وهمية — امسحها من لوحة
      الإدارة قبل ما تنشر الموقع للناس.</p>
    <button class="btn call wide" type="submit">ابدأ التنصيب</button>
  </form>
<?php endif; ?>
</main>
</body>
</html>
