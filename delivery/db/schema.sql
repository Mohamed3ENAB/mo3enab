-- ============================================================
--  في السكة — دليل السواقين والتوك توك المتاحين
--  خدمة مجتمعية: مفيش عمولة · مفيش إسناد · مفيش فلوس · مفيش حسابات عملاء
--
--  الترتيب محليًا:
--    00-local-shim.sql → schema.sql → seed.sql → rls-test.sql
--  على Supabase: schema.sql → seed.sql (الشيم مش محتاجه)
-- ============================================================

create extension if not exists "pgcrypto";

-- ------------------------------------------------------------
-- الأنواع
-- ------------------------------------------------------------
create type service_kind as enum (
  'delivery',      -- توصيل طلبات
  'tuktuk',        -- توك توك
  'goods',         -- نقل بضاعة / حاجات كبيرة
  'bicycle',       -- عجلة للطلبات القريبة
  'mahalla_run'    -- مشاوير المحلة الكبرى
);

create type report_reason as enum (
  'rude','overcharge','no_show','unsafe','wrong_number','other'
);

-- ------------------------------------------------------------
-- ١) القرى
-- ------------------------------------------------------------
create table service_zones (
  id         serial primary key,
  name_ar    text not null unique,
  is_active  boolean not null default true,
  sort_order int not null default 0
);

-- ------------------------------------------------------------
-- ٢) مقدمو الخدمة (السواقين والتوك توك)
--    ملاحظة: الجدول ده بيتقرا من غير تسجيل دخول — ده الهدف.
--    التليفون ظاهر عن قصد. الموافقة المكتوبة من السائق شرط (شوف 05-legal.md).
-- ------------------------------------------------------------
create table providers (
  id             uuid primary key default gen_random_uuid(),
  display_name   text not null,
  phone          text not null,
  whatsapp       text,
  zone_id        int  not null references service_zones(id),
  services       service_kind[] not null default '{}',
  vehicle_note   text,                       -- 'موتوسيكل' / 'توك توك أزرق'
  note           text,                       -- 'بيشتغل من ٢ لـ ١٠'

  -- شفت بطاقته ورخصته بنفسك؟
  is_verified    boolean not null default false,
  verified_at    date,

  -- التوفر
  is_available   boolean not null default false,
  availability_updated_at timestamptz,

  is_active      boolean not null default true,
  suspended_reason text,
  created_at     timestamptz not null default now()
);
create index on providers (zone_id) where is_active;
create index on providers using gin (services);

-- 🔴 التوفر بينتهي تلقائيًا بعد ٤ ساعات.
--    السائق بينسى يقفل الزرار وهو مشغول، والقايمة الكذابة بتضيّع ثقة الناس.
create or replace function is_live(p providers) returns boolean
language sql stable as $$
  select p.is_available
     and p.availability_updated_at is not null
     and p.availability_updated_at > now() - interval '4 hours';
$$;

-- ------------------------------------------------------------
-- ٣) لينك السائق الخاص — بدل كلمة السر
--    منفصل عن providers عشان ما يتقراش مع البيانات العامة.
-- ------------------------------------------------------------
create table provider_tokens (
  provider_id uuid primary key references providers(id) on delete cascade,
  token       text not null unique default encode(gen_random_bytes(16), 'hex'),
  created_at  timestamptz not null default now()
);

-- ------------------------------------------------------------
-- ٤) سجل التوفر — يوريك مين بيشتغل فعلاً ومتى
-- ------------------------------------------------------------
create table availability_log (
  id          bigserial primary key,
  provider_id uuid not null references providers(id) on delete cascade,
  is_available boolean not null,
  created_at  timestamptz not null default now()
);
create index on availability_log (provider_id, created_at desc);

