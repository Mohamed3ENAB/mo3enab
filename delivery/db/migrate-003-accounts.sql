-- ============================================================
--  ترحيل ٠٠٣ — الصور · طلبات الانضمام · التقييمات للمحلات · المحادثات
--
--  التشغيل: psql -d <db> -v ON_ERROR_STOP=1 -f db/migrate-003-accounts.sql
--  schema.sql فيه ده كله للقواعد الجديدة.
-- ============================================================

-- ------------------------------------------------------------
-- ١) الصور
--    متخزنة في قاعدة البيانات نفسها مش في خدمة تخزين خارجية.
--    الصورة بتتصغّر لـ 256×256 webp قبل التخزين (~15 كيلو)،
--    فـ 200 صورة = 3 ميجا. ده بيوفر عليك خدمة زيادة تدفع فيها
--    وتظبطها، والعدد هنا عمره ما هيوصل لحد يستدعي غير كده.
-- ------------------------------------------------------------
create table if not exists images (
  id         uuid primary key default gen_random_uuid(),
  mime       text not null,
  bytes      bytea not null,
  byte_size  int not null,
  created_at timestamptz not null default now(),
  constraint images_size_sane check (byte_size > 0 and byte_size <= 400000)
);

alter table providers add column if not exists photo_id uuid references images(id) on delete set null;
alter table places    add column if not exists photo_id uuid references images(id) on delete set null;

-- ------------------------------------------------------------
-- ٢) طلبات الانضمام
--    السائق أو صاحب المحل بيسجّل نفسه، والطلب بيستنى موافقة
--    الإدارة قبل ما يظهر لأي حد. مفيش حاجة بتنشر لوحدها.
-- ------------------------------------------------------------
do $$ begin
  if not exists (select 1 from pg_type where typname = 'application_kind') then
    create type application_kind as enum ('driver','place');
  end if;
  if not exists (select 1 from pg_type where typname = 'application_status') then
    create type application_status as enum ('pending','approved','rejected');
  end if;
end $$;

create table if not exists applications (
  id            uuid primary key default gen_random_uuid(),
  kind          application_kind not null,
  status        application_status not null default 'pending',

  name          text not null,
  phone         text not null,
  whatsapp      text,
  zone_id       int not null references service_zones(id),
  note          text,
  photo_id      uuid references images(id) on delete set null,

  -- للسائق
  services      service_kind[],
  vehicle_note  text,

  -- للمحل
  category      place_category,
  address_note  text,
  hours_note    text,

  -- المراجعة
  reviewed_at   timestamptz,
  reject_reason text,
  created_at    timestamptz not null default now()
);
create index if not exists applications_pending_idx on applications (created_at desc) where status = 'pending';

-- ------------------------------------------------------------
-- ٣) التقييمات: تشمل المحلات كمان، مش السواقين بس
-- ------------------------------------------------------------
alter table ratings alter column provider_id drop not null;
alter table ratings add column if not exists place_id uuid references places(id) on delete cascade;
alter table ratings add column if not exists author_name text;
alter table ratings add column if not exists is_hidden boolean not null default false;

do $$ begin
  if not exists (select 1 from pg_constraint where conname = 'ratings_one_target') then
    alter table ratings add constraint ratings_one_target
      check ((provider_id is not null) <> (place_id is not null));
  end if;
end $$;
create index if not exists ratings_place_idx on ratings (place_id) where not is_hidden;

-- ------------------------------------------------------------
-- ٤) المحادثات على الطلب
--    مفيش حسابات. صاحب الطلب بياخد لينك سري لخيطه، والسائق
--    بيرد من لينكه السري اللي عنده أصلاً. نفس فكرة زرار التوفر.
-- ------------------------------------------------------------
create table if not exists threads (
  id             uuid primary key default gen_random_uuid(),
  request_id     uuid not null references requests(id) on delete cascade,
  owner_token    text not null unique default encode(gen_random_bytes(16), 'hex'),
  closed_at      timestamptz,
  created_at     timestamptz not null default now(),
  unique (request_id)
);

create table if not exists messages (
  id          bigserial primary key,
  thread_id   uuid not null references threads(id) on delete cascade,
  -- مين اللي كاتب: صاحب الطلب، أو سائق معيّن
  from_owner  boolean not null,
  provider_id uuid references providers(id) on delete set null,
  body        text not null check (length(btrim(body)) between 1 and 1000),
  is_hidden   boolean not null default false,
  created_at  timestamptz not null default now(),
  constraint messages_sender_ok check (from_owner or provider_id is not null)
);
create index if not exists messages_thread_idx on messages (thread_id, created_at);

-- خيط تلقائي لكل طلب جديد
create or replace function ensure_thread() returns trigger
language plpgsql as $$
begin
  insert into threads (request_id) values (new.id) on conflict (request_id) do nothing;
  return new;
end $$;

drop trigger if exists trg_request_thread on requests;
create trigger trg_request_thread after insert on requests
  for each row execute function ensure_thread();

-- ------------------------------------------------------------
-- ٥) إعادة بناء العرض العام: يضم الصورة ويستثني التقييمات المخفية
-- ------------------------------------------------------------
drop view if exists providers_public;
create or replace view providers_public as
  select
    p.id, p.display_name, p.phone, p.whatsapp, p.zone_id,
    p.services, p.vehicle_note, p.note, p.photo_id,
    p.is_verified, is_live(p) as live, p.availability_updated_at,
    (select round(avg(r.stars)::numeric, 1) from ratings r
      where r.provider_id = p.id and not r.is_hidden) as rating_avg,
    (select count(*) from ratings r
      where r.provider_id = p.id and not r.is_hidden) as rating_count
  from providers p
  where p.is_active;

-- ------------------------------------------------------------
-- ٦) ملاحظة على الصلاحيات
--    schema.sql مفيهوش RLS عن قصد: المتصفح مبيكلمش قاعدة البيانات،
--    والتحكم بيحصل في كود السيرفر. لو بتستخدم rls-supabase.sql،
--    هو فيه سياسات الجداول الجديدة دي كمان.
-- ------------------------------------------------------------
