<?php
declare(strict_types=1);

function hero(string $brandIcon, string $brandText, string $h1, string $sub, bool $zonepick = true): string {
    $zone = $zonepick
        ? '<span class="zonepick">' . icon('pin', 15) . ' ' . e((string) cfg('main_zone')) . '</span>'
        : '';
    return '<header class="hero"><div class="hero-top">'
        . '<div class="hero-brand"><span class="hero-logo">' . icon($brandIcon, 20) . '</span> ' . e($brandText) . '</div>'
        . $zone . '</div>'
        . '<h1>' . e($h1) . '</h1><p>' . e($sub) . '</p></header>';
}

function install_bar(): string {
    return '<div class="installbar" id="installbar" hidden>'
        . '<span class="bubble b-delivery" style="width:40px;height:40px;border-radius:13px;display:grid;place-items:center;color:#fff;flex:0 0 auto">'
        . icon('logo', 20) . '</span>'
        . '<span class="t">ثبّت التطبيق على شاشتك<span>يفتح على طول من غير متصفح</span></span>'
        . '<button type="button" data-install>تثبيت</button>'
        . '<button type="button" class="x" aria-label="إخفاء">' . icon('close', 16) . '</button></div>';
}

function search_box(string $placeholder): string {
    return '<div class="searchwrap"><div class="search">' . icon('search', 21)
        . '<input id="q" type="search" placeholder="' . e($placeholder) . '" aria-label="بحث"></div></div>';
}

function chips_zones(array $zones): string {
    $out = '<section class="sec"><div class="zones">'
        . '<button class="zchip" data-set="zone:" aria-pressed="true">كل القرى</button>';
    foreach ($zones as $z) {
        $out .= '<button class="zchip" data-set="zone:' . e($z['name_ar']) . '" aria-pressed="false">'
              . e($z['name_ar']) . '</button>';
    }
    return $out . '</div></section>';
}

function chips_services(array $keys, array $labels, string $title): string {
    $out = '<section class="sec"><div class="sec-head"><h2>' . e($title) . '</h2></div><div class="services">'
        . '<button class="svc" data-set="kind:" aria-pressed="true">'
        . '<span class="bubble b-all">' . icon('all', 22) . '</span><span>الكل</span></button>';
    foreach ($keys as $k) {
        $out .= '<button class="svc" data-set="kind:' . e($k) . '" aria-pressed="false">'
              . '<span class="bubble ' . bubble_class($k) . '">' . icon($k, 22) . '</span>'
              . '<span>' . e($labels[$k]) . '</span></button>';
    }
    return $out . '</div></section>';
}

/** كارت السائق في الدليل. */
function provider_card(array $p): string {
    $svc  = services_of($p);
    $live = is_live($p);
    $first = explode(' ', trim($p['display_name']))[0];
    $hay  = $p['display_name'] . ' ' . ($p['vehicle_note'] ?? '');
    $rate = ((int) $p['rating_count'] > 0) ? $p['rating_avg'] : null;

    $out = '<article class="row' . ($live ? ' live' : '') . '"'
        . ' data-hay="' . e($hay) . '"'
        . ' data-zone="' . e($p['zone_name']) . '"'
        . ' data-kinds="' . e(implode(',', $svc)) . '">'
        . avatar($p['display_name'], $p['photo_id'], (bool) $p['is_verified'])
        . '<div><div class="head"><h3>' . e($p['display_name']) . '</h3>'
        . ($rate ? '<span class="rate">' . icon('star', 13) . ' ' . e($rate) . '</span>' : '')
        . '</div>'
        . '<p class="meta">' . icon($svc[0] ?? 'delivery', 16) . ' '
        . e($p['vehicle_note'] ?: implode('، ', array_map(fn($s) => SERVICES[$s] ?? $s, $svc)))
        . ' ' . icon('pin', 13) . ' ' . e($p['zone_name']) . '</p>';

    if ($p['vehicle_note']) {
        $out .= '<p class="subnote">' . e(implode('، ', array_map(fn($s) => SERVICES[$s] ?? $s, $svc))) . '</p>';
    }
    if ($p['note']) $out .= '<p class="subnote">' . e($p['note']) . '</p>';

    $out .= state_pill($live, $p['available_at'])
        . '<div class="actions">'
        . '<a class="btn call" href="' . e(tel_link($p['phone'])) . '">' . icon('phone', 20) . ' اتصال</a>';
    if ($p['whatsapp']) {
        $out .= '<a class="btn wa" target="_blank" rel="noreferrer" aria-label="واتساب ' . e($first) . '" href="'
              . e(wa_link($p['whatsapp'], 'السلام عليكم، لقيت رقمك في «في السكة»')) . '">' . icon('wa', 22) . '</a>';
    }
    $out .= '<a class="btn quiet" href="' . url('p/' . e($p['id'])) . '" aria-label="تفاصيل وتقييم">'
          . icon('chevron', 16) . '</a></div></div></article>';
    return $out;
}

