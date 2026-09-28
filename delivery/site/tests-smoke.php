<?php
declare(strict_types=1);
/**
 * اختبار سريع للمسارات المهمة — بيشتغل من سطر الأوامر بس.
 *
 *   php -S 127.0.0.1:8090 router.php &
 *   php tests-smoke.php http://127.0.0.1:8090
 *
 * بيعدي على: التنصيب · الدخول · تسجيل سائق بصور · موافقة الإدارة ·
 * الداش بوردات · حماية صور المستندات · نشر طلب · الأيقونات.
 * ⚠️ بيكتب بيانات حقيقية في القاعدة — شغّله على قاعدة تجريبية بس.
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

require __DIR__ . '/lib/helpers.php';   // للقناع وبس — مفيش اتصال بقاعدة البيانات

$BASE = rtrim($argv[1] ?? 'http://127.0.0.1:8090', '/');
$JAR  = sys_get_temp_dir() . '/sekka-test-' . getmypid() . '.cookies';
@unlink($JAR);

$pass = 0; $fail = 0;

function ok(string $name, bool $cond, string $extra = ''): void {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  \033[32m✓\033[0m $name\n"; }
    else       { $fail++; echo "  \033[31m✗\033[0m $name" . ($extra ? " — $extra" : '') . "\n"; }
}
function group(string $t): void { echo "\n\033[1m$t\033[0m\n"; }

/** طلب HTTP مع كوكيز محفوظة بين الطلبات. */
function http(string $url, ?array $post = null, array $files = []): array {
    global $JAR;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $JAR,
        CURLOPT_COOKIEFILE     => $JAR,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    if ($post !== null) {
        foreach ($files as $field => $path) {
            $post[$field] = new CURLFile($path, mime_content_type($path) ?: 'application/octet-stream', basename($path));
        }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $files ? $post : http_build_query($post));
    }
    $raw  = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hlen = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return ['code' => $code, 'head' => substr($raw, 0, $hlen), 'body' => substr($raw, $hlen)];
}

/** التوكن بيتقرا من الصفحة نفسها زي ما المتصفح بيعمل. */
function csrf(string $html): string {
    return preg_match('/name="_csrf" value="([a-f0-9]+)"/', $html, $m) ? $m[1] : '';
}

/** صورة اختبار حقيقية بـ GD. */
function make_image(string $path, int $w, int $h, array $rgb): string {
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, ...$rgb));
    imagefilledellipse($im, (int) ($w / 2), (int) ($h / 2), (int) ($w / 3), (int) ($h / 3),
                       imagecolorallocate($im, 255, 255, 255));
    imagejpeg($im, $path, 88);
    imagedestroy($im);
    return $path;
}

$tmp   = sys_get_temp_dir();
$photo = make_image("$tmp/sekka-photo.jpg", 900, 600, [40, 120, 90]);
$idimg = make_image("$tmp/sekka-id.jpg", 1600, 1000, [180, 140, 60]);
$lic   = make_image("$tmp/sekka-lic.jpg", 1200, 800, [60, 90, 170]);
$pdf   = "$tmp/sekka-fake.pdf";
file_put_contents($pdf, "%PDF-1.4\n% fake file, not an image\n");

$stamp = substr((string) time(), -6);
$adminPhone  = '0100' . $stamp . '1';
$driverPhone = '0111' . $stamp . '2';
$custPhone   = '0122' . $stamp . '3';

echo "\n\033[1m═══ في السكة — اختبار سريع ═══\033[0m\n$BASE\n";

// ── ١. التنصيب ───────────────────────────────────────────────
group('١. التنصيب');
$r = http("$BASE/install.php");
ok('صفحة التنصيب بتفتح', $r['code'] === 200);
$installed = str_contains($r['body'], 'التطبيق متنصّب خلاص');

if (!$installed) {
    ok('الفحوصات بتظهر', str_contains($r['body'], 'مكتبة الصور GD'));
    $r = http("$BASE/install.php", [
        'name' => 'مدير الاختبار', 'phone' => $adminPhone,
        'password' => 'sekka12345', 'password2' => 'sekka12345',
    ]);
    ok('التنصيب خلص', str_contains($r['body'], 'التطبيق جاهز'), 'رد غير متوقع');
    ok('بيقول امسح install.php', str_contains($r['body'], 'امسح ملف install.php'));
} else {
    echo "  (متنصّب قبل كده — بنكمّل)\n";
}

