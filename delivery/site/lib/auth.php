<?php
declare(strict_types=1);

const SESSION_NAME = 'sekka_sid';
const SESSION_TTL  = 2592000;   // ٣٠ يوم لو فاكرني
const MAX_FAILS    = 6;         // بعدها القفل ١٥ دقيقة
const LOCK_MINUTES = 15;

/** كوكي جلسة متشدّد. لازم يتنادى قبل أي output. */
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
    session_start();
    // تدوير معرّف الجلسة كل ساعة — يقلل خطر تثبيت الجلسة
    if (empty($_SESSION['born'])) {
        $_SESSION['born'] = time();
    } elseif (time() - (int)$_SESSION['born'] > 3600) {
        session_regenerate_id(true);
        $_SESSION['born'] = time();
    }
}

// ── المستخدم الحالي ────────────────────────────────────────────

function current_user(): ?array {
    static $cached = false, $user = null;
    if ($cached) return $user;
    $cached = true;
    $id = $_SESSION['uid'] ?? null;
    if (!$id) return $user = null;
    $u = row('SELECT * FROM users WHERE id = ?', [$id]);
    // الحساب اتمسح أو اتوقف وهو داخل — نخرّجه فورًا.
    // المرفوض بيفضل داخل عشان يقرا سبب الرفض في صفحة الانتظار.
    if (!$u || $u['status'] === 'suspended') { unset($_SESSION['uid']); return $user = null; }
    return $user = $u;
}

function uid(): ?string { $u = current_user(); return $u['id'] ?? null; }
function role(): string { $u = current_user(); return $u['role'] ?? 'guest'; }
function is_logged_in(): bool { return current_user() !== null; }
function is_admin(): bool { return role() === 'admin'; }
function is_approved(): bool { $u = current_user(); return $u !== null && $u['status'] === 'active'; }

/** مسار الداش بورد حسب الدور (من غير prefix). */
function dash_path(?string $role = null): string {
    return match ($role ?? role()) {
        'admin'    => 'admin',
        'driver'   => 'dashboard/driver',
        'merchant' => 'dashboard/merchant',
        'customer' => 'dashboard/customer',
        default    => 'login',
    };
}
function dash_url(?string $role = null): string { return url(dash_path($role)); }

// ── الدخول والخروج ─────────────────────────────────────────────

/** بترجّع ['user'=>..] أو ['error'=>'..'] */
function attempt_login(string $phone, string $password): array {
    $u = row('SELECT * FROM users WHERE phone = ?', [$phone]);
    if (!$u) {
        // نفس الرسالة في الحالتين عشان محدش يعرف الرقم مسجّل ولا لأ
        return ['error' => 'الرقم أو كلمة السر غلط.'];
    }
    if ($u['locked_until'] && strtotime($u['locked_until']) > time()) {
        $mins = max(1, (int) ceil((strtotime($u['locked_until']) - time()) / 60));
        return ['error' => "الحساب مقفول مؤقتًا. جرّب تاني بعد $mins دقيقة."];
    }
    if (!password_verify($password, $u['pass_hash'])) {
        $fails = ((int) $u['fail_count']) + 1;
        if ($fails >= MAX_FAILS) {
            q('UPDATE users SET fail_count = 0, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?',
              [LOCK_MINUTES, $u['id']]);
            return ['error' => 'حاولت كتير. الحساب اتقفل ' . LOCK_MINUTES . ' دقيقة.'];
        }
        q('UPDATE users SET fail_count = ? WHERE id = ?', [$fails, $u['id']]);
        return ['error' => 'الرقم أو كلمة السر غلط.'];
    }
    if ($u['status'] === 'suspended') return ['error' => 'الحساب دا موقوف. كلّم الإدارة.'];
    if ($u['status'] === 'rejected')  return ['error' => 'طلبك اترفض. ' . ($u['reject_reason'] ?: '')];

    // كلمة السر قديمة الخوارزمية؟ نجدّد الهاش بهدوء
    if (password_needs_rehash($u['pass_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET pass_hash = ? WHERE id = ?',
          [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    q('UPDATE users SET fail_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?', [$u['id']]);

    session_regenerate_id(true);
    $_SESSION['uid']  = $u['id'];
    $_SESSION['born'] = time();
    return ['user' => $u];
}

function login_as(string $userId): void {
    session_regenerate_id(true);
    $_SESSION['uid']  = $userId;
    $_SESSION['born'] = time();
}

/**
 * بنفضّي الجلسة وناخد معرّف جديد بدل ما نقتلها خالص:
 * كده الجلسة القديمة بتتمسح من السيرفر، وفي نفس الوقت لسه
 * عندنا جلسة شغّالة نحط فيها رسالة «خرجت من حسابك».
 */
function logout(): void {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['born'] = time();
}

// ── الحُرّاس ───────────────────────────────────────────────────

function require_login(): array {
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        set_flash('bad', 'سجّل دخولك الأول.');
        redirect('login');
    }
    return $u;
}

/** الدور لازم يكون واحد من دول، والحساب لازم يكون متفعّل. */
function require_role(string ...$roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        set_flash('bad', 'الصفحة دي مش من حقك.');
        redirect(dash_path($u['role']));
    }
    if ($u['status'] !== 'active') redirect('dashboard/pending');
    return $u;
}

function require_admin(): array { return require_role('admin'); }

// ── CSRF ───────────────────────────────────────────────────────

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
function csrf_ok(): bool {
    return hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['_csrf'] ?? ''));
}
/** كل POST بيعدي من هنا الأول. */
function guard_post(): void {
    if (is_post() && !csrf_ok()) {
        http_response_code(419);
        exit('<div style="font:16px system-ui;direction:rtl;padding:30px">'
           . 'الصفحة قعدت مفتوحة كتير. اقفلها وافتحها تاني وجرّب.</div>');
    }
}

// ── كلمات السر ─────────────────────────────────────────────────

function hash_password(string $p): string { return password_hash($p, PASSWORD_DEFAULT); }

function password_problem(string $p): ?string {
    if (mb_strlen($p) < 8)  return 'كلمة السر لازم ٨ حروف أو أرقام على الأقل.';
    if (mb_strlen($p) > 72) return 'كلمة السر طويلة أوي.';
    if (preg_match('/^\d+$/', $p)) return 'متخليهاش أرقام بس — زوّد حروف.';
    return null;
}

// ── إشعارات داخل التطبيق ───────────────────────────────────────

function notify(string $userId, string $title, string $body = '', string $href = '', string $icon = 'info'): void {
    if ($userId === '') return;
    q('INSERT INTO notifications (id, user_id, title, body, href, icon) VALUES (?,?,?,?,?,?)',
      [new_id(), $userId, mb_substr($title, 0, 120), mb_substr($body, 0, 300), mb_substr($href, 0, 160), $icon]);
}

function notify_admins(string $title, string $body = '', string $href = '', string $icon = 'info'): void {
    foreach (rows("SELECT id FROM users WHERE role = 'admin' AND status = 'active'") as $a) {
        notify($a['id'], $title, $body, $href, $icon);
    }
}

function unread_count(?string $userId = null): int {
    $userId ??= uid();
    if (!$userId) return 0;
    return (int) val('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
}
