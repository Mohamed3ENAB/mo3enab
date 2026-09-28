<?php
declare(strict_types=1);

/**
 * الحاجات المشتركة بين كل أنواع التسجيل: الاسم والرقم وكلمة السر والقرية.
 * بترجّع ['errors'=>[..], 'data'=>[..]] — الأخطاء كلها مرة واحدة،
 * مش واحد واحد، عشان المستخدم ميفضلش يبعت ويرجع.
 */
function read_signup_basics(): array {
    $d = [
        'name'    => post('name', 80),
        'phone'   => preg_replace('/\D/', '', post('phone', 20)),
        'pass'    => (string) ($_POST['password'] ?? ''),
        'pass2'   => (string) ($_POST['password2'] ?? ''),
        'zone_id' => (int) post('zone_id', 6),
    ];
    $err = [];

    if (mb_strlen($d['name']) < 3)        $err[] = 'اكتب اسمك كامل (٣ حروف على الأقل).';
    if (!valid_phone($d['phone']))        $err[] = 'الرقم لازم يبدأ بصفر ويكون ١١ رقم.';
    elseif (user_by_phone($d['phone']))   $err[] = 'الرقم دا مسجّل عندنا قبل كده. جرّب تسجّل دخول.';
    if ($p = password_problem($d['pass'])) $err[] = $p;
    elseif ($d['pass'] !== $d['pass2'])   $err[] = 'كلمتين السر مش زي بعض.';
    if ($d['zone_id'] <= 0)               $err[] = 'اختار المنطقة بتاعتك.';

    return ['errors' => $err, 'data' => $d];
}

/** بتعمل الحساب وبترجّع الـ id. */
function create_account(string $role, array $d, ?string $avatarId, string $status): string {
    $id = new_id();
    q('INSERT INTO users (id, role, name, phone, pass_hash, status, zone_id, avatar_id)
       VALUES (?,?,?,?,?,?,?,?)',
      [$id, $role, $d['name'], $d['phone'], hash_password($d['pass']), $status,
       $d['zone_id'] ?: null, $avatarId]);
    return $id;
}

/**
 * بتقرا صورة الحساب + المستندات المطلوبة.
 * بترجّع ['errors'=>[], 'avatar'=>id|null, 'docs'=>['national_id'=>id,..]]
 * ولو حصل أي خطأ بتمسح الصور اللي اتخزنت قبله — مفيش صور يتيمة في القاعدة.
 */
function read_signup_files(array $required = ['national_id']): array {
    $err = [];
    $saved = [];
    $docs  = [];
    $avatarId = null;

    $a = store_avatar('photo');
    if (isset($a['error']))      $err[] = 'صورة الحساب: ' . $a['error'];
    elseif (empty($a['id']))     $err[] = 'لازم ترفع صورة للحساب.';
    else { $avatarId = $a['id']; $saved[] = $a['id']; }

    foreach ($required as $kind) {
        $r = store_doc($kind);
        $label = DOC_KINDS[$kind] ?? $kind;
        if (isset($r['error']))  { $err[] = $label . ': ' . $r['error']; continue; }
        if (empty($r['id']))     { $err[] = 'لازم ترفع صورة ' . $label . '.'; continue; }
        $docs[$kind] = $r['id'];
        $saved[] = $r['id'];
    }

    if ($err) {
        foreach ($saved as $imgId) delete_image($imgId);
        return ['errors' => $err, 'avatar' => null, 'docs' => []];
    }
    return ['errors' => [], 'avatar' => $avatarId, 'docs' => $docs];
}

/** بتحوّل قايمة أخطاء لكتلة HTML واحدة. */
function errors_block(array $errors): string {
    if (!$errors) return '';
    $out = '<div class="msg bad list">' . icon('alert', 16) . '<ul>';
    foreach ($errors as $e) $out .= '<li>' . e($e) . '</li>';
    return $out . '</ul></div>';
}

/** قايمة المناطق كـ select. */
function zones_select(string $name = 'zone_id', int $selected = 0, string $firstLabel = 'اختار المنطقة', bool $required = true): string {
    $out = '<select name="' . e($name) . '"' . ($required ? ' required' : '') . '>'
         . '<option value="">' . e($firstLabel) . '</option>';
    foreach (all_zones() as $z) {
        $sel = ((int) $z['id'] === $selected) ? ' selected' : '';
        $out .= '<option value="' . (int) $z['id'] . '"' . $sel . '>' . e($z['name_ar']) . '</option>';
    }
    return $out . '</select>';
}

/** حقل كلمة سر بزرار إظهار. */
function password_field(string $name, string $ph): string {
    return '<span class="pw"><input type="password" name="' . e($name) . '" placeholder="' . e($ph)
         . '" required minlength="8" autocomplete="new-password">'
         . '<button type="button" class="pw-eye" data-pw-toggle aria-label="إظهار كلمة السر">'
         . icon('eye', 18) . '</button></span>';
}