/** كارت المحل + ورقة الطلب بخطوتين. */
function place_card(array $pl, array $liveDrivers): string {
    $cat  = $pl['category'];
    $rate = ((int) $pl['rating_count'] > 0) ? $pl['rating_avg'] : null;
    $sid  = 'sheet-' . $pl['id'];
    $hay  = $pl['name_ar'] . ' ' . ($pl['address_note'] ?? '');

    $img = $pl['photo_id']
        ? '<span class="avatar photo"><img src="' . url('img.php?id=' . e($pl['photo_id'])) . '" alt="" loading="lazy"></span>'
        : '<span class="avatar ' . bubble_class($cat) . '">' . icon($cat, 24) . '</span>';

    $out = '<article class="row" data-hay="' . e($hay) . '" data-zone="' . e($pl['zone_name'])
        . '" data-kinds="' . e($cat) . '">' . $img
        . '<div><div class="head"><h3>' . e($pl['name_ar']) . '</h3>'
        . ($rate ? '<span class="rate">' . icon('star', 13) . ' ' . e($rate) . '</span>' : '')
        . '</div>'
        . '<p class="meta">' . e(CATEGORIES[$cat] ?? $cat) . ' ' . icon('pin', 13) . ' ' . e($pl['zone_name']) . '</p>';
    if ($pl['address_note']) $out .= '<p class="subnote">' . e($pl['address_note']) . '</p>';
    if ($pl['hours_note'])   $out .= '<p class="subnote">' . icon('clock', 13) . ' ' . e($pl['hours_note']) . '</p>';
    if ($pl['note'])         $out .= '<p class="subnote">' . e($pl['note']) . '</p>';

    $out .= '<div class="actions"><button class="btn call" type="button" data-sheet="' . e($sid) . '">اطلب من هنا</button>';
    if ($pl['whatsapp']) {
        $out .= '<a class="btn wa" target="_blank" rel="noreferrer" aria-label="واتساب ' . e($pl['name_ar']) . '" href="'
              . e(wa_link($pl['whatsapp'], 'السلام عليكم، عايز أطلب من ' . $pl['name_ar'])) . '">' . icon('wa', 22) . '</a>';
    }
    $out .= '<a class="btn quiet" href="' . url('m/' . e($pl['id'])) . '" aria-label="تفاصيل وتقييم">'
          . icon('star', 16) . '</a></div></div></article>';

    // ورقة الطلب
    $msg = 'السلام عليكم، ممكن تستلملي طلب من ' . $pl['name_ar']
         . ($pl['address_note'] ? ' (' . $pl['address_note'] . ')' : '') . ' في ' . $pl['zone_name'] . '؟';

    $out .= '<div class="scrim" id="' . e($sid) . '" hidden><div class="sheet" role="dialog" aria-modal="true">'
        . '<div class="grab"></div>'
        . '<h2 style="margin:0 0 3px;font-size:19px;font-weight:800">' . e($pl['name_ar']) . '</h2>'
        . '<p style="margin:0 0 20px;color:var(--ink-500);font-size:14px">'
        . e(CATEGORIES[$cat] ?? $cat) . ' · ' . e($pl['zone_name']) . '</p>'
        . '<div class="step"><span class="n">1</span><div class="c"><b>كلّم المحل واطلب</b>'
        . '<p>قوله على طلبك، واسأله هيبقى جاهز إمتى وبكام.</p>'
        . ($pl['phone'] ? tel_box($pl['phone']) : '');
    if ($pl['whatsapp']) {
        $out .= '<a class="btn wa wide" style="width:100%;margin-top:10px" target="_blank" rel="noreferrer" href="'
              . e(wa_link($pl['whatsapp'], 'السلام عليكم، عايز أطلب من ' . $pl['name_ar'])) . '">'
              . icon('wa', 20) . ' افتح واتساب</a>';
    }
    $out .= '</div></div><div class="step"><span class="n">2</span><div class="c"><b>كلّم سائق يستلم منهم</b>';
    if (!$liveDrivers) {
        $out .= '<p>مفيش سائق متاح دلوقتي. اكتب طلبك في لوحة الطلبات.</p>';
    } else {
        $out .= '<p>الرسالة هتتكتب لوحدها — بس ابعتها.</p>';
        foreach (array_slice($liveDrivers, 0, 3) as $d) {
            $ds = services_of($d);
            $out .= '<div class="mini">' . icon($ds[0] ?? 'delivery', 18)
                  . '<span class="t"><b>' . e($d['display_name']) . '</b><span>' . e((string) $d['vehicle_note']) . '</span></span>'
                  . '<a class="btn quiet" href="' . e(tel_link($d['phone'])) . '" aria-label="اتصل بـ ' . e($d['display_name']) . '">'
                  . icon('phone', 17) . '</a>';
            if ($d['whatsapp']) {
                $out .= '<a class="btn wa" style="width:46px;min-height:44px" target="_blank" rel="noreferrer" href="'
                      . e(wa_link($d['whatsapp'], $msg)) . '" aria-label="واتساب ' . e($d['display_name']) . '">'
                      . icon('wa', 19) . '</a>';
            }
            $out .= '</div>';
        }
    }
    $out .= '</div></div><button class="btn quiet wide" type="button" data-close>إغلاق</button></div></div>';
    return $out;
}

function photo_field(string $label, string $hint): string {
    return '<div class="photofield"><span class="prev">' . icon('camera', 24) . '</span>'
        . '<span class="t"><b>' . e($label) . '</b><span>' . e($hint) . '</span></span>'
        . '<button type="button" class="pick">اختار صورة</button>'
        . '<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></div>';
}
