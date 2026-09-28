<?php
declare(strict_types=1);

// ── الدليل العام ───────────────────────────────────────────────

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

function provider_of_user(string $userId): ?array {
    return row("SELECT p.*, z.name_ar AS zone_name FROM providers p
                 JOIN zones z ON z.id = p.zone_id WHERE p.user_id = ?", [$userId]);
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
          ORDER BY pl.is_open DESC, pl.sort_order, pl.name_ar"
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

function place_of_user(string $userId): ?array {
    return row("SELECT pl.*, z.name_ar AS zone_name FROM places pl
                 JOIN zones z ON z.id = pl.zone_id WHERE pl.user_id = ?", [$userId]);
}

function live_providers(array $all): array {
    return array_values(array_filter($all, 'is_live'));
}

function all_zones(): array {
    return rows('SELECT * FROM zones WHERE is_active = 1 ORDER BY sort_order, id');
}

// ── الطلبات ────────────────────────────────────────────────────

/** 🔴 الرقم المخفي مبيخرجش من قاعدة البيانات أصلًا — بس القناع. */
function open_requests(?int $zoneId = null): array {
    return rows(
        "SELECT r.id, r.kind, r.body, r.hide_phone, r.status, r.created_at, r.expires_at,
                z.name_ar AS zone_name,
                CASE WHEN r.hide_phone = 1 THEN NULL ELSE r.contact_phone END AS contact_phone,
                CASE WHEN r.hide_phone = 1
                     THEN CONCAT(LEFT(r.contact_phone,4), '••••', RIGHT(r.contact_phone,2))
                     ELSE NULL END AS masked_phone,
                (SELECT COUNT(*) FROM messages m WHERE m.request_id = r.id AND m.is_hidden = 0) AS msg_count
           FROM requests r LEFT JOIN zones z ON z.id = r.zone_id
          WHERE r.is_hidden = 0 AND r.expires_at > NOW() AND r.status = 'open'
            AND (? IS NULL OR r.zone_id = ?)
          ORDER BY r.created_at DESC LIMIT 50",
        [$zoneId, $zoneId]
    );
}

function my_requests(string $userId): array {
    return rows(
        "SELECT r.*, z.name_ar AS zone_name, p.display_name AS driver_name, p.phone AS driver_phone,
                (SELECT COUNT(*) FROM messages m WHERE m.request_id = r.id AND m.is_hidden = 0) AS msg_count
           FROM requests r
           LEFT JOIN zones z ON z.id = r.zone_id
           LEFT JOIN providers p ON p.id = r.taken_by
          WHERE r.user_id = ?
          ORDER BY r.created_at DESC LIMIT 60", [$userId]
    );
}

function requests_taken_by(string $providerId): array {
    return rows(
        "SELECT r.*, z.name_ar AS zone_name,
                CASE WHEN r.hide_phone = 1 AND r.status = 'open' THEN NULL ELSE r.contact_phone END AS contact_phone
           FROM requests r LEFT JOIN zones z ON z.id = r.zone_id
          WHERE r.taken_by = ? ORDER BY r.taken_at DESC LIMIT 40", [$providerId]
    );
}

function one_request(string $id): ?array {
    return row("SELECT r.*, z.name_ar AS zone_name FROM requests r
                 LEFT JOIN zones z ON z.id = r.zone_id WHERE r.id = ?", [$id]);
}

function request_by_token(string $token): ?array {
    return row("SELECT r.*, z.name_ar AS zone_name FROM requests r
                 LEFT JOIN zones z ON z.id = r.zone_id WHERE r.owner_token = ?", [$token]);
}

function messages_for(string $requestId): array {
    return rows(
        "SELECT m.id, m.from_owner, m.body, m.created_at, p.display_name AS sender_name, p.photo_id
           FROM messages m LEFT JOIN providers p ON p.id = m.provider_id
          WHERE m.request_id = ? AND m.is_hidden = 0
          ORDER BY m.created_at", [$requestId]
    );
}

// ── التقييمات والأسعار ─────────────────────────────────────────

function reviews_for(?string $providerId, ?string $placeId): array {
    return rows(
        "SELECT id, stars, comment, author_name, created_at FROM ratings
          WHERE is_hidden = 0 AND comment IS NOT NULL AND comment <> ''
            AND (? IS NULL OR provider_id = ?) AND (? IS NULL OR place_id = ?)
          ORDER BY created_at DESC LIMIT 30",
        [$providerId, $providerId, $placeId, $placeId]
    );
}

function ratings_of_owner(?string $providerId, ?string $placeId): array {
    return rows(
        "SELECT id, stars, comment, author_name, created_at FROM ratings
          WHERE is_hidden = 0 AND ((? IS NOT NULL AND provider_id = ?) OR (? IS NOT NULL AND place_id = ?))
          ORDER BY created_at DESC LIMIT 50",
        [$providerId, $providerId, $placeId, $placeId]
    );
}

function rating_summary(?string $providerId, ?string $placeId): array {
    $r = row(
        "SELECT ROUND(AVG(stars),1) AS avg_stars, COUNT(*) AS n FROM ratings
          WHERE is_hidden = 0 AND ((? IS NOT NULL AND provider_id = ?) OR (? IS NOT NULL AND place_id = ?))",
        [$providerId, $providerId, $placeId, $placeId]
    );
    return ['avg' => $r['avg_stars'] ?? null, 'n' => (int) ($r['n'] ?? 0)];
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

// ── الحسابات ───────────────────────────────────────────────────

function user_by_phone(string $phone): ?array {
    return row('SELECT * FROM users WHERE phone = ?', [$phone]);
}

function pending_users(): array {
    return rows(
        "SELECT u.*, z.name_ar AS zone_name,
                (SELECT COUNT(*) FROM user_docs d WHERE d.user_id = u.id) AS doc_count
           FROM users u LEFT JOIN zones z ON z.id = u.zone_id
          WHERE u.status = 'pending' ORDER BY u.created_at"
    );
}

function users_by_role(string $role, int $limit = 100): array {
    return rows(
        "SELECT u.*, z.name_ar AS zone_name FROM users u
           LEFT JOIN zones z ON z.id = u.zone_id
          WHERE u.role = ? ORDER BY u.created_at DESC LIMIT " . (int) $limit, [$role]
    );
}

function notifications_for(string $userId, int $limit = 20): array {
    return rows('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit,
                [$userId]);
}

function favorites_of(string $userId): array {
    return rows(
        "SELECT f.id, f.provider_id, f.place_id,
                p.display_name AS p_name, p.phone AS p_phone, p.photo_id AS p_photo,
                p.is_available, p.available_at, p.is_verified,
                pl.name_ar AS l_name, pl.phone AS l_phone, pl.photo_id AS l_photo, pl.category
           FROM favorites f
           LEFT JOIN providers p ON p.id = f.provider_id AND p.is_active = 1
           LEFT JOIN places   pl ON pl.id = f.place_id  AND pl.is_active = 1
          WHERE f.user_id = ? ORDER BY f.created_at DESC", [$userId]
    );
}

function is_favorite(string $userId, ?string $providerId, ?string $placeId): bool {
    return (bool) val(
        'SELECT COUNT(*) FROM favorites WHERE user_id = ?
          AND ((? IS NOT NULL AND provider_id = ?) OR (? IS NOT NULL AND place_id = ?))',
        [$userId, $providerId, $providerId, $placeId, $placeId]
    );
}

// ── أرقام الداش بورد ───────────────────────────────────────────

function admin_stats(): array {
    return [
        'drivers'  => (int) val('SELECT COUNT(*) FROM providers WHERE is_active = 1'),
        'live'     => (int) val("SELECT COUNT(*) FROM providers WHERE is_active = 1 AND is_available = 1
                                  AND available_at > DATE_SUB(NOW(), INTERVAL " . AVAILABILITY_HOURS . " HOUR)"),
        'places'   => (int) val('SELECT COUNT(*) FROM places WHERE is_active = 1'),
        'pending'  => (int) val("SELECT COUNT(*) FROM users WHERE status = 'pending'"),
        'reports'  => (int) val('SELECT COUNT(*) FROM reports WHERE handled_at IS NULL'),
        'requests' => (int) val("SELECT COUNT(*) FROM requests WHERE is_hidden = 0 AND expires_at > NOW() AND status = 'open'"),
        'users'    => (int) val('SELECT COUNT(*) FROM users'),
        'customers'=> (int) val("SELECT COUNT(*) FROM users WHERE role = 'customer'"),
    ];
}

function driver_stats(string $providerId): array {
    return [
        'taken' => (int) val('SELECT COUNT(*) FROM requests WHERE taken_by = ?', [$providerId]),
        'done'  => (int) val("SELECT COUNT(*) FROM requests WHERE taken_by = ? AND status = 'done'", [$providerId]),
        'open'  => (int) val("SELECT COUNT(*) FROM requests WHERE is_hidden = 0 AND status = 'open' AND expires_at > NOW()"),
        'stars' => (float) (val('SELECT ROUND(AVG(stars),1) FROM ratings WHERE provider_id = ? AND is_hidden = 0',
                                [$providerId]) ?? 0),
    ];
}
