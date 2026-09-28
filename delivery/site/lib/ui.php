<?php
declare(strict_types=1);

const ASSET_V = '3';   // بيتغيّر مع كل تحديث عشان الكاش ينضف

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
    return '<span class="avatar"' . $st . '>' . e(initials($name)) . $badge . '</span>';
}

/** الرقم كنص يتنسخ + زرار اتصال. بنعرض الرقم عشان يشتغل في كل الحالات. */
function tel_box(string $phone, string $label = 'اتصال'): string {
    return '<div class="telbox"><span class="num">' . e($phone) . '</span>'
         . '<a class="tb-call" href="' . e(tel_link($phone)) . '" data-sfx="call">' . icon('phone', 18) . ' ' . e($label) . '</a>'
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

function badge(string $text, string $tone = ''): string {
    return '<span class="badge ' . e($tone) . '">' . e($text) . '</span>';
}

function status_badge(string $status): string {
    return match ($status) {
        'active'    => badge('متفعّل', 'good'),
        'approved'  => badge('اتقبلت', 'good'),
        'pending'   => badge('تحت المراجعة', 'warn'),
        'rejected'  => badge('مرفوض', 'bad'),
        'suspended' => badge('موقوف', 'bad'),
        'open'      => badge('مفتوح', 'good'),
        'taken'     => badge('سائق استلمه', 'info'),
        'done'      => badge('خلص', 'muted'),
        'cancelled' => badge('اتلغى', 'muted'),
        default     => badge($status),
    };
}

// ── عناصر النماذج ──────────────────────────────────────────────

function field(string $label, string $control, string $hint = '', bool $required = false): string {
    return '<label class="fld"><span class="lbl">' . e($label)
         . ($required ? ' <i class="req">*</i>' : '') . '</span>' . $control
         . ($hint ? '<span class="hint">' . e($hint) . '</span>' : '') . '</label>';
}

/**
 * صورة الحساب: دايرة المعاينة + تكبير + سحب لضبط المنتصف.
 * الناتج بيتبعت في حقل مخفي كصورة مقصوصة جاهزة، ولو المتصفح
 * مشغّلش الجافاسكريبت السيرفر بيقص من النص لوحده — الحالتين شغّالين.
 */
function avatar_field(string $name = 'photo', ?string $currentId = null, string $label = 'صورة الحساب', bool $required = false): string {
    $preview = $currentId
        ? '<img src="' . url('img.php?id=' . e($currentId)) . '" alt="">'
        : '<span class="ph">' . icon('camera', 30) . '</span>';
    // 🔴 مفيش required على input مخفي: كروم بيوقف الإرسال بصمت
    //    برسالة "invalid control is not focusable". الفحص بيتعمل في
    //    الجافاسكريبت (رسالة واضحة) وفي السيرفر (الفحص الحقيقي).
    $req = $required && !$currentId ? ' data-af-required' : '';
    return '<div class="avatar-field" data-avatar-field' . $req . '>'
      . '<span class="lbl">' . e($label) . ($required ? ' <i class="req">*</i>' : '') . '</span>'
      . '<div class="af-row">'
        . '<div class="af-stage">'
          . '<div class="af-ring" data-af-stage>'
            . '<canvas data-af-canvas hidden></canvas>'
            . '<div class="af-preview" data-af-preview>' . $preview . '</div>'
          . '</div>'
          . '<span class="af-ring-note">كده هتبان صورتك</span>'
        . '</div>'
        . '<div class="af-controls">'
          . '<label class="btn ghost sm af-pick">' . icon('upload', 16) . ' اختار صورة'
            . '<input type="file" name="' . e($name) . '" accept="image/png,image/jpeg,image/webp,image/gif" data-af-input hidden>'
          . '</label>'
          . '<div class="af-zoom" hidden data-af-zoomwrap>'
            . '<span class="af-zi">' . icon('zoom', 15) . '</span>'
            . '<input type="range" min="100" max="300" value="100" step="1" data-af-zoom aria-label="تكبير الصورة">'
          . '</div>'
          . '<p class="hint af-hint">اسحب الصورة بصباعك عشان تظبّط المنتصف، وكبّرها بالشريط. '
            . 'الصورة بتتحفظ مربعة فتبان مظبوطة في كل مكان.</p>'
          . '<button type="button" class="linkbtn" data-af-clear hidden>شيل الصورة</button>'
        . '</div>'
      . '</div>'
      . '<input type="hidden" name="photo_crop" data-af-crop>'
    . '</div>';
}

/** رفع صورة بطاقة أو رخصة — صور بس، والـ accept والفحص الاتنين مقفولين على الصور. */
function doc_field(string $name, string $label, string $hint = '', ?string $existingId = null, bool $required = false): string {
    $has = $existingId
        ? '<div class="doc-have">' . icon('check', 15) . ' مرفوعة — '
          . '<a href="' . url('img.php?id=' . e($existingId)) . '" target="_blank" rel="noopener">اعرضها</a></div>'
        : '';
    return '<div class="doc-field" data-doc-field'
      . ($required && !$existingId ? ' data-doc-required' : '') . '>'
      . '<span class="lbl">' . e($label) . ($required ? ' <i class="req">*</i>' : '') . '</span>'
      . '<label class="doc-drop" tabindex="0">'
        . '<span class="doc-thumb" data-doc-thumb>' . icon('image', 26) . '</span>'
        . '<span class="doc-txt"><b>' . ($existingId ? 'غيّر الصورة' : 'ارفع صورة') . '</b>'
        . '<i data-doc-name>JPG · PNG · WEBP — صورة بس</i></span>'
        . '<input type="file" name="' . e($name) . '" accept="image/png,image/jpeg,image/webp,image/gif" hidden data-doc-input>'
      . '</label>'
      . $has
      . ($hint ? '<span class="hint">' . e($hint) . '</span>' : '')
    . '</div>';
}

// ── الهيكل ─────────────────────────────────────────────────────

function bottom_nav(string $current): string {
    $items = [
        ['', 'السواقين', 'home'],
        ['places', 'المحلات', 'store'],
        ['requests', 'الطلبات', 'board'],
        ['prices', 'الأسعار', 'money'],
    ];
    $u = current_user();
    $items[] = $u
        ? [dash_path($u['role']), 'حسابي', 'dash']
        : ['login', 'دخول', 'user'];

    $out = '<nav class="nav" aria-label="التنقل"><ul>';
    foreach ($items as [$path, $label, $ic]) {
        $key = $path === '' ? 'home' : $path;
        $cur = $key === $current ? ' aria-current="page"' : '';
        $out .= '<li><a href="' . url($path) . '"' . $cur . ' data-sfx="tap">'
              . '<span class="nav-ic">' . icon($ic, 22, 'ic') . '</span>' . e($label) . '</a></li>';
    }
    return $out . '</ul><span class="nav-glow" aria-hidden="true"></span></nav>';
}

function topbar(string $title = '', bool $back = false): string {
    $u = current_user();
    $out = '<header class="topbar"><div class="tb-in">';

    if ($back) {
        $out .= '<button type="button" class="tb-btn" data-back aria-label="رجوع">' . icon('back', 20) . '</button>';
    }
    $out .= '<a class="brand" href="' . url('') . '">'
          . '<span class="brand-mark">' . icon('logo', 19) . '</span>'
          . '<span class="brand-tx">' . ($title !== '' ? e($title) : 'في السكة') . '</span></a>';

    $out .= '<div class="tb-actions">';
    $out .= '<button type="button" class="tb-btn" data-sound-toggle aria-label="الصوت">'
          . '<span data-sound-on hidden>' . icon('sound', 19) . '</span>'
          . '<span data-sound-off>' . icon('mute', 19) . '</span></button>';
    $out .= '<button type="button" class="tb-btn" data-theme-toggle aria-label="الوضع الليلي">'
          . '<span data-theme-dark>' . icon('moon', 19) . '</span>'
          . '<span data-theme-light hidden>' . icon('sun', 19) . '</span></button>';

    if ($u) {
        $n = unread_count($u['id']);
        $out .= '<a class="tb-btn bell" href="' . dash_url($u['role']) . '#notifs" aria-label="الإشعارات">'
              . icon('bell', 19) . ($n ? '<i class="dot">' . ($n > 9 ? '٩+' : $n) . '</i>' : '') . '</a>';
        $out .= '<a class="tb-me" href="' . dash_url($u['role']) . '">'
              . avatar($u['name'], $u['avatar_id'], false, 30) . '</a>';
    } else {
        $out .= '<a class="btn tiny" href="' . url('login') . '" data-sfx="tap">دخول</a>';
    }
    $out .= '</div></div></header>';
    return $out;
}

function site_footer(): string {
    $phone = (string) cfg('support_phone');
    return '<footer class="foot">'
      . '<p><a href="' . url('join') . '">اشتغل معانا</a> · <a href="' . url('register') . '">حساب جديد</a>'
      . ' · <a href="' . url('login') . '">دخول</a></p>'
      . '<p><strong>في السكة</strong> خدمة مجتمعية مجانية بتعرض بيانات سواقين ومحلات في المنطقة عشان '
      . 'تسهّل التواصل. إحنا مش طرف في أي اتفاق بينك وبين السائق أو المحل، ومش بناخد عمولة، '
      . 'ومش بنحدد أسعار ملزمة، ومش مسؤولين عن جودة الخدمة أو أي خلاف.</p>'
      . '<p>«موثّق» معناها إننا شوفنا بطاقة ورخصة السائق. مش ضمان.</p>'
      . ($phone ? '<p>لأي شكوى أو طلب حذف بياناتك: <a href="' . e(tel_link($phone)) . '">' . e($phone) . '</a></p>' : '')
      . '</footer>';
}

/**
 * الصفحة كلها.
 * $opts: nav (مفتاح التبويب), bar (شريط علوي), back, wide, title_bar
 */
function layout(string $title, string $body, array $opts = []): void {
    $t    = $title ? $title . ' · في السكة' : 'في السكة — سواقين ومحلات القيصرية';
    $nav  = $opts['nav'] ?? null;
    $bar  = $opts['bar'] ?? true;
    $back = !empty($opts['back']);
    $f    = take_flash();
    ?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($t) ?></title>
<meta name="description" content="دليل مجاني لسواقين ومحلات القيصرية والمناطق المجاورة. شوف مين متاح دلوقتي، اطلب، وتابع طلبك من حسابك.">
<meta name="theme-color" content="#06795F" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#060D14" media="(prefers-color-scheme: dark)">
<meta name="format-detection" content="telephone=no">
<meta name="color-scheme" content="light dark">
<link rel="icon" href="<?= url('assets/icons/favicon.svg') ?>" type="image/svg+xml">
<link rel="icon" href="<?= url('icon.php?s=32') ?>" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="<?= url('icon.php?s=180') ?>">
<link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="في السكة">
<meta name="mobile-web-app-capable" content="yes">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= url('assets/app.css') ?>?v=<?= ASSET_V ?>">
<script>
/* الثيم بيتحط قبل أي رسم عشان الشاشة متلمعش أبيض في الوضع الليلي */
try{var m=localStorage.getItem('sekka-theme');if(m)document.documentElement.dataset.theme=m;}catch(e){}
</script>
</head>
<body class="<?= $nav !== null ? 'app' : '' ?><?= !empty($opts['wide']) ? ' wide' : '' ?>">
<a class="skip" href="#main">تخطّي للمحتوى</a>
<?= $bar ? topbar($opts['bar_title'] ?? '', $back) : '' ?>
<?= $body ?>
<?= $nav !== null ? bottom_nav($nav) : '' ?>
<div class="toasts" id="toasts" aria-live="polite"></div>
<?php if ($f): ?>
<script>window.__flash = <?= json_encode($f, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php endif; ?>
<script src="<?= url('assets/app.js') ?>?v=<?= ASSET_V ?>" defer></script>
</body>
</html>
    <?php
}

/** عنوان صفحة داخلي. */
function page_head(string $title, string $sub = '', string $ic = ''): string {
    return '<div class="phead">'
      . ($ic ? '<span class="phead-ic">' . icon($ic, 22) . '</span>' : '')
      . '<div><h1>' . e($title) . '</h1>'
      . ($sub ? '<p class="lede">' . e($sub) . '</p>' : '') . '</div></div>';
}

/** كارت رقم في الداش بورد. */
function stat_card(string $label, string|int $value, string $ic, string $tone = ''): string {
    return '<div class="stat ' . e($tone) . '"><span class="st-ic">' . icon($ic, 20) . '</span>'
         . '<b class="st-v" data-count="' . e((string) $value) . '">' . e((string) $value) . '</b>'
         . '<span class="st-l">' . e($label) . '</span></div>';
}
