import 'server-only';
import { q, q1 } from './db';
import type {
  Provider, Zone, OpenRequest, PriceRow, ServiceKind, Place, VehicleRate,
} from './types';

export async function getZones(): Promise<Zone[]> {
  return q<Zone>(
    `select id, name_ar from service_zones where is_active order by sort_order, id`
  );
}

export async function getProviders(opts: {
  zoneId?: number;
  kind?: ServiceKind;
}): Promise<Provider[]> {
  return q<Provider>(
    `select p.id, p.display_name, p.phone, p.whatsapp, p.zone_id,
            p.services::text[] as services, p.vehicle_note, p.note,
            p.is_verified, p.live, p.availability_updated_at,
            p.rating_avg, p.rating_count, p.photo_id,
            z.name_ar as zone_name
       from providers_public p
       join service_zones z on z.id = p.zone_id
      where ($1::int is null or p.zone_id = $1)
        and ($2::text is null or $2::service_kind = any(p.services))
      order by p.live desc,
               p.is_verified desc,
               p.availability_updated_at desc nulls last,
               p.display_name`,
    [opts.zoneId ?? null, opts.kind ?? null]
  );
}

export async function getProvider(id: string): Promise<Provider | null> {
  return q1<Provider>(
    `select p.id, p.display_name, p.phone, p.whatsapp, p.zone_id,
            p.services::text[] as services, p.vehicle_note, p.note,
            p.is_verified, p.live, p.availability_updated_at,
            p.rating_avg, p.rating_count, p.photo_id,
            z.name_ar as zone_name
       from providers_public p
       join service_zones z on z.id = p.zone_id
      where p.id = $1`,
    [id]
  );
}

/** بيانات السائق من لينكه السري. اللينك نفسه مبيتخزنش في المتصفح. */
export async function getProviderByToken(token: string) {
  return q1<{
    id: string;
    display_name: string;
    is_available: boolean;
    availability_updated_at: string | null;
    is_verified: boolean;
    zone_name: string;
    services: ServiceKind[];
  }>(
    `select p.id, p.display_name, p.is_available, p.availability_updated_at,
            p.is_verified, z.name_ar as zone_name, p.services::text[] as services
       from providers p
       join provider_tokens t on t.provider_id = p.id
       join service_zones z on z.id = p.zone_id
      where t.token = $1 and p.is_active`,
    [token]
  );
}

export async function getOpenRequests(): Promise<OpenRequest[]> {
  return q<OpenRequest>(
    `select r.id, z.name_ar as zone_name, r.kind, r.body,
            r.contact_phone, r.created_at, r.expires_at
       from requests r
       left join service_zones z on z.id = r.zone_id
      where not r.is_hidden and r.expires_at > now()
      order by r.created_at desc
      limit 50`
  );
}

export async function getPrices(): Promise<PriceRow[]> {
  return q<PriceRow>(
    `select f.name_ar as from_zone, t.name_ar as to_zone, g.kind,
            g.typical_min, g.typical_max, g.note_ar
       from price_guide g
       left join service_zones f on f.id = g.from_zone_id
       left join service_zones t on t.id = g.to_zone_id
      where g.is_active
      order by g.id`
  );
}

export async function getPlaces(): Promise<Place[]> {
  return q<Place>(
    `select p.id, p.name_ar, p.category::text as category, p.zone_id,
            p.phone, p.whatsapp, p.address_note, p.hours_note, p.note, p.photo_id,
            (select round(avg(r.stars)::numeric,1) from ratings r
              where r.place_id = p.id and not r.is_hidden) as rating_avg,
            (select count(*) from ratings r
              where r.place_id = p.id and not r.is_hidden) as rating_count,
            z.name_ar as zone_name
       from places p
       join service_zones z on z.id = p.zone_id
      where p.is_active
      order by p.sort_order, p.name_ar`
  );
}

export async function getVehicleRates(): Promise<VehicleRate[]> {
  return q<VehicleRate>(
    `select kind::text as kind, starts_from, note_ar
       from vehicle_rates where is_active order by sort_order, starts_from`
  );
}

export async function getPlace(id: string): Promise<Place | null> {
  return q1<Place>(
    `select p.id, p.name_ar, p.category::text as category, p.zone_id,
            p.phone, p.whatsapp, p.address_note, p.hours_note, p.note, p.photo_id,
            (select round(avg(r.stars)::numeric,1) from ratings r
              where r.place_id = p.id and not r.is_hidden) as rating_avg,
            (select count(*) from ratings r
              where r.place_id = p.id and not r.is_hidden) as rating_count,
            z.name_ar as zone_name
       from places p
       join service_zones z on z.id = p.zone_id
      where p.id = $1 and p.is_active`,
    [id]
  );
}

export type Review = {
  id: string;
  stars: number;
  comment: string | null;
  author_name: string | null;
  created_at: string;
};

/** الريفيوز: التقييمات اللي فيها كلام مكتوب. */
export async function getReviews(opts: { providerId?: string; placeId?: string }) {
  return q<Review>(
    `select id, stars, comment, author_name, created_at
       from ratings
      where not is_hidden
        and comment is not null
        and ($1::uuid is null or provider_id = $1)
        and ($2::uuid is null or place_id = $2)
      order by created_at desc
      limit 30`,
    [opts.providerId ?? null, opts.placeId ?? null]
  );
}

/* ---------------- الإدارة ---------------- */

