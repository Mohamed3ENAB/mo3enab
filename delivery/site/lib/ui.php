<?php
declare(strict_types=1);

/** أيقونات SVG مرسومة بالإيد — مفيش مكتبة خارجية. التوك توك مش موجود في أي مكتبة. */
function icon(string $name, int $size = 22, string $class = ''): string {
    static $d = null;
    if ($d === null) $d = require __DIR__ . '/icons.php';
    if (!isset($d[$name])) return '';
    [$path, $fill, $sw] = $d[$name];
    $c = $class ? ' class="' . e($class) . '"' : '';
    if ($fill) {
        return "<svg{$c} width=\"$size\" height=\"$size\" viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\">$path</svg>";
    }
    return "<svg{$c} width=\"$size\" height=\"$size\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" "
         . "stroke-width=\"$sw\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\">$path</svg>";
}

function bubble_class(string $key): string {
    return 'b-' . ($key === 'mahalla_run' ? 'mahalla' : $key);
}

function avatar(string $name, ?string $photoId, bool $verified = false, int $size = 52): string {
    $style = $size !== 52 ? " style=\"width:{$size}px;height:{$size}px;font-size:" . (int)($size * .38) . "px\"" : '';
    $badge = $verified ? '<span class="vbadge" title="موثّق">' . icon('check', 12) . '</span>' : '';
    if ($photoId) {
        return '<span class="avatar photo"' . $style . '><img src="' . url('img.php?id=' . e($photoId))
             . '" alt="" loading="lazy" decoding="async">' . $badge . '</span>';
    }
    $bg = avatar_bg($name);
    $st = $style ? rtrim($style, '"') . ';background:' . $bg . '"' : ' style="background:' . $bg . '"';
    return '<span class="avatar"' . $st . '>' . e(mb_substr(trim($name), 0, 1)) . $badge . '</span>';
}

/** الرقم كنص يتنسخ + زرار اتصال. بنعرض الرقم عشان يشتغل في كل الحالات. */
function tel_box(string $phone, string $label = 'اتصال'): string {
    return '<div class="telbox"><span class="num">' . e($phone) . '</span>'
         . '<a class="tb-call" href="' . e(tel_link($phone)) . '">' . icon('phone', 18) . ' ' . e($label) . '</a>'
         . '<button type="button" class="tb-copy" data-copy="' . e($phone) . '">نسخ</button></div>';
}

function state_pill(bool $live, ?string $at): string {
    return '<span class="state ' . ($live ? 'on' : 'off') . '">'
         . '<span class="beacon"><i></i><i></i></span>'
         . ($live ? 'متاح' : 'مش متاح')
         . '<span class="since">' . e(since_ar($at)) . '</span></span>';
}

function stars_row(int $n): string {
    $out = '<span class="stars-row" aria-label="' . $n . ' من 5">';
    for ($i = 1; $i <= 5; $i++) $out .= icon('star', 13, $i <= $n ? '' : 'off');
    return $out . '</span>';
}

function flash(?string $ok, ?string $bad): string {
    if ($ok)  return '<p class="msg ok">' . icon('check', 16) . ' ' . e($ok) . '</p>';
    if ($bad) return '<p class="msg bad">' . icon('alert', 16) . ' ' . e($bad) . '</p>';
    return '';
}

function bottom_nav(string $current): string {
    $items = [
        ['', 'السواقين', 'home'],
        ['places', 'المحلات', 'store'],
        ['requests', 'الطلبات', 'board'],
        ['prices', 'الأسعار', 'money'],
    ];
    $out = '<nav class="nav" aria-label="التنقل"><ul>';
    foreach ($items as [$path, $label, $ic]) {
        $key = $path === '' ? 'home' : $path;
        $cur = $key === $current ? ' aria-current="page"' : '';
        $out .= '<li><a href="' . url($path) . '"' . $cur . '>' . icon($ic, 23, 'ic') . $label . '</a></li>';
    }
    return $out . '</ul></nav>';
}

function site_footer(): string {
    $phone = (string) cfg('support_phone');
    return '<footer class="foot">'
      . '<p><a href="' . url('join') . '">اشتغل معانا</a> · <a href="' . url('admin') . '">الإدارة</a></p>'
      . '<p><strong>في السكة</strong> خدمة مجتمعية مجانية بتعرض بيانات سواقين ومحلات في المنطقة عشان '
      . 'تسهّل التواصل. إحنا مش طرف في أي اتفاق بينك وبين السائق أو المحل، ومش بناخد عمولة، '
      . 'ومش بنحدد أسعار ملزمة، ومش مسؤولين عن جودة الخدمة أو أي خلاف.</p>'
      . '<p>«موثّق» معناها إننا شوفنا بطاقة ورخصة السائق. مش ضمان.</p>'
      . ($phone ? '<p>لأي شكوى أو طلب حذف بياناتك: <a href="' . e(tel_link($phone)) . '">' . e($phone) . '</a></p>' : '')
      . '</footer>';
}

function layout(string $title, string $body, string $nav = '', bool $wide = false): void {
    $t = $title ? $title . ' · في السكة' : 'في السكة';
    ?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($t) ?></title>
<meta name="description" content="دليل مجاني لسواقين ومحلات القيصرية والمناطق المجاورة. شوف مين متاح دلوقتي وكلّمه على طول.">
<meta name="theme-color" content="#00A86B" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#070B12" media="(prefers-color-scheme: dark)">
<meta name="format-detection" content="telephone=no">
<link rel="icon" href="<?= url('assets/icons/favicon.ico') ?>" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="<?= url('assets/icons/favicon-32.png') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= url('assets/icons/favicon-16.png') ?>">
<link rel="apple-touch-icon" href="<?= url('assets/icons/apple-touch-icon.png') ?>">
<link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="في السكة">
<meta name="mobile-web-app-capable" content="yes">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;600;700;800&display=swap">
<link rel="stylesheet" href="<?= url('assets/app.css') ?>?v=1">
</head>
<body<?= $nav ? ' class="app"' : '' ?>>
<?= $body ?>
<?= $nav ?>
<script src="<?= url('assets/app.js') ?>?v=1" defer></script>
</body>
</html>
    <?php
}
