<?php
declare(strict_types=1);

const MAX_UPLOAD  = 8388608;   // ٨ ميجا قبل التصغير
const AVATAR_SIDE = 512;       // صورة الحساب — مربعة دايمًا
const DOC_MAX     = 1400;      // البطاقة والرخصة — الكلام لازم يفضل مقروء
const MAX_CROP_B64 = 6000000;  // أقصى حجم للقصّة الجاية من المتصفح

/** الصيغ المقبولة. PDF وأي ملف تاني مرفوض — صور بس. */
const OK_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF, IMAGETYPE_BMP];

function gd_ready(): bool { return function_exists('imagecreatefromstring'); }

/**
 * بتقرا الملف المرفوع وترجّع الـ bytes الخام.
 * ['raw'=>..,'tmp'=>..] أو ['error'=>..] أو ['raw'=>null] لو مفيش ملف.
 */
function read_upload(string $field): array {
    $f = $_FILES[$field] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return ['raw' => null];

    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        return ['error' => 'الصورة كبيرة أوي على السيرفر. صغّرها أو اختار صورة تانية.'];
    }
    if ($f['error'] !== UPLOAD_ERR_OK)     return ['error' => 'مقدرناش نرفع الصورة. جرّب تاني.'];
    if (!is_uploaded_file($f['tmp_name'])) return ['error' => 'الملف مش صح.'];
    if ($f['size'] > MAX_UPLOAD)           return ['error' => 'الصورة كبيرة أوي. أقصى حجم ٨ ميجا.'];

    $raw = file_get_contents($f['tmp_name']);
    if ($raw === false || $raw === '') return ['error' => 'مقدرناش نقرا الصورة.'];

    // 🔴 الفحص الحقيقي هنا: بنقرا بايتات الصورة نفسها، مش الامتداد ولا
    //    الـ mime اللي المتصفح بعته — الاتنين دول ممكن يتزوّروا.
    $info = @getimagesizefromstring($raw);
    if (!$info || !in_array($info[2], OK_TYPES, true)) {
        return ['error' => 'لازم صورة (JPG أو PNG أو WEBP). الملفات زي PDF أو Word مش مقبولة.'];
    }
    return ['raw' => $raw, 'tmp' => $f['tmp_name'], 'type' => $info[2]];
}

/** صورة الموبايل بتيجي مقلوبة كتير — بنعدّلها من بيانات EXIF. */
function fix_orientation(GdImage $img, ?string $tmpPath): GdImage {
    if (!$tmpPath || !function_exists('exif_read_data')) return $img;
    $exif = @exif_read_data($tmpPath);
    $o = (int) ($exif['Orientation'] ?? 0);
    $deg = match ($o) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
    if ($deg === 0) return $img;
    $rot = @imagerotate($img, (float) $deg, 0);
    if ($rot === false) return $img;
    imagedestroy($img);
    return $rot;
}

/** بتكتب الصورة webp لو متاح، وإلا jpeg. */
function encode_image(GdImage $img, int $quality = 82): array {
    ob_start();
    if (function_exists('imagewebp')) { imagewebp($img, null, $quality); $mime = 'image/webp'; }
    else                              { imagejpeg($img, null, $quality); $mime = 'image/jpeg'; }
    return [(string) ob_get_clean(), $mime];
}

function save_image_bytes(string $bytes, string $mime, int $w, int $h, bool $private = false): string {
    $id = new_id();
    q('INSERT INTO images (id, mime, bytes, byte_size, width, height, is_private) VALUES (?,?,?,?,?,?,?)',
      [$id, $mime, $bytes, strlen($bytes), $w, $h, $private ? 1 : 0]);
    return $id;
}

function delete_image(?string $id): void {
    if ($id) q('DELETE FROM images WHERE id = ?', [$id]);
}

/** مربع مقصوص من النص — دي اللي بتخلي الصورة تبان كصورة حساب. */
function square_crop(GdImage $src, int $side = AVATAR_SIDE): GdImage {
    $w = imagesx($src); $h = imagesy($src);
    $cut = min($w, $h);
    $dst = imagecreatetruecolor($side, $side);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled(
        $dst, $src, 0, 0,
        (int) (($w - $cut) / 2), (int) (($h - $cut) / 2),
        $side, $side, $cut, $cut
    );
    return $dst;
}

