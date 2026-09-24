<?php
declare(strict_types=1);

const SERVICES = [
    'delivery'    => 'دليفري',
    'tuktuk'      => 'توك توك',
    'mahalla_run' => 'مشوار المحلة',
    'goods'       => 'نقل بضاعة',
    'bicycle'     => 'عجلة',
];
const CATEGORIES = [
    'restaurant'  => 'مطاعم',
    'supermarket' => 'سوبر ماركت',
    'grocery'     => 'بقالة',
    'produce'     => 'خضار وفاكهة',
    'bakery'      => 'مخبز',
    'herbalist'   => 'عطارة',
    'butcher'     => 'جزارة',
    'sweets'      => 'حلويات',
    'pharmacy'    => 'صيدلية',
    'stationery'  => 'مكتبة',
    'hardware'    => 'أدوات ومستلزمات',
    'phones'      => 'موبايلات',
    'other'       => 'غير كده',
];
const REPORT_REASONS = [
    'rude'         => 'معاملة وحشة',
    'overcharge'   => 'طلب سعر مبالغ فيه',
    'no_show'      => 'اتفق ومجاش',
    'unsafe'       => 'قيادة خطر أو سلوك مقلق',
    'wrong_number' => 'الرقم غلط أو مش بيرد',
    'other'        => 'حاجة تانية',
];

/** التوفر بينتهي لوحده بعد ٤ ساعات — القايمة الكذابة بتضيّع ثقة الناس. */
const AVAILABILITY_HOURS = 4;

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function is_live(array $p): bool {
    if (empty($p['is_available']) || empty($p['available_at'])) return false;
    return (time() - strtotime($p['available_at'])) < AVAILABILITY_HOURS * 3600;
}

function since_ar(?string $ts): string {
    if (!$ts) return 'مش معروف';
    $m = (int) floor((time() - strtotime($ts)) / 60);
    if ($m < 1)  return 'دلوقتي';
    if ($m === 1) return 'من دقيقة';
    if ($m === 2) return 'من دقيقتين';
    if ($m < 11) return "من $m دقايق";
    if ($m < 60) return "من $m دقيقة";
    $h = intdiv($m, 60);
    if ($h === 1) return 'من ساعة';
    if ($h === 2) return 'من ساعتين';
    if ($h < 11) return "من $h ساعات";
    if ($h < 24) return "من $h ساعة";
    $d = intdiv($h, 24);
    return $d === 1 ? 'من إمبارح' : "من $d يوم";
}

function money(mixed $v): string {
    $n = (float) $v;
    return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
}

function wa_link(?string $phone, string $text = ''): string {
    if (!$phone) return '';
    $p = preg_replace('/\D/', '', $phone);
    if (str_starts_with($p, '0')) $p = '2' . $p;
    return 'https://wa.me/' . $p . ($text ? '?text=' . rawurlencode($text) : '');
}

function tel_link(?string $phone): string {
    return 'tel:' . preg_replace('/[^\d+]/', '', (string)$phone);
}

function mask_phone(string $phone): string {
    return mb_substr($phone, 0, 4) . '••••' . mb_substr($phone, -2);
}

function valid_phone(string $p, int $min = 9, int $max = 11): bool {
    return (bool) preg_match('/^0\d{' . ($min - 1) . ',' . ($max - 1) . '}$/', $p);
}

/** لون ثابت لكل اسم — نفس الشخص بياخد نفس اللون دايمًا. */
function avatar_bg(string $name): string {
    $pal = [
        'linear-gradient(140deg,#00C07E,#00815A)', 'linear-gradient(140deg,#5B9BFF,#2452D6)',
        'linear-gradient(140deg,#FFC24D,#F08C00)', 'linear-gradient(140deg,#B07CFF,#6D2FD4)',
        'linear-gradient(140deg,#4FD1D9,#12879B)', 'linear-gradient(140deg,#FF8A8A,#D63030)',
    ];
    return $pal[abs(crc32($name)) % count($pal)];
}

function services_of(array $p): array {
    return array_values(array_filter(explode(',', (string)($p['services'] ?? ''))));
}

function url(string $path = ''): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    if ($base === '.' || $base === '/') $base = '';
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never { header('Location: ' . url($path)); exit; }

function post(string $k, int $max = 500): string {
    return mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
}
function get(string $k, int $max = 100): string {
    return mb_substr(trim((string)($_GET[$k] ?? '')), 0, $max);
}
