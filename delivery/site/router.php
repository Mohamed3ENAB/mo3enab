<?php
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$f = __DIR__ . $p;
if ($p !== '/' && is_file(__DIR__ . $p)) return false;   // ملفات حقيقية
return require __DIR__ . '/index.php';