// ── ٢. الصفحات العامة ────────────────────────────────────────
group('٢. الصفحات العامة');
foreach ([
    ''         => 'في السكة',
    'places'   => 'المحلات',
    'prices'   => 'الأسعار',
    'requests' => 'لوحة الطلبات',
    'join'     => 'اشتغل معانا',
    'register' => 'اختار نوع الحساب',
    'login'    => 'أهلًا بيك تاني',
] as $path => $needle) {
    $r = http("$BASE/$path");
    ok("/$path بتفتح", $r['code'] === 200 && str_contains($r['body'], $needle), 'HTTP ' . $r['code']);
}
$r = http("$BASE/mesh-msh-mawgood");
ok('صفحة مش موجودة بتدي 404', $r['code'] === 404 && str_contains($r['body'], 'الصفحة مش موجودة'));

// ── ٣. الأيقونات ─────────────────────────────────────────────
group('٣. الأيقونات والمانيفست');
foreach ([64, 192, 512] as $s) {
    $r = http("$BASE/icon.php?s=$s");
    $info = @getimagesizefromstring($r['body']);
    ok("icon.php?s=$s بيطلع PNG {$s}×{$s}",
        $info && $info[0] === $s && $info[1] === $s && $info[2] === IMAGETYPE_PNG);
}
$r = http("$BASE/icon.php?s=512&m=1");
ok('النسخة الـ maskable شغّالة', (bool) @getimagesizefromstring($r['body']));
$r = http("$BASE/manifest.webmanifest");
$mf = json_decode($r['body'], true);
ok('المانيفست JSON صح', is_array($mf) && ($mf['short_name'] ?? '') === 'في السكة');
$r = http("$BASE/assets/icons/favicon.svg");
ok('الـ favicon موجود', str_contains($r['body'], '<svg'));

// ── ٤. الدخول ────────────────────────────────────────────────
group('٤. دخول الإدارة');
$r = http("$BASE/admin");
ok('الإدارة محميّة من الزوار', str_contains($r['body'], 'أهلًا بيك تاني'), 'المفروض يروّح على الدخول');

$page = http("$BASE/login");
$r = http("$BASE/login", ['_csrf' => csrf($page['body']), 'phone' => $adminPhone, 'password' => 'ghalat-khales']);
ok('كلمة سر غلط بترفض', str_contains($r['body'], 'الرقم أو كلمة السر غلط'));

$page = http("$BASE/login");
$r = http("$BASE/login", ['_csrf' => csrf($page['body']), 'phone' => $adminPhone, 'password' => 'sekka12345']);
ok('دخول الإدارة شغّال', str_contains($r['body'], 'لوحة الإدارة'), 'مدخلش');

$r = http("$BASE/login", ['phone' => $adminPhone, 'password' => 'sekka12345']);
ok('POST من غير توكن CSRF بيترفض', $r['code'] === 419);

foreach (['approvals', 'drivers', 'places', 'users', 'requests', 'reports', 'settings'] as $sec) {
    $r = http("$BASE/admin/$sec");
    ok("قسم الإدارة /$sec بيفتح", $r['code'] === 200 && !str_contains($r['body'], 'أهلًا بيك تاني'));
}
http("$BASE/logout");

// ── ٥. تسجيل سائق بصور ───────────────────────────────────────
group('٥. تسجيل سائق برفع صور');
$page = http("$BASE/register/driver");
$tok  = csrf($page['body']);
ok('نموذج السائق فيه حقل صورة الحساب', str_contains($page['body'], 'data-avatar-field'));
ok('نموذج السائق فيه البطاقة والرخصة',
    str_contains($page['body'], 'name="national_id"') && str_contains($page['body'], 'name="license"'));
ok('حقول الصور بتقبل صور بس',
    substr_count($page['body'], 'accept="image/png,image/jpeg,image/webp,image/gif"') >= 3);

// ملف مش صورة لازم يترفض
$r = http("$BASE/register/driver", [
    '_csrf' => $tok, 'name' => 'سائق الاختبار', 'phone' => $driverPhone,
    'password' => 'sekka12345', 'password2' => 'sekka12345', 'zone_id' => '1',
    'services' => ['tuktuk'], 'vehicle_note' => 'توك توك أحمر',
], ['photo' => $photo, 'national_id' => $pdf, 'license' => $lic]);
ok('ملف PDF بدل صورة بيترفض', str_contains($r['body'], 'الملفات زي PDF أو Word مش مقبولة'));
ok('الحساب متعملش لما الرفع بايظ', str_contains($r['body'], 'سجّل كسائق'));

// الصح
$page = http("$BASE/register/driver");
$r = http("$BASE/register/driver", [
    '_csrf' => csrf($page['body']), 'name' => 'سائق الاختبار', 'phone' => $driverPhone,
    'password' => 'sekka12345', 'password2' => 'sekka12345', 'zone_id' => '1',
    'services' => ['tuktuk', 'delivery'], 'vehicle_note' => 'توك توك أحمر',
], ['photo' => $photo, 'national_id' => $idimg, 'license' => $lic]);
ok('التسجيل بيوصل لصفحة المراجعة', str_contains($r['body'], 'طلبك تحت المراجعة'), 'رد غير متوقع');
ok('السائق مش ظاهر في الدليل قبل الموافقة', !str_contains(http("$BASE/")['body'], 'سائق الاختبار'));

