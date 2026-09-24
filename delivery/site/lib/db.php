<?php
declare(strict_types=1);

function cfg(?string $key = null): mixed {
    static $c = null;
    if ($c === null) {
        $path = __DIR__ . '/../config.php';
        if (!is_file($path)) {
            http_response_code(500);
            exit('<div style="font:16px system-ui;direction:rtl;padding:30px">'
               . 'ملف <b>config.php</b> مش موجود. انسخ <b>config.example.php</b> باسم '
               . '<b>config.php</b> وحط فيه بيانات قاعدة البيانات.</div>');
        }
        $c = require $path;
    }
    return $key === null ? $c : ($c[$key] ?? null);
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', cfg('db_host'), cfg('db_name'));
    try {
        $pdo = new PDO($dsn, cfg('db_user'), cfg('db_pass'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('DB connect failed: ' . $e->getMessage());
        exit('<div style="font:16px system-ui;direction:rtl;padding:30px">'
           . 'مقدرناش نوصل لقاعدة البيانات. راجع بيانات الاتصال في <b>config.php</b>.</div>');
    }
    return $pdo;
}

/** كل الاستعلامات بتعدي من هنا — بارامترات مربوطة، مفيش دمج نصوص. */
function q(string $sql, array $args = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st;
}
function rows(string $sql, array $args = []): array { return q($sql, $args)->fetchAll(); }
function row(string $sql, array $args = []): ?array { $r = q($sql, $args)->fetch(); return $r === false ? null : $r; }
function val(string $sql, array $args = []): mixed { $r = q($sql, $args)->fetch(PDO::FETCH_NUM); return $r === false ? null : $r[0]; }

function new_id(): string { return bin2hex(random_bytes(16)); }