-- ------------------------------------------------------------
-- ٥) لوحة الطلبات المفتوحة
--    «محتاج حد يجيبلي دوا من المحلة» → كل السواقين يشوفوها ويكلموه.
--    مفيش إسناد ومفيش متابعة — مجرد لوحة إعلانات.
-- ------------------------------------------------------------
create table requests (
  id          uuid primary key default gen_random_uuid(),
  zone_id     int references service_zones(id),
  kind        service_kind,
  body        text not null check (length(btrim(body)) between 5 and 500),
  contact_phone text not null,
  is_hidden   boolean not null default false,   -- الإدارة تخفي المسيء
  expires_at  timestamptz not null default now() + interval '6 hours',
  created_at  timestamptz not null default now()
);
create index on requests (expires_at) where not is_hidden;

-- ------------------------------------------------------------
-- ٦) التقييم — بسيط، من غير حساب
-- ------------------------------------------------------------
create table ratings (
  id          uuid primary key default gen_random_uuid(),
  provider_id uuid not null references providers(id) on delete cascade,
  stars       int not null check (stars between 1 and 5),
  comment     text check (comment is null or length(comment) <= 300),
  created_at  timestamptz not null default now()
);
create index on ratings (provider_id);

-- ------------------------------------------------------------
-- ٧) البلاغات — للإدارة بس، متبانش للعامة أبدًا
-- ------------------------------------------------------------
create table reports (
  id          uuid primary key default gen_random_uuid(),
  provider_id uuid not null references providers(id) on delete cascade,
  reason      report_reason not null,
  details     text,
  reporter_phone text,
  handled_at  timestamptz,
  created_at  timestamptz not null default now()
);

-- ------------------------------------------------------------
-- ٨) الأسعار الاسترشادية — حماية للناس، مش تسعيرة ملزمة
-- ------------------------------------------------------------
create table price_guide (
  id           serial primary key,
  from_zone_id int references service_zones(id),
  to_zone_id   int references service_zones(id),
  kind         service_kind,
  typical_min  numeric(10,2),
  typical_max  numeric(10,2),
  note_ar      text,
  is_active    boolean not null default true
);

-- ------------------------------------------------------------
-- ٩) الإدارة
-- ------------------------------------------------------------
create table admins (
  id         uuid primary key references auth.users(id) on delete cascade,
  name       text,
  created_at timestamptz not null default now()
);

create or replace function is_admin() returns boolean
language sql stable security definer set search_path = public as $$
  select exists (select 1 from admins where id = auth.uid());
$$;

-- ------------------------------------------------------------
-- ١٠) العرض العام — الأعمدة الآمنة فقط، بدون توكن
-- ------------------------------------------------------------
create view providers_public as
  select
    p.id, p.display_name, p.phone, p.whatsapp, p.zone_id,
    p.services, p.vehicle_note, p.note,
    p.is_verified, is_live(p) as live, p.availability_updated_at,
    (select round(avg(r.stars)::numeric, 1) from ratings r where r.provider_id = p.id) as rating_avg,
    (select count(*) from ratings r where r.provider_id = p.id) as rating_count
  from providers p
  where p.is_active;

-- ------------------------------------------------------------
-- ١١) تبديل التوفر باللينك الخاص — من غير تسجيل دخول
-- ------------------------------------------------------------
create or replace function toggle_availability(p_token text, p_available boolean)
returns table (name text, available boolean, updated_at timestamptz)
language plpgsql security definer set search_path = public as $$
declare v_id uuid;
begin
  select provider_id into v_id from provider_tokens where token = p_token;
  if v_id is null then
    raise exception 'لينك غير صحيح';
  end if;

  update providers
     set is_available = p_available,
         availability_updated_at = now()
   where id = v_id and is_active
   returning display_name, is_available, availability_updated_at
   into name, available, updated_at;

  if name is null then
    raise exception 'الحساب موقوف';
  end if;

  insert into availability_log (provider_id, is_available) values (v_id, p_available);
  return next;
end $$;

-- السائق يشوف بياناته بلينكه
create or replace function my_provider(p_token text)
returns table (id uuid, display_name text, is_available boolean,
               availability_updated_at timestamptz, is_verified boolean)
