<?php
declare(strict_types=1);

/** كارت سائق في الدليل. */
function provider_card(array $p): string {
    $live  = is_live($p);
    $servs = services_of($p);
    $chips = '';
    foreach ($servs as $s) {
        if (!isset(SERVICES[$s])) continue;
        $chips .= '<span class="chip ' . bubble_class($s) . '">' . icon($s, 14) . ' ' . e(SERVICES[$s]) . '</span>';
    }
    $rate = '';
    if (!empty($p['rating_count'])) {
        $rate = '<span class="rate">' . icon('star', 13) . ' ' . e((string) $p['rating_avg'])
              . ' <i>(' . (int) $p['rating_count'] . ')</i></span>';
    }
    return '<article class="card prov tilt' . ($live ? ' is-live' : '') . '" data-name="' . e($p['display_name'])
         . '" data-zone="' . (int) $p['zone_id'] . '" data-services="' . e((string) $p['services'])
         . '" data-live="' . ($live ? '1' : '0') . '">'
      . '<a class="card-hit" href="' . url('p/' . e($p['id'])) . '" aria-label="' . e($p['display_name']) . '"></a>'
      . '<div class="card-top">'
        . avatar($p['display_name'], $p['photo_id'], (bool) $p['is_verified'])
        . '<div class="card-id"><h3>' . e($p['display_name']) . $rate . '</h3>'
        . '<p class="sub">' . icon('pin', 13) . ' ' . e($p['zone_name'])
        . ($p['vehicle_note'] ? ' · ' . e($p['vehicle_note']) : '') . '</p></div>'
        . state_pill($live, $p['available_at'])
      . '</div>'
      . ($chips ? '<div class="chips">' . $chips . '</div>' : '')
      . '<div class="card-acts">'
        . '<a class="btn call" href="' . e(tel_link($p['phone'])) . '" data-sfx="call">' . icon('phone', 17) . ' اتصال</a>'
        . ($p['whatsapp'] ? '<a class="btn wa" href="' . e(wa_link($p['whatsapp'], 'السلام عليكم، لقيت رقمك في تطبيق «في السكة».'))
            . '" target="_blank" rel="noopener" data-sfx="tap">' . icon('wa', 17) . ' واتساب</a>' : '')
        . '<a class="btn ghost" href="' . url('p/' . e($p['id'])) . '">التفاصيل</a>'
      . '</div>'
    . '</article>';
}

/** كارت محل. */
function place_card(array $pl): string {
    $cat  = CATEGORIES[$pl['category']] ?? 'محل';
    $open = (int) ($pl['is_open'] ?? 1) === 1;
    $rate = '';
    if (!empty($pl['rating_count'])) {
        $rate = '<span class="rate">' . icon('star', 13) . ' ' . e((string) $pl['rating_avg'])
              . ' <i>(' . (int) $pl['rating_count'] . ')</i></span>';
    }
    return '<article class="card place tilt" data-name="' . e($pl['name_ar'])
         . '" data-zone="' . (int) $pl['zone_id'] . '" data-cat="' . e($pl['category']) . '">'
      . '<a class="card-hit" href="' . url('m/' . e($pl['id'])) . '" aria-label="' . e($pl['name_ar']) . '"></a>'
      . '<div class="card-top">'
        . avatar($pl['name_ar'], $pl['photo_id'])
        . '<div class="card-id"><h3>' . e($pl['name_ar']) . $rate . '</h3>'
        . '<p class="sub">' . icon($pl['category'], 13) . ' ' . e($cat) . ' · ' . e($pl['zone_name']) . '</p></div>'
        . '<span class="state ' . ($open ? 'on' : 'off') . '"><span class="beacon"><i></i><i></i></span>'
        . ($open ? 'فاتح' : 'قافل') . '</span>'
      . '</div>'
      . ($pl['hours_note'] ? '<p class="note">' . icon('clock', 13) . ' ' . e($pl['hours_note']) . '</p>' : '')
      . '<div class="card-acts">'
        . ($pl['phone'] ? '<a class="btn call" href="' . e(tel_link($pl['phone'])) . '" data-sfx="call">' . icon('phone', 17) . ' اتصال</a>' : '')
        . ($pl['whatsapp'] ? '<a class="btn wa" href="' . e(wa_link($pl['whatsapp'])) . '" target="_blank" rel="noopener">' . icon('wa', 17) . ' واتساب</a>' : '')
        . '<a class="btn ghost" href="' . url('m/' . e($pl['id'])) . '">التفاصيل</a>'
      . '</div>'
    . '</article>';
}

/** كارت طلب في لوحة الطلبات. */
function request_card(array $r, bool $asDriver = false, ?array $provider = null, string $takeAction = ''): string {
    $kind = $r['kind'] && isset(SERVICES[$r['kind']]) ? SERVICES[$r['kind']] : 'طلب';
    $phone = $r['contact_phone'] ?? null;
    $out = '<article class="card req">'
      . '<div class="req-top">'
        . '<span class="chip ' . bubble_class((string) $r['kind']) . '">' . icon((string) $r['kind'], 14) . ' ' . e($kind) . '</span>'
        . ($r['zone_name'] ? '<span class="muted">' . icon('pin', 12) . ' ' . e($r['zone_name']) . '</span>' : '')
        . '<span class="muted time">' . e(since_ar($r['created_at'])) . '</span>'
      . '</div>'
      . '<p class="req-body">' . nl2br(e($r['body'])) . '</p>';

    if ($phone) {
        $out .= tel_box($phone, 'كلّم صاحب الطلب');
    } else {
        $out .= '<div class="masked">' . icon('lock', 14) . ' الرقم متخفي — '
              . e((string) ($r['masked_phone'] ?? '')) . ' · كلّمه من جوه التطبيق</div>';
    }

    if ($asDriver && $provider) {
        $out .= '<div class="card-acts">'
          . '<a class="btn call" href="' . url('t/p/' . e($provider['token']) . '/' . e($r['id'])) . '" data-sfx="tap">'
          . icon('chat', 17) . ' رد على الطلب</a>'
          . ($takeAction
            ? '<form method="post" action="' . e($takeAction) . '" class="inline">'
              . csrf_field() . '<input type="hidden" name="do" value="take"><input type="hidden" name="rid" value="' . e($r['id']) . '">'
              . '<button class="btn ghost" data-sfx="ok">' . icon('check', 16) . ' استلم الطلب</button></form>'
            : '')
          . '</div>';
    } else {
        $out .= '<div class="card-acts"><span class="muted small">' . icon('chat', 13) . ' '
              . (int) ($r['msg_count'] ?? 0) . ' رد</span></div>';
    }
    return $out . '</article>';
}

