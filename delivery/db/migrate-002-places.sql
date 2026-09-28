-- ============================================================
--  ترحيل ٠٠٢ — المحلات وأسعار البداية
--
--  شغّله لو عندك قاعدة بيانات اتعملت بنسخة أقدم من schema.sql.
--  لو بتعمل قاعدة جديدة، schema.sql فيه ده كله وماتحتاجش الملف ده.
--  التشغيل: psql -d <db> -v ON_ERROR_STOP=1 -f db/migrate-002-places.sql
-- ============================================================

do $$ begin
  if not exists (select 1 from pg_type where typname = 'place_category') then
    create type place_category as enum (
      'restaurant','supermarket','grocery','herbalist','bakery','butcher',
      'produce','pharmacy','stationery','sweets','hardware','phones','other'
    );
  end if;
end $$;

create table if not exists places (
  id            uuid primary key default gen_random_uuid(),
  name_ar       text not null,
  category      place_category not null,
  zone_id       int not null references service_zones(id),
  phone         text,
  whatsapp      text,
  address_note  text,
  hours_note    text,
  note          text,
  is_active     boolean not null default true,
  sort_order    int not null default 0,
  created_at    timestamptz not null default now(),
  constraint places_need_contact check (phone is not null or whatsapp is not null)
);
create index if not exists places_category_idx on places (category) where is_active;
create index if not exists places_zone_idx on places (zone_id) where is_active;

create table if not exists vehicle_rates (
  kind        service_kind primary key,
  starts_from numeric(10,2) not null,
  note_ar     text,
  is_active   boolean not null default true,
  sort_order  int not null default 0
);

insert into vehicle_rates (kind, starts_from, note_ar, sort_order) values
  ('bicycle',      5.00,  'للطلبات القريبة جوه البلد',           1),
  ('delivery',    10.00,  'موتوسيكل — جوه البلد والقرى القريبة', 2),
  ('tuktuk',      15.00,  'للحاجات اللي محتاجة مساحة',           3),
  ('goods',       20.00,  'حسب الحجم والوزن — اتفق قبل',         4),
  ('mahalla_run', 70.00,  'ذهاب وعودة للمحلة الكبرى',            5)
on conflict (kind) do nothing;

alter table places        enable row level security;
alter table vehicle_rates enable row level security;

do $$ begin
  if not exists (select 1 from pg_policies where tablename='places' and policyname='pl_read') then
    create policy pl_read on places for select using (is_active);
  end if;
  if not exists (select 1 from pg_policies where tablename='vehicle_rates' and policyname='vr_read') then
    create policy vr_read on vehicle_rates for select using (is_active);
  end if;
end $$;

grant select on places, vehicle_rates to anon, authenticated;