language sql security definer set search_path = public as $$
  select p.id, p.display_name, p.is_available, p.availability_updated_at, p.is_verified
  from providers p join provider_tokens t on t.provider_id = p.id
  where t.token = p_token and p.is_active;
$$;

-- ------------------------------------------------------------
-- ١٢) الصلاحيات
--     القاعدة: أي حد يقرا الدليل. محدش يعدّل غير الإدارة.
--     التوكنات والبلاغات ما تتقراش من العامة نهائيًا.
-- ------------------------------------------------------------
alter table providers        enable row level security;
alter table provider_tokens  enable row level security;
alter table availability_log enable row level security;
alter table requests         enable row level security;
alter table ratings          enable row level security;
alter table reports          enable row level security;
alter table price_guide      enable row level security;
alter table service_zones    enable row level security;
alter table admins           enable row level security;

-- قراءة عامة للدليل والقرى والأسعار
create policy z_read     on service_zones for select using (is_active);
create policy pg_read    on price_guide   for select using (is_active);
create policy prov_read  on providers     for select using (is_active);

-- الكتابة للإدارة بس
create policy prov_write  on providers    for all using (is_admin()) with check (is_admin());
create policy z_write     on service_zones for all using (is_admin()) with check (is_admin());
create policy pg_write    on price_guide  for all using (is_admin()) with check (is_admin());

-- 🔴 التوكنات: الإدارة بس. مفيش سياسة قراءة عامة خالص.
create policy tok_admin on provider_tokens for all using (is_admin()) with check (is_admin());

-- سجل التوفر: الإدارة بس (بيتكتب من الدالة security definer)
create policy log_admin on availability_log for select using (is_admin());

-- لوحة الطلبات: أي حد يكتب ويقرا الطلبات السارية
create policy req_read   on requests for select
  using (not is_hidden and expires_at > now());
create policy req_insert on requests for insert with check (true);
create policy req_admin  on requests for update using (is_admin()) with check (is_admin());

-- التقييم: أي حد يقيّم، والكل يشوف
create policy rate_read   on ratings for select using (true);
create policy rate_insert on ratings for insert with check (true);

-- 🔴 البلاغات: أي حد يبلّغ، والإدارة بس هي اللي تقرا.
create policy rep_insert on reports for insert with check (true);
create policy rep_read   on reports for select using (is_admin());

create policy adm_read on admins for select using (is_admin());

-- ------------------------------------------------------------
-- ١٣) صلاحيات الأدوار (Supabase: anon = زائر, authenticated = إدارة)
-- ------------------------------------------------------------
do $$ begin
  if not exists (select 1 from pg_roles where rolname='anon') then create role anon nologin; end if;
  if not exists (select 1 from pg_roles where rolname='authenticated') then create role authenticated nologin; end if;
end $$;

grant usage on schema public to anon, authenticated;
grant select on service_zones, price_guide, providers, ratings to anon, authenticated;
grant select on providers_public to anon, authenticated;
grant insert on requests, ratings, reports to anon, authenticated;
grant select on requests to anon, authenticated;
grant usage, select on all sequences in schema public to anon, authenticated;
grant execute on function toggle_availability(text, boolean) to anon, authenticated;
grant execute on function my_provider(text) to anon, authenticated;

-- 🔴 التوكنات والبلاغات: مفيش grant للزائر نهائيًا
revoke all on provider_tokens from anon, authenticated;
revoke all on reports from anon;
grant insert on reports to anon;

-- ============================================================
--  اختبارات لازم تعدّي (موجودة في rls-test.sql):
--    ١. الزائر يقرا الدليل من غير تسجيل.
--    ٢. الزائر ما يقدرش يقرا provider_tokens.
--    ٣. الزائر ما يقدرش يعدّل توفر سائق مباشرة.
--    ٤. اللينك الخاص بيغيّر التوفر فعلاً.
--    ٥. لينك غلط بيترفض.
--    ٦. التوفر بينتهي بعد ٤ ساعات.
--    ٧. الزائر ما يقدرش يقرا البلاغات.
-- ============================================================
