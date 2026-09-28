<?php
declare(strict_types=1);

$u = require_login();
if (!is_post()) redirect('');

$providerId = post('provider_id', 32) ?: null;
$placeId    = post('place_id', 32) ?: null;
$back       = (string) ($_POST['back'] ?? '');

if ($providerId || $placeId) {
    if (is_favorite($u['id'], $providerId, $placeId)) {
        q('DELETE FROM favorites WHERE user_id = ?
             AND ((? IS NOT NULL AND provider_id = ?) OR (? IS NOT NULL AND place_id = ?))',
          [$u['id'], $providerId, $providerId, $placeId, $placeId]);
        set_flash('ok', 'اتشال من المفضلة.');
    } else {
        q('INSERT INTO favorites (id, user_id, provider_id, place_id) VALUES (?,?,?,?)',
          [new_id(), $u['id'], $providerId, $placeId]);
        set_flash('ok', 'اتضاف للمفضلة ❤');
    }
}

// نرجّعه لنفس الصفحة، بس بمسار من عندنا مش من المستخدم
if ($back !== '' && str_starts_with($back, '/') && !str_starts_with($back, '//')) {
    header('Location: ' . $back);
    exit;
}
redirect(dash_path($u['role']));
