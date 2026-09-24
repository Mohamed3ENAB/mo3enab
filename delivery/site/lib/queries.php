<?php
declare(strict_types=1);

/** السواقين النشطين مع القرية والتقييم، مرتّبين بالمتاح الأول. */
function all_providers(): array {
    return rows(
        "SELECT p.*, z.name_ar AS zone_name,
                (SELECT ROUND(AVG(r.stars),1) FROM ratings r
                  WHERE r.provider_id = p.id AND r.is_hidden = 0) AS rating_avg,
                (SELECT COUNT(*) FROM ratings r
                  WHERE r.provider_id = p.id AND r.is_hidden = 0) AS rating_count
           FROM providers p JOIN zones z ON z.id = p.zone_id
          WHERE p.is_active = 1
          ORDER BY p.is_verified DESC, p.available_at DESC, p.display_name"
    );
}

function one_provider(string $id): ?array {
    return row(
        "SELECT p.*, z.name_ar AS zone_name,
                (SELECT ROUND(AVG(r.stars),1) FROM ratings r
                  WHERE r.provider_id = p.id AND r.is_hidden = 0) AS rating_avg,
                (SELECT COUNT(*) FROM ratings r
                  WHERE r.provider_id = p.id AND r.is_hidden = 0) AS rating_count
           FROM providers p JOIN zones z ON z.id = p.zone_id
          WHERE p.id = ? AND p.is_active = 1", [$id]
    );
}

function provider_by_token(string $t): ?array {
    return row("SELECT p.*, z.name_ar AS zone_name FROM providers p
                 JOIN zones z ON z.id = p.zone_id
                WHERE p.token = ? AND p.is_active = 1", [$t]);
}

function all_places(): array {
    return rows(
        "SELECT pl.*, z.name_ar AS zone_name,
                (SELECT ROUND(AVG(r.stars),1) FROM ratings r
                  WHERE r.place_id = pl.id AND r.is_hidden = 0) AS rating_avg,
                (SELECT COUNT(*) FROM ratings r
                  WHERE r.place_id = pl.id AND r.is_hidden = 0) AS rating_count
           FROM places pl JOIN zones z ON z.id = pl.zone_id
          WHERE pl.is_active = 1
          ORDER BY pl.sort_order, pl.name_ar"
    );
}

function one_place(string $id): ?array {
    return row(
        "SELECT pl.*, z.name_ar AS zone_name,
                (SELECT ROUND(AVG(r.stars),1) FROM ratings r
                  WHERE r.place_id = pl.id AND r.is_hidden = 0) AS rating_avg,
                (SELECT COUNT(*) FROM ratings r
                  WHERE r.place_id = pl.id AND r.is_hidden = 0) AS rating_count
           FROM places pl JOIN zones z ON z.id = pl.zone_id
          WHERE pl.id = ? AND pl.is_active = 1", [$id]
    );
}

function live_providers(array $all): array {
    return array_values(array_filter($all, 'is_live'));
}

function all_zones(): array {
    return rows('SELECT * FROM zones WHERE is_active = 1 ORDER BY sort_order, id');
}

/** 🔴 الرقم المخفي مبيخرجش من قاعدة البيانات أصلًا — بس القناع. */
function open_requests(): array {
    return rows(
        "SELECT r.id, r.kind, r.body, r.hide_phone, r.created_at, r.expires_at,
                z.name_ar AS zone_name,
                CASE WHEN r.hide_phone = 1 THEN NULL ELSE r.contact_phone END AS contact_phone,
                CASE WHEN r.hide_phone = 1
                     THEN CONCAT(LEFT(r.contact_phone,4), '••••', RIGHT(r.contact_phone,2))
                     ELSE NULL END AS masked_phone,
                (SELECT COUNT(*) FROM messages m WHERE m.request_id = r.id AND m.is_hidden = 0) AS msg_count
           FROM requests r LEFT JOIN zones z ON z.id = r.zone_id
          WHERE r.is_hidden = 0 AND r.expires_at > NOW()
          ORDER BY r.created_at DESC LIMIT 50"
    );
}

function reviews_for(?string $providerId, ?string $placeId): array {
    return rows(
        "SELECT id, stars, comment, author_name, created_at FROM ratings
          WHERE is_hidden = 0 AND comment IS NOT NULL AND comment <> ''
            AND (? IS NULL OR provider_id = ?) AND (? IS NULL OR place_id = ?)
          ORDER BY created_at DESC LIMIT 30",
        [$providerId, $providerId, $placeId, $placeId]
    );
}

function vehicle_rates(): array {
    return rows('SELECT * FROM vehicle_rates WHERE is_active = 1 ORDER BY sort_order, starts_from');
}

function price_guide(): array {
    return rows(
        "SELECT g.*, f.name_ar AS from_zone, t.name_ar AS to_zone
           FROM price_guide g
           LEFT JOIN zones f ON f.id = g.from_zone_id
           LEFT JOIN zones t ON t.id = g.to_zone_id
          WHERE g.is_active = 1 ORDER BY g.id"
    );
}

function messages_for(string $requestId): array {
    return rows(
        "SELECT m.id, m.from_owner, m.body, m.created_at, p.display_name AS sender_name
           FROM messages m LEFT JOIN providers p ON p.id = m.provider_id
          WHERE m.request_id = ? AND m.is_hidden = 0
          ORDER BY m.created_at", [$requestId]
    );
}

function admin_stats(): array {
    return [
        'drivers'  => (int) val('SELECT COUNT(*) FROM providers WHERE is_active = 1'),
        'live'     => (int) val("SELECT COUNT(*) FROM providers WHERE is_active = 1 AND is_available = 1
                                  AND available_at > DATE_SUB(NOW(), INTERVAL " . AVAILABILITY_HOURS . " HOUR)"),
        'places'   => (int) val('SELECT COUNT(*) FROM places WHERE is_active = 1'),
        'pending'  => (int) val("SELECT COUNT(*) FROM applications WHERE status = 'pending'"),
        'reports'  => (int) val('SELECT COUNT(*) FROM reports WHERE handled_at IS NULL'),
        'requests' => (int) val('SELECT COUNT(*) FROM requests WHERE is_hidden = 0 AND expires_at > NOW()'),
    ];
}
