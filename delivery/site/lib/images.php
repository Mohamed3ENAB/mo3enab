<?php
declare(strict_types=1);

const MAX_UPLOAD = 6291456; // ٦ ميجا قبل التصغير
const THUMB      = 256;

/**
 * بتصغّر الصورة لـ 256×256 webp قبل ما تتخزن في قاعدة البيانات.
 * صورة موبايل 4 ميجا بتبقى ~15 كيلو، فمفيش حاجة اسمها مجلد رفع
 * محتاج صلاحيات ولا مساحة بتكبر من غير ما تاخد بالك.
 * بترجّع: id الصورة، أو null لو مفيش صورة، أو رسالة خطأ.
 */
function store_image(string $field): array {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['id' => null];
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK)  return ['error' => 'مقدرناش نرفع الصورة. جرّب تاني.'];
    if ($f['size'] > MAX_UPLOAD)        return ['error' => 'الصورة كبيرة أوي. أقصى حجم ٦ ميجا.'];
    if (!is_uploaded_file($f['tmp_name'])) return ['error' => 'الملف مش صح.'];

    $raw = file_get_contents($f['tmp_name']);
    if ($raw === false || $raw === '') return ['error' => 'مقدرناش نقرا الصورة.'];

    $info = @getimagesizefromstring($raw);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return ['error' => 'الصورة لازم تكون JPG أو PNG أو WEBP.'];
    }

    $src = @imagecreatefromstring($raw);
    if (!$src) return ['error' => 'مقدرناش نقرا الصورة. جرّب صورة تانية.'];

    [$w, $h] = [imagesx($src), imagesy($src)];
    $side = min($w, $h);
    $dst  = imagecreatetruecolor(THUMB, THUMB);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled(
        $dst, $src, 0, 0,
        (int) (($w - $side) / 2), (int) (($h - $side) / 2),
        THUMB, THUMB, $side, $side
    );
    imagedestroy($src);

    ob_start();
    if (function_exists('imagewebp')) { imagewebp($dst, null, 80); $mime = 'image/webp'; }
    else                             { imagejpeg($dst, null, 82); $mime = 'image/jpeg'; }
    $out = (string) ob_get_clean();
    imagedestroy($dst);

    $id = new_id();
    q('INSERT INTO images (id, mime, bytes, byte_size) VALUES (?,?,?,?)',
      [$id, $mime, $out, strlen($out)]);
    return ['id' => $id];
}
