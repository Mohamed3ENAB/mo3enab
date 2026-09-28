<?php
declare(strict_types=1);

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/auth.php';

$id = (string) ($_GET['id'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $id)) { http_response_code(404); exit; }

$img = row('SELECT mime, bytes, is_private FROM images WHERE id = ?', [$id]);
if (!$img) { http_response_code(404); exit; }

if ((int) $img['is_private'] === 1) {
    // 🔴 البطاقة والرخصة. الإدارة أو صاحبها بس — ومفيش كاش عشان
    //    الصورة ماتفضلش في المتصفح بعد ما يقفل حسابه.
    start_session();
    $me = uid();
    $allowed = is_admin() || ($me && val(
        'SELECT COUNT(*) FROM user_docs WHERE image_id = ? AND user_id = ?', [$id, $me]
    ));
    if (!$allowed) { http_response_code(403); exit; }
    header('Cache-Control: private, no-store, max-age=0');
} else {
    $etag = '"' . $id . '"';
    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=2592000, immutable');
}

header('Content-Type: ' . $img['mime']);
header('Content-Length: ' . strlen($img['bytes']));
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; sandbox');
echo $img['bytes'];
