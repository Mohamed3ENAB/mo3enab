<?php
declare(strict_types=1);

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/ui.php';
require __DIR__ . '/lib/images.php';
require __DIR__ . '/lib/queries.php';
require __DIR__ . '/lib/signup.php';
require __DIR__ . '/views/_parts.php';

start_session();

// المسار بعد مجلد التطبيق
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($base !== '' && str_starts_with($path, $base)) $path = substr($path, strlen($base));
$path = trim($path, '/');
$seg  = $path === '' ? [] : explode('/', $path);

// قاعدة البيانات لسه متعملتش؟ وجّه للتنصيب
if (!is_file(__DIR__ . '/.installed') && ($seg[0] ?? '') !== 'install.php') {
    // وجود الجدول هو الدليل — مش وجود صفوف فيه، عشان قاعدة
    // لسه فاضية ماتوديناش لصفحة التنصيب كل مرة.
    $ok = false;
    try { $ok = table_exists('users'); } catch (Throwable) {}
    if (!$ok) { header('Location: ' . url('install.php')); exit; }
}

// 🔴 كل POST بيتفحص هنا مرة واحدة — مفيش صفحة بتفتكر تعمل الفحص ده بنفسها
guard_post();

$view = __DIR__ . '/views/';

switch ($seg[0] ?? '') {
    // ── الدليل العام ───────────────────────────────────────────
    case '':         require $view . 'home.php';     break;
    case 'places':   require $view . 'places.php';   break;
    case 'requests': require $view . 'requests.php'; break;
    case 'prices':   require $view . 'prices.php';   break;
    case 'join':     require $view . 'join.php';     break;
    case 'p': $id = $seg[1] ?? ''; require $view . 'provider.php'; break;
    case 'm': $id = $seg[1] ?? ''; require $view . 'place.php';    break;
    case 'd': $token = $seg[1] ?? ''; require $view . 'driver.php'; break;
    case 't':
        if (($seg[1] ?? '') === 'p') { $ptoken = $seg[2] ?? ''; $reqId = $seg[3] ?? ''; require $view . 'thread_driver.php'; }
        else                         { $token = $seg[1] ?? ''; require $view . 'thread_owner.php'; }
        break;

    // ── الحسابات ───────────────────────────────────────────────
    case 'login':    require $view . 'login.php';  break;
    case 'logout':   logout(); set_flash('ok', 'خرجت من حسابك. سلامتك.'); redirect('');
    case 'register':
        switch ($seg[1] ?? '') {
            case 'customer': $reg_role = 'customer'; require $view . 'register_customer.php'; break;
            case 'driver':   require $view . 'register_driver.php';   break;
            case 'merchant': require $view . 'register_merchant.php'; break;
            default:         require $view . 'register.php';
        }
        break;
    case 'account':  require $view . 'account.php'; break;
    case 'fav':      require $view . 'fav.php';     break;

    // ── الداش بوردات ───────────────────────────────────────────
    case 'dashboard':
        $u = require_login();
        switch ($seg[1] ?? '') {
            case 'pending':  require $view . 'dash_pending.php';  break;
            case 'customer': require $view . 'dash_customer.php'; break;
            case 'driver':   require $view . 'dash_driver.php';   break;
            case 'merchant': require $view . 'dash_merchant.php'; break;
            default:         redirect(dash_path($u['role']));
        }
        break;

    case 'admin':
        if (($seg[1] ?? '') === 'login')  { redirect('login'); }
        if (($seg[1] ?? '') === 'logout') { logout(); redirect('login'); }
        $section = $seg[1] ?? 'overview';
        require $view . 'admin.php';
        break;

    default:
        http_response_code(404);
        ob_start(); ?>
        <main class="wrap page" id="main">
          <div class="empty big">
            <span class="empty-ic"><?= icon('search', 34) ?></span>
            <h1>الصفحة مش موجودة</h1>
            <p class="lede">اللينك ده مش شغال. يمكن اتغيّر أو انتهى.</p>
            <a class="btn call wide" href="<?= url('') ?>">رجوع للدليل</a>
          </div>
        </main>
        <?php
        layout('مش موجود', (string) ob_get_clean(), ['nav' => 'home']);
}