$r = http("$BASE/dashboard/driver");
ok('داش بورد السائق مقفولة قبل الموافقة', str_contains($r['body'], 'طلبك تحت المراجعة'));

// صورة المستند: صاحبها يشوفها.
// بندوّر على لينك بـ target="_blank" عشان دي لينكات المستندات بس —
// صورة الحساب في نفس الصفحة عامة، ولو أخدناها الاختبار مبيثبتش حاجة.
preg_match('/img\.php\?id=([a-f0-9]{32})" target="_blank"/', http("$BASE/dashboard/pending")['body'], $m);
$docId = $m[1] ?? '';
ok('لقينا صورة مستند في صفحة المراجعة', $docId !== '');
ok('صور المستندات ظاهرة لصاحبها', $docId !== '' && http("$BASE/img.php?id=$docId")['code'] === 200);
http("$BASE/logout");
ok('🔴 صور المستندات مقفولة على الغريب', http("$BASE/img.php?id=$docId")['code'] === 403);

// ── ٦. الموافقة ──────────────────────────────────────────────
group('٦. موافقة الإدارة');
$page = http("$BASE/login");
http("$BASE/login", ['_csrf' => csrf($page['body']), 'phone' => $adminPhone, 'password' => 'sekka12345']);
$page = http("$BASE/admin/approvals");
ok('الطلب ظاهر للإدارة', str_contains($page['body'], 'سائق الاختبار'));
ok('الإدارة بتشوف صور المستندات', str_contains($page['body'], 'docthumb'));

preg_match('/name="id" value="([a-f0-9]{32})"/', $page['body'], $m);
$r = http("$BASE/admin/approvals", ['_csrf' => csrf($page['body']), 'do' => 'approve', 'id' => $m[1] ?? '']);
ok('الموافقة اشتغلت', str_contains($r['body'], 'اتوافق على الحساب'));
http("$BASE/logout");

$home = http("$BASE/")['body'];
ok('السائق ظهر في الدليل بعد الموافقة', str_contains($home, 'سائق الاختبار'));
ok('صورة السائق بتتقدّم', (bool) preg_match('/img\.php\?id=[a-f0-9]{32}/', $home));

// الصورة المربعة
preg_match('/img\.php\?id=([a-f0-9]{32})/', $home, $m);
$img = http("$BASE/img.php?id={$m[1]}");
$info = @getimagesizefromstring($img['body']);
ok('صورة الحساب اتحفظت مربعة 512×512', $info && $info[0] === 512 && $info[1] === 512,
   $info ? "{$info[0]}×{$info[1]}" : 'مش صورة');

// ── ٧. العميل والطلبات ───────────────────────────────────────
group('٧. العميل والطلبات');
$page = http("$BASE/register/customer");
$r = http("$BASE/register/customer", [
    '_csrf' => csrf($page['body']), 'name' => 'عميل الاختبار', 'phone' => $custPhone,
    'password' => 'sekka12345', 'password2' => 'sekka12345', 'zone_id' => '1', 'hide_phone' => '1',
]);
ok('حساب العميل بيشتغل على طول', str_contains($r['body'], 'عميل الاختبار') && str_contains($r['body'], 'اطلب دلوقتي'));

$page = http("$BASE/dashboard/customer");
$r = http("$BASE/dashboard/customer", [
    '_csrf' => csrf($page['body']), 'do' => 'new_request', 'kind' => 'tuktuk',
    'body' => 'محتاج توك توك من القيصرية للمحلة الساعة ٦ بالليل.', 'zone_id' => '1', 'hide_phone' => '1',
]);
ok('الطلب اتنشر', str_contains($r['body'], 'طلبك اتنشر'));

// بنخرج الأول: صاحب الطلب بيشوف رقمه هو في النموذج (طبيعي)،
// والسؤال الحقيقي هو الزائر بيشوف إيه.
http("$BASE/logout");
$board = http("$BASE/requests")['body'];
ok('الطلب ظاهر في اللوحة', str_contains($board, 'محتاج توك توك من القيصرية'));
ok('🔴 الرقم المخفي مش في الـ HTML خالص', !str_contains($board, $custPhone), 'الرقم ظهر!');
ok('القناع ظاهر بدل الرقم', str_contains($board, mask_phone($custPhone)));

