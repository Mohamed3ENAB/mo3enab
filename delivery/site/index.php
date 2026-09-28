<?php
declare(strict_types=1);

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/ui.php';
require __DIR__ . '/lib/images.php';
require __DIR__ . '/lib/queries.php';
require __DIR__ . '/views/_parts.php';

// المسار بعد مجلد التطبيق
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($base !== '' && str_starts_with($path, $base)) $path = substr($path, strlen($base));
$path = trim($path, '/');
$seg  = $path === '' ? [] : explode('/', $path);

// قاعدة البيانات لسه متعملتش؟ وجّه للتنصيب
if (!is_file(__DIR__ . '/.installed') && ($seg[0] ?? '') !== 'install.php') {
    $ok = false;
    try { $ok = (bool) val('SELECT 1 FROM zones LIMIT 1'); } catch (Throwable) {}
    if (!$ok) { header('Location: ' . url('install.php')); exit; }
}

$view = __DIR__ . '/views/';

switch ($seg[0] ?? '') {
    case '':         require $view . 'home.php';     break;
    case 'places':   require $view . 'places.php';   break;
    case 'requests': require $view . 'requests.php'; break;
    case 'prices':   require $view . 'prices.php';   break;
    case 'join':
        if (($seg[1] ?? '') === 'apply') { require $view . 'apply.php'; }
        else                             { require $view . 'join.php'; }
        break;
    case 'p': $id = $seg[1] ?? ''; require $view . 'provider.php'; break;
    case 'm': $id = $seg[1] ?? ''; require $view . 'place.php';    break;
    case 'd': $token = $seg[1] ?? ''; require $view . 'driver.php'; break;
    case 't':
        if (($seg[1] ?? '') === 'p') { $ptoken = $seg[2] ?? ''; $reqId = $seg[3] ?? ''; require $view . 'thread_driver.php'; }
        else                         { $token = $seg[1] ?? ''; require $view . 'thread_owner.php'; }
        break;
    case 'admin':
        if (($seg[1] ?? '') === 'login')  { require $view . 'admin_login.php'; }
        elseif (($seg[1] ?? '') === 'logout') { logout_admin(); redirect('admin/login'); }
        else { require $view . 'admin.php'; }
        break;
    default:
        http_response_code(404);
        layout('مش موجود',
            '<main class="wrap page"><h1>الصفحة مش موجودة</h1>'
          . '<p class="lede">اللينك ده مش شغال. يمكن اتغيّر أو انتهى.</p>'
          . '<a class="btn call wide" href="' . url('') . '">رجوع للدليل</a></main>');
}