/** قائمة الريفيوز. */
function reviews_block(array $list): string {
    if (!$list) return '<p class="muted center pad">لسه مفيش ريفيوز. كن أول واحد يكتب.</p>';
    $out = '<ul class="reviews">';
    foreach ($list as $r) {
        $out .= '<li><div class="rv-top">' . stars_row((int) $r['stars'])
              . '<span class="muted">' . e($r['author_name'] ?: 'مجهول') . ' · ' . e(since_ar($r['created_at'])) . '</span></div>'
              . ($r['comment'] ? '<p>' . nl2br(e($r['comment'])) . '</p>' : '') . '</li>';
    }
    return $out . '</ul>';
}

/** نموذج تقييم. */
function rating_form(string $action, ?string $providerId, ?string $placeId): string {
    $stars = '';
    for ($i = 5; $i >= 1; $i--) {
        $stars .= '<input type="radio" name="stars" id="s' . $i . '" value="' . $i . '"' . ($i === 5 ? ' checked' : '') . '>'
                . '<label for="s' . $i . '" title="' . $i . '">' . icon('star', 26) . '</label>';
    }
    return '<form class="rate-form card soft" method="post" action="' . e($action) . '">'
      . csrf_field()
      . '<input type="hidden" name="do" value="rate">'
      . ($providerId ? '<input type="hidden" name="provider_id" value="' . e($providerId) . '">' : '')
      . ($placeId ? '<input type="hidden" name="place_id" value="' . e($placeId) . '">' : '')
      . '<h3>' . icon('star', 17) . ' قيّم تجربتك</h3>'
      . '<div class="starpick">' . $stars . '</div>'
      . '<input type="text" name="author_name" maxlength="40" placeholder="اسمك (اختياري)">'
      . '<textarea name="comment" maxlength="320" rows="3" placeholder="اكتب رأيك بهدوء — الكلام بيساعد ناس تانية."></textarea>'
      . '<button class="btn call wide" data-sfx="ok">' . icon('send', 17) . ' ابعت التقييم</button>'
    . '</form>';
}

/** زرار المفضلة. */
function fav_button(?string $providerId, ?string $placeId): string {
    if (!is_logged_in()) return '';
    $on = is_favorite((string) uid(), $providerId, $placeId);
    return '<form method="post" action="' . url('fav') . '" class="inline">'
      . csrf_field()
      . ($providerId ? '<input type="hidden" name="provider_id" value="' . e($providerId) . '">' : '')
      . ($placeId ? '<input type="hidden" name="place_id" value="' . e($placeId) . '">' : '')
      . '<input type="hidden" name="back" value="' . e($_SERVER['REQUEST_URI'] ?? '') . '">'
      . '<button class="iconbtn fav' . ($on ? ' on' : '') . '" aria-label="المفضلة" data-sfx="tap">'
      . icon('heart', 19) . '</button></form>';
}

/** حالة فاضية بشكل محترم. */
function empty_state(string $title, string $text = '', string $ic = 'search', string $btn = '', string $href = ''): string {
    return '<div class="empty"><span class="empty-ic">' . icon($ic, 30) . '</span>'
      . '<h3>' . e($title) . '</h3>'
      . ($text ? '<p class="lede">' . e($text) . '</p>' : '')
      . ($btn ? '<a class="btn call" href="' . e($href) . '">' . e($btn) . '</a>' : '')
      . '</div>';
}

/** قائمة الإشعارات في الداش بورد. */
function notifications_block(array $list): string {
    if (!$list) return empty_state('مفيش إشعارات', 'أول ما يحصل جديد هتلاقيه هنا.', 'bell');
    $out = '<ul class="notifs">';
    foreach ($list as $n) {
        $inner = '<span class="nf-ic">' . icon($n['icon'] ?: 'info', 17) . '</span>'
               . '<div><b>' . e($n['title']) . '</b>'
               . ($n['body'] ? '<p>' . e($n['body']) . '</p>' : '')
               . '<i class="muted">' . e(since_ar($n['created_at'])) . '</i></div>';
        $out .= '<li class="' . ((int) $n['is_read'] ? '' : 'unread') . '">'
              . ($n['href'] ? '<a href="' . e($n['href']) . '">' . $inner . '</a>' : '<div class="nf">' . $inner . '</div>')
              . '</li>';
    }
    return $out . '</ul>';
}

/** تبويبات الداش بورد. */
function dash_tabs(array $tabs, string $current): string {
    $out = '<nav class="tabs" role="tablist">';
    foreach ($tabs as $key => [$label, $ic]) {
        $out .= '<a class="tab' . ($key === $current ? ' on' : '') . '" href="#' . e($key) . '" data-tab="' . e($key) . '">'
              . icon($ic, 16) . ' ' . e($label) . '</a>';
    }
    return $out . '</nav>';
}
