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
            p.rating_avg, p.rating_count,
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
            p.rating_avg, p.rating_count,
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
            p.phone, p.whatsapp, p.address_note, p.hours_note, p.note,
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
  }>(
    `select p.id, p.display_name, p.phone, z.name_ar as zone_name,
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