export async function adminListProviders() {
  return q<{
    id: string;
    display_name: string;
    phone: string;
    zone_name: string;
    services: ServiceKind[];
    is_verified: boolean;
    is_active: boolean;
    is_available: boolean;
    availability_updated_at: string | null;
    token: string | null;
    open_reports: string;
    photo_id: string | null;
  }>(
    `select p.id, p.display_name, p.phone, p.photo_id, z.name_ar as zone_name,
            p.services::text[] as services,
            p.is_verified, p.is_active, p.is_available, p.availability_updated_at,
            t.token,
            (select count(*) from reports r
              where r.provider_id = p.id and r.handled_at is null) as open_reports
       from providers p
       join service_zones z on z.id = p.zone_id
       left join provider_tokens t on t.provider_id = p.id
      order by p.is_active desc, p.display_name`
  );
}

export async function adminListReports() {
  return q<{
    id: string;
    provider_name: string;
    reason: string;
    details: string | null;
    reporter_phone: string | null;
    created_at: string;
    handled_at: string | null;
  }>(
    `select r.id, p.display_name as provider_name, r.reason::text as reason,
            r.details, r.reporter_phone, r.created_at, r.handled_at
       from reports r join providers p on p.id = r.provider_id
      order by r.handled_at nulls first, r.created_at desc
      limit 100`
  );
}

export async function adminListRequests() {
  return q<{
    id: string;
    body: string;
    contact_phone: string;
    is_hidden: boolean;
    created_at: string;
    expires_at: string;
  }>(
    `select id, body, contact_phone, is_hidden, created_at, expires_at
       from requests order by created_at desc limit 50`
  );
}

export async function adminListPlaces() {
  return q<{
    id: string;
    name_ar: string;
    category: string;
    zone_name: string;
    phone: string | null;
    whatsapp: string | null;
    hours_note: string | null;
    is_active: boolean;
  }>(
    `select p.id, p.name_ar, p.category::text as category, z.name_ar as zone_name,
            p.phone, p.whatsapp, p.hours_note, p.is_active
       from places p
       join service_zones z on z.id = p.zone_id
      order by p.is_active desc, p.sort_order, p.name_ar`
  );
}

export async function adminListApplications() {
  return q<{
    id: string;
    kind: 'driver' | 'place';
    status: string;
    name: string;
    phone: string;
    whatsapp: string | null;
    zone_name: string;
    note: string | null;
    photo_id: string | null;
    services: string[] | null;
    vehicle_note: string | null;
    category: string | null;
    address_note: string | null;
    hours_note: string | null;
    created_at: string;
  }>(
    `select a.id, a.kind::text as kind, a.status::text as status, a.name, a.phone,
            a.whatsapp, z.name_ar as zone_name, a.note, a.photo_id,
            a.services::text[] as services, a.vehicle_note,
            a.category::text as category, a.address_note, a.hours_note, a.created_at
       from applications a
       join service_zones z on z.id = a.zone_id
      order by (a.status = 'pending') desc, a.created_at desc
      limit 60`
  );
}

/** عدادات صغيرة للوحة الإدارة. */
export async function adminStats() {
  return q1<{
    drivers: string; live: string; places: string;
    pending: string; open_reports: string; open_requests: string;
  }>(
    `select
       (select count(*) from providers where is_active) as drivers,
       (select count(*) from providers_public where live) as live,
       (select count(*) from places where is_active) as places,
       (select count(*) from applications where status = 'pending') as pending,
       (select count(*) from reports where handled_at is null) as open_reports,
       (select count(*) from requests where expires_at > now() and not is_hidden) as open_requests`
  );
}

/* ---------------- المحادثة على الطلب ---------------- */

export type ChatMessage = {
  id: string;
  from_owner: boolean;
  sender_name: string | null;
  body: string;
  created_at: string;
};

/** خيط صاحب الطلب — بيتفتح بلينكه السري. */
export async function getThreadByOwnerToken(token: string) {
  return q1<{
    id: string; request_id: string; body: string; closed_at: string | null; expires_at: string;
  }>(
    `select t.id, t.request_id, r.body, t.closed_at, r.expires_at
       from threads t join requests r on r.id = t.request_id
      where t.owner_token = $1`,
    [token]
  );
}

/** نفس الخيط بس من ناحية السائق — بيتفتح بلينك السائق السري. */
export async function getThreadForProvider(requestId: string, providerToken: string) {
  return q1<{
    id: string; provider_id: string; provider_name: string; body: string; closed_at: string | null;
  }>(
    `select t.id, p.id as provider_id, p.display_name as provider_name, r.body, t.closed_at
       from threads t
       join requests r on r.id = t.request_id
       join provider_tokens pt on pt.token = $2
       join providers p on p.id = pt.provider_id and p.is_active
      where t.request_id = $1`,
    [requestId, providerToken]
  );
}

export async function getMessages(threadId: string): Promise<ChatMessage[]> {
  return q<ChatMessage>(
    `select m.id::text as id, m.from_owner, p.display_name as sender_name,
            m.body, m.created_at
       from messages m
       left join providers p on p.id = m.provider_id
      where m.thread_id = $1 and not m.is_hidden
      order by m.created_at`,
    [threadId]
  );
}

/** الطلبات اللي عليها محادثة — عشان السائق يشوفها من لوحة الطلبات. */
export async function getRequestThreadCounts() {
  return q<{ request_id: string; n: string }>(
    `select t.request_id, count(m.id) as n
       from threads t
       left join messages m on m.thread_id = t.id and not m.is_hidden
       join requests r on r.id = t.request_id
      where r.expires_at > now() and not r.is_hidden
      group by t.request_id`
  );
}
