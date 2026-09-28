<?php
declare(strict_types=1);
/**
 * أيقونة التطبيق — بتترسم هنا بدل ما تتخزن كملفات PNG جاهزة.
 * السبب: أي مقاس بيتولد لوحده (16 لحد 1024)، والتصميم بيتغير من
 * مكان واحد، ومفيش ملفات صور تتنسى تتحدّث.
 *   icon.php?s=192          أيقونة عادية بحواف دايرية
 *   icon.php?s=512&m=1      maskable — مربع كامل والرسمة جوه المنطقة الآمنة
 */

$size = (int) ($_GET['s'] ?? 512);
$size = max(16, min(1024, $size));
$maskable = !empty($_GET['m']);

$etag = '"icon-v2-' . $size . ($maskable ? 'm' : '') . '"';
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: ' . $etag);
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }

if (!function_exists('imagecreatetruecolor')) { http_response_code(500); exit; }

// بنرسم بأربع أضعاف المقاس وبعدين نصغّر — دي أرخص طريقة
// لحواف ناعمة في GD من غير antialiasing حقيقي.
$SS = min(2048, $size * 4);
$im = imagecreatetruecolor($SS, $SS);
imagealphablending($im, false);
imagesavealpha($im, true);
imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
imagealphablending($im, true);

$radius = $maskable ? 0 : (int) round($SS * 0.225);

/** لون متدرّج بين لونين. */
$mix = function (array $a, array $b, float $t): array {
    return [
        (int) round($a[0] + ($b[0] - $a[0]) * $t),
        (int) round($a[1] + ($b[1] - $a[1]) * $t),
        (int) round($a[2] + ($b[2] - $a[2]) * $t),
    ];
};

$top = [26, 226, 160];   // أخضر فاتح
$bot = [4, 92, 84];      // أخضر غامق مزرق

// الخلفية: كل سطر بلونه، وعرض السطر محسوب عشان الحواف تطلع دايرية
for ($y = 0; $y < $SS; $y++) {
    $t = $y / max(1, $SS - 1);
    [$r, $g, $b] = $mix($top, $bot, $t);
    $col = imagecolorallocate($im, $r, $g, $b);
    $dx = 0;
    if ($radius > 0) {
        if ($y < $radius)            $dy = $radius - $y;
        elseif ($y > $SS - $radius)  $dy = $y - ($SS - $radius);
        else                         $dy = 0;
        if ($dy > 0) $dx = (int) round($radius - sqrt(max(0, $radius * $radius - $dy * $dy)));
    }
    imageline($im, $dx, $y, $SS - 1 - $dx, $y, $col);
}

// لمعة قطرية خفيفة فوق — بتدي إحساس بالحجم
$glow = imagecolorallocatealpha($im, 255, 255, 255, 112);
imagefilledpolygon($im, [0, 0, $SS, 0, 0, (int) ($SS * 0.72)], $glow);

// المنطقة الآمنة للـ maskable: الرسمة كلها جوه ٨٠٪ من المربع
$pad   = $maskable ? $SS * 0.19 : $SS * 0.14;
$inner = $SS - 2 * $pad;
$cx    = $SS / 2;

// السكة: شكل منظوري — عريض من تحت، ضيّق من فوق
$roadTopY    = $pad + $inner * 0.30;
$roadBotY    = $pad + $inner * 0.95;
$roadTopHalf = $inner * 0.13;
$roadBotHalf = $inner * 0.40;

$road = imagecolorallocatealpha($im, 255, 255, 255, 18);
imagefilledpolygon($im, [
    (int) ($cx - $roadTopHalf), (int) $roadTopY,
    (int) ($cx + $roadTopHalf), (int) $roadTopY,
    (int) ($cx + $roadBotHalf), (int) $roadBotY,
    (int) ($cx - $roadBotHalf), (int) $roadBotY,
], $road);

// الشرط المتقطّع في نص السكة — أصفر دافي
$dash = imagecolorallocate($im, 255, 198, 76);
$steps = 4;
for ($i = 0; $i < $steps; $i++) {
    $t0 = $i / $steps + 0.06;
    $t1 = ($i + 0.55) / $steps + 0.06;
    if ($t1 > 1) break;
    $y0 = $roadTopY + ($roadBotY - $roadTopY) * $t0;
    $y1 = $roadTopY + ($roadBotY - $roadTopY) * $t1;
    $w0 = ($roadTopHalf + ($roadBotHalf - $roadTopHalf) * $t0) * 0.16;
    $w1 = ($roadTopHalf + ($roadBotHalf - $roadTopHalf) * $t1) * 0.16;
    imagefilledpolygon($im, [
        (int) ($cx - $w0), (int) $y0, (int) ($cx + $w0), (int) $y0,
        (int) ($cx + $w1), (int) $y1, (int) ($cx - $w1), (int) $y1,
    ], $dash);
}

// دبوس المكان فوق السكة
$pinR  = $inner * 0.145;
$pinCy = $pad + $inner * 0.20;
$white = imagecolorallocate($im, 255, 255, 255);
imagefilledellipse($im, (int) $cx, (int) $pinCy, (int) ($pinR * 2), (int) ($pinR * 2), $white);
imagefilledpolygon($im, [
    (int) ($cx - $pinR * 0.72), (int) ($pinCy + $pinR * 0.62),
    (int) ($cx + $pinR * 0.72), (int) ($pinCy + $pinR * 0.62),
    (int) $cx,                  (int) ($pinCy + $pinR * 1.85),
], $white);
$hole = imagecolorallocate($im, 6, 120, 96);
imagefilledellipse($im, (int) $cx, (int) $pinCy, (int) ($pinR * 0.82), (int) ($pinR * 0.82), $hole);

// التصغير للمقاس المطلوب
$out = imagecreatetruecolor($size, $size);
imagealphablending($out, false);
imagesavealpha($out, true);
imagecopyresampled($out, $im, 0, 0, 0, 0, $size, $size, $SS, $SS);
imagedestroy($im);

header('Content-Type: image/png');
imagepng($out, null, 6);
imagedestroy($out);
