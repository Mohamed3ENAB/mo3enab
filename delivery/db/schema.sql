-- ============================================================
--  في السكة — دليل السواقين والتوك توك المتاحين
--  خدمة مجتمعية: مفيش عمولة · مفيش إسناد · مفيش فلوس · مفيش حسابات عملاء
--
--  التشغيل:  schema.sql → seed.sql
--  بتشتغل على أي PostgreSQL 14+ (محلي أو Supabase) من غير أي تجهيز.
--
--  التحكم في الصلاحيات بيحصل في كود السيرفر (delivery/web/src/lib/db.ts)
--  لأن المتصفح مبيكلمش قاعدة البيانات مباشرة.
--  لو حبيت تستخدم Supabase client من المتصفح، شغّل rls-supabase.sql كمان.
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

create type place_category as enum (
  'restaurant',   -- مطاعم
  'supermarket',  -- سوبر ماركت
  'grocery',      -- بقالة
  'herbalist',    -- عطار
  'bakery',       -- مخبز
  'butcher',      -- جزارة
  'produce',      -- خضار وفاكهة
  'pharmacy',     -- صيدلية
  'stationery',   -- مكتبة
  'sweets',       -- حلويات
  'hardware',     -- أدوات ومستلزمات
  'phones',       -- موبايلات
  'other'         -- غير كده
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
-- ٨) المحلات والمطاعم
--    دليل تاني جنب دليل السواقين: تكلّم المحل وتطلب، وبعدين
--    تكلّم سائق متاح يروح يستلم منه.
--    الأرقام دي أرقام أنشطة تجارية (معلنة على اللافتة عادة)،
--    مش أرقام شخصية — بس برضه استأذن صاحب المحل.
-- ------------------------------------------------------------
create table places (
  id            uuid primary key default gen_random_uuid(),
  name_ar       text not null,
  category      place_category not null,
  zone_id       int not null references service_zones(id),
  phone         text,
  whatsapp      text,
  address_note  text,                 -- 'جنب الجامع الكبير'
  hours_note    text,                 -- 'من 10ص لـ 12 بالليل'
  note          text,
  is_active     boolean not null default true,
  sort_order    int not null default 0,
  created_at    timestamptz not null default now(),
  constraint places_need_contact check (phone is not null or whatsapp is not null)
);
create index on places (category) where is_active;
create index on places (zone_id) where is_active;

-- ------------------------------------------------------------
-- ٩) سعر البداية لكل مركبة
--    «العجلة تبدأ من ٥ ج» — بيخلي الدخول سهل للشباب اللي عندهم
--    عجلة بس، والطلبات القريبة تبقى مجدية للطرفين.
-- ------------------------------------------------------------
create table vehicle_rates (
  kind        service_kind primary key,
  starts_from numeric(10,2) not null,
  note_ar     text,
  is_active   boolean not null default true,
  sort_order  int not null default 0
);

-- ------------------------------------------------------------
-- ١٠) الأسعار الاسترشادية — حماية للناس، مش تسعيرة ملزمة
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
-- ١١) الإدارة
--    مفيش جدول مستخدمين. لوحة الإدارة بتتحمي بكلمة سر في متغير بيئة
--    (ADMIN_PASSWORD) والتحقق بيحصل في السيرفر. أبسط حاجة تأمّن الغرض.
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- ١٢) العرض العام — الأعمدة الآمنة فقط، بدون توكن
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
-- ١٣) تبديل التوفر باللينك الخاص — من غير تسجيل دخول
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
