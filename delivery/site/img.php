<?php
declare(strict_types=1);
require __DIR__ . '/lib/db.php';

$id = (string) ($_GET['id'] ?? '');
if (!preg_match('/^[0-9a-f]{32}$/', $id)) { http_response_code(404); exit('Not found'); }

$img = row('SELECT mime, bytes FROM images WHERE id = ?', [$id]);
if (!$img) { http_response_code(404); exit('Not found'); }

// المحتوى ثابت والـ id عشوائي، فبنكاشها للأبد
header('Content-Type: ' . $img['mime']);
header('Content-Length: ' . strlen($img['bytes']));
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
echo $img['bytes'];