// ── ٨. السائق بيستلم ─────────────────────────────────────────
group('٨. السائق بيستلم الطلب');
$page = http("$BASE/login");
http("$BASE/login", ['_csrf' => csrf($page['body']), 'phone' => $driverPhone, 'password' => 'sekka12345']);
$page = http("$BASE/dashboard/driver");
ok('داش بورد السائق فتحت بعد الموافقة', str_contains($page['body'], 'افتح التوفر'));

$r = http("$BASE/dashboard/driver", ['_csrf' => csrf($page['body']), 'do' => 'avail', 'v' => '1']);
ok('التوفر بيتفتح', str_contains($r['body'], 'اسمك دلوقتي في المتاحين'));
ok('السائق بقى في المتاحين', str_contains(http("$BASE/")['body'], 'متاح دلوقتي'));

preg_match('/name="rid" value="([a-f0-9]{32})"/', $r['body'], $m);
if (!empty($m[1])) {
    $page = http("$BASE/dashboard/driver");
    $r = http("$BASE/dashboard/driver", ['_csrf' => csrf($page['body']), 'do' => 'take', 'rid' => $m[1]]);
    ok('السائق استلم الطلب', str_contains($r['body'], 'استلمت الطلب'));
} else {
    ok('الطلب ظاهر للسائق', false, 'ملقيناش زرار الاستلام');
}
http("$BASE/logout");

// ── ٩. المحل ─────────────────────────────────────────────────
group('٩. تسجيل محل');
$shopPhone = '0155' . $stamp . '4';
$page = http("$BASE/register/merchant");
$r = http("$BASE/register/merchant", [
    '_csrf' => csrf($page['body']), 'name' => 'صاحب محل الاختبار', 'phone' => $shopPhone,
    'password' => 'sekka12345', 'password2' => 'sekka12345', 'zone_id' => '1',
    'shop_name' => 'سوبر ماركت الاختبار', 'category' => 'supermarket',
    'hours_note' => 'من ٩ الصبح لـ ١٢ بالليل',
], ['photo' => $photo, 'national_id' => $idimg]);
ok('تسجيل المحل بيوصل للمراجعة', str_contains($r['body'], 'طلبك تحت المراجعة'));
ok('المحل مش ظاهر قبل الموافقة', !str_contains(http("$BASE/places")['body'], 'سوبر ماركت الاختبار'));
http("$BASE/logout");

$page = http("$BASE/login");
http("$BASE/login", ['_csrf' => csrf($page['body']), 'phone' => $adminPhone, 'password' => 'sekka12345']);
$page = http("$BASE/admin/approvals");
if (preg_match('/name="id" value="([a-f0-9]{32})"/', $page['body'], $m)) {
    $r = http("$BASE/admin/approvals", ['_csrf' => csrf($page['body']), 'do' => 'approve', 'id' => $m[1]]);
    ok('الإدارة وافقت على المحل', str_contains($r['body'], 'اتوافق على الحساب'));
}
http("$BASE/logout");
ok('المحل ظهر في الدليل', str_contains(http("$BASE/places")['body'], 'سوبر ماركت الاختبار'));

$page = http("$BASE/login");
$r = http("$BASE/login", ['_csrf' => csrf($page['body']), 'phone' => $shopPhone, 'password' => 'sekka12345']);
ok('داش بورد المحل فتحت', str_contains($r['body'], 'المحل فاتح') || str_contains($r['body'], 'اقفل المحل'));
$page = http("$BASE/dashboard/merchant");
$r = http("$BASE/dashboard/merchant", ['_csrf' => csrf($page['body']), 'do' => 'open', 'v' => '0']);
ok('قفل المحل شغّال', str_contains($r['body'], 'قفلت المحل'));
ok('الحالة اتغيّرت في الدليل', str_contains(http("$BASE/places")['body'], 'قافل'));
http("$BASE/logout");

// ── ١٠. الحماية ──────────────────────────────────────────────
group('١٠. الحماية');
ok('العميل مش بيدخل داش بورد السائق',
    str_contains(http("$BASE/dashboard/driver")['body'], 'أهلًا بيك تاني'));
$page = http("$BASE/login");
$r = http("$BASE/login", ['_csrf' => csrf($page['body']), 'phone' => $driverPhone, 'password' => 'sekka12345']);
$r = http("$BASE/admin");
ok('السائق مش بيدخل لوحة الإدارة', !str_contains($r['body'], 'طلبات الانضمام'));
http("$BASE/logout");

@unlink($JAR);
foreach ([$photo, $idimg, $lic, $pdf] as $f) @unlink($f);

echo "\n\033[1m───────────────────────────\033[0m\n";
echo "  نجح: \033[32m$pass\033[0m   فشل: " . ($fail ? "\033[31m$fail\033[0m" : '0') . "\n\n";
exit($fail ? 1 : 0);
