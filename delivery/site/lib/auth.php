<?php
declare(strict_types=1);

const ADMIN_COOKIE = 'sekka_admin';
const ADMIN_TTL    = 43200; // ١٢ ساعة

function sign(string $payload): string {
    return hash_hmac('sha256', $payload, (string) cfg('session_secret'));
}

/** المقارنة بوقت ثابت عشان كلمة السر ماتتخمنش بالتوقيت. */
function check_password(string $input): bool {
    $expected = (string) cfg('admin_password');
    if (strlen($expected) < 4) return false;
    return hash_equals($expected, $input);
}

function login_admin(): void {
    $payload = (time() + ADMIN_TTL) . '.' . bin2hex(random_bytes(8));
    setcookie(ADMIN_COOKIE, $payload . '.' . sign($payload), [
        'expires'  => time() + ADMIN_TTL,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
}

function logout_admin(): void {
    setcookie(ADMIN_COOKIE, '', ['expires' => 1, 'path' => '/']);
}

function is_admin(): bool {
    $v = $_COOKIE[ADMIN_COOKIE] ?? '';
    $i = strrpos($v, '.');
    if ($i === false) return false;
    $payload = substr($v, 0, $i);
    $mac     = substr($v, $i + 1);
    if (!hash_equals(sign($payload), $mac)) return false;
    return ((int) explode('.', $payload)[0]) > time();
}

function require_admin(): void {
    if (!is_admin()) redirect('admin/login');
}

/** توكن CSRF لكل نماذج الإدارة. */
function csrf_token(): string {
    if (empty($_COOKIE['sekka_csrf'])) {
        $t = bin2hex(random_bytes(16));
        setcookie('sekka_csrf', $t, ['expires' => time() + 86400, 'path' => '/', 'samesite' => 'Lax']);
        $_COOKIE['sekka_csrf'] = $t;
    }
    return $_COOKIE['sekka_csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
function csrf_ok(): bool {
    return hash_equals($_COOKIE['sekka_csrf'] ?? '', (string)($_POST['_csrf'] ?? ''));
}