/**
 * صورة الحساب.
 * لو المتصفح بعت قصّة جاهزة (المستخدم حرّك الصورة وكبّرها في الدايرة)
 * بناخدها زي ما هي. لو مبعتش — بنقص من النص لوحدنا.
 * الناتج دايمًا مربع AVATAR_SIDE، فكل الصور بتبان متساوية في الدايرة.
 */
function store_avatar(string $field, string $cropField = 'photo_crop'): array {
    if (!gd_ready()) return ['error' => 'السيرفر مفيهوش مكتبة الصور GD. كلّم الاستضافة.'];

    $cropped = (string) ($_POST[$cropField] ?? '');
    if ($cropped !== '' && strlen($cropped) <= MAX_CROP_B64
        && preg_match('#^data:image/(png|jpeg|webp);base64,#', $cropped)) {
        $b64 = substr($cropped, strpos($cropped, ',') + 1);
        $raw = base64_decode($b64, true);
        if ($raw !== false && $raw !== '') {
            $info = @getimagesizefromstring($raw);
            if ($info && in_array($info[2], OK_TYPES, true)) {
                $src = @imagecreatefromstring($raw);
                if ($src) {
                    $dst = square_crop($src, AVATAR_SIDE);
                    imagedestroy($src);
                    [$bytes, $mime] = encode_image($dst, 84);
                    imagedestroy($dst);
                    return ['id' => save_image_bytes($bytes, $mime, AVATAR_SIDE, AVATAR_SIDE)];
                }
            }
        }
        // القصّة بايظة؟ بنكمل بالملف الأصلي بدل ما نوقف المستخدم
    }

    $up = read_upload($field);
    if (isset($up['error'])) return $up;
    if ($up['raw'] === null) return ['id' => null];

    $src = @imagecreatefromstring($up['raw']);
    if (!$src) return ['error' => 'مقدرناش نقرا الصورة. جرّب صورة تانية.'];
    $src = fix_orientation($src, $up['tmp'] ?? null);

    $dst = square_crop($src, AVATAR_SIDE);
    imagedestroy($src);
    [$bytes, $mime] = encode_image($dst, 84);
    imagedestroy($dst);
    return ['id' => save_image_bytes($bytes, $mime, AVATAR_SIDE, AVATAR_SIDE)];
}

/**
 * البطاقة أو الرخصة: صورة كاملة من غير قص (الأركان مهمة)،
 * أكبر ضلع DOC_MAX، وبتتخزن كـ private فمحدش يفتحها غير
 * صاحبها أو الإدارة.
 */
function store_doc(string $field): array {
    if (!gd_ready()) return ['error' => 'السيرفر مفيهوش مكتبة الصور GD. كلّم الاستضافة.'];

    $up = read_upload($field);
    if (isset($up['error'])) return $up;
    if ($up['raw'] === null) return ['id' => null];

    $src = @imagecreatefromstring($up['raw']);
    if (!$src) return ['error' => 'مقدرناش نقرا الصورة. جرّب صورة تانية.'];
    $src = fix_orientation($src, $up['tmp'] ?? null);

    $w = imagesx($src); $h = imagesy($src);
    if (max($w, $h) > DOC_MAX) {
        $scale = DOC_MAX / max($w, $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        $src = $dst; $w = $nw; $h = $nh;
    }
    [$bytes, $mime] = encode_image($src, 80);
    imagedestroy($src);

    if (strlen($bytes) > 12000000) return ['error' => 'الصورة كبيرة أوي بعد التصغير. جرّب صورة أوضح وأصغر.'];
    return ['id' => save_image_bytes($bytes, $mime, $w, $h, true)];
}

/** بتربط صورة مستند بالمستخدم، وبتشيل القديمة من نفس النوع. */
function attach_doc(string $userId, string $kind, string $imageId): void {
    foreach (rows('SELECT image_id FROM user_docs WHERE user_id = ? AND kind = ?', [$userId, $kind]) as $old) {
        delete_image($old['image_id']);
    }
    q('DELETE FROM user_docs WHERE user_id = ? AND kind = ?', [$userId, $kind]);
    q('INSERT INTO user_docs (id, user_id, kind, image_id) VALUES (?,?,?,?)',
      [new_id(), $userId, $kind, $imageId]);
}

function docs_of(string $userId): array {
    return rows('SELECT d.*, i.width, i.height FROM user_docs d
                   JOIN images i ON i.id = d.image_id
                  WHERE d.user_id = ? ORDER BY d.kind', [$userId]);
}
