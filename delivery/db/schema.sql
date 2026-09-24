-- ============================================================
--  في السكة — مخطط قاعدة البيانات (PostgreSQL / Supabase)
--  النسخة الأولى: عميل · سائق · إدارة
--  شغّله في محرر SQL في Supabase، أو كملف ترحيل (migration).
-- ============================================================

create extension if not exists "pgcrypto";

-- ------------------------------------------------------------
-- ١) الأنواع
-- ------------------------------------------------------------
create type user_role       as enum ('customer','driver','admin','merchant');
create type vehicle_type    as enum ('bicycle','motorcycle','tuktuk','car');
create type order_type      as enum ('shop_purchase','parcel','mahalla_run','intercity_quote');
create type order_status    as enum (
  'draft','awaiting_quote','awaiting_confirm','confirmed','assigned',
  'to_pickup','picked_up','to_dropoff','delivered','cancelled','disputed'
);
create type doc_status      as enum ('pending','approved','rejected','expired');
create type payment_method  as enum ('cash','transfer','card','wallet');
create type stop_kind       as enum ('pickup','dropoff');
create type settlement_state as enum ('open','submitted','reconciled','frozen');

-- ------------------------------------------------------------
-- ٢) المستخدمون
--    ملاحظة: auth.users بتاعة Supabase هي مصدر الهوية.
--    الجدول ده بيضيف الدور والحالة.
-- ------------------------------------------------------------
create table profiles (
  id            uuid primary key references auth.users(id) on delete cascade,
  role          user_role   not null default 'customer',
  full_name     text,
  phone         text        not null unique,
  is_active     boolean     not null default true,
  -- مؤشر مخاطر بسيط: بيزيد مع الإلغاء المتكرر والبلاغات
  risk_score    int         not null default 0,
  created_at    timestamptz not null default now()
);
create index on profiles (role) where is_active;

-- ------------------------------------------------------------
-- ٣) المناطق والتسعير
-- ------------------------------------------------------------
create table service_zones (
  id          serial primary key,
  name_ar     text not null unique,          -- 'القيصرية'
  is_active   boolean not null default true,
  sort_order  int not null default 0
);

-- السعر بين منطقة ومنطقة. (from = to) يعني داخل نفس القرية.
create table zone_pricing (
  id                serial primary key,
  from_zone_id      int not null references service_zones(id),
  to_zone_id        int not null references service_zones(id),
  base_fare         numeric(10,2) not null,
  driver_share_pct  numeric(5,2)  not null default 80.00,
  -- مشوار المحلة المجدول بيتسعّر مختلف عن الفوري
  is_scheduled_only boolean not null default false,
  express_fare      numeric(10,2),           -- سعر الفوري لو متاح
  is_active         boolean not null default true,
  unique (from_zone_id, to_zone_id, is_scheduled_only)
);

-- إضافات السعر (انتظار، وقفة زيادة، ليلي...)
create table pricing_rules (
  id          serial primary key,
  code        text not null unique,          -- 'waiting_10min'
  label_ar    text not null,
  amount      numeric(10,2),
  percent     numeric(5,2),
  is_active   boolean not null default true
);

-- ------------------------------------------------------------
-- ٤) العناوين — القلب. دبوس الخريطة لوحده مش كفاية في القرى.
-- ------------------------------------------------------------
create table addresses (
  id           uuid primary key default gen_random_uuid(),
  owner_id     uuid not null references profiles(id) on delete cascade,
  zone_id      int  not null references service_zones(id),
  label        text,                          -- 'البيت' / 'الدكان'
  description  text not null,                 -- الوصف المكتوب — إجباري
  landmark     text not null,                 -- العلامة المميزة — إجباري
  contact_phone text not null,
  driver_note  text,
  lat          numeric(10,7),                 -- اختياري
  lng          numeric(10,7),
  is_archived  boolean not null default false,
  created_at   timestamptz not null default now()
);
create index on addresses (owner_id) where not is_archived;

-- ------------------------------------------------------------
-- ٥) السائقون والمركبات والمستندات
-- ------------------------------------------------------------
create table drivers (
  id                uuid primary key references profiles(id) on delete cascade,
  is_verified       boolean not null default false,
  is_available      boolean not null default false,
  -- أقصى قيمة مشتريات مسموح للسائق يحملها
  purchase_cap      numeric(10,2) not null default 300.00,
  cash_float        numeric(10,2) not null default 0,   -- العهدة اللي معاه
  allowed_zones     int[] not null default '{}',
  rating_avg        numeric(3,2),
  rating_count      int not null default 0,
  -- ترتيب الأولوية في الإسناد. بيقل لو السائق بياخد طلبات برة المنصة.
  priority_score    int not null default 100,
  suspended_reason  text,
  created_at        timestamptz not null default now()
);

create table vehicles (
  id              uuid primary key default gen_random_uuid(),
  driver_id       uuid not null references drivers(id) on delete cascade,
  kind            vehicle_type not null,
  plate_number    text,
  license_expiry  date,
  is_active       boolean not null default true
);

create table driver_documents (
  id            uuid primary key default gen_random_uuid(),
  driver_id     uuid not null references drivers(id) on delete cascade,
  doc_type      text not null,                -- 'national_id' | 'driving_license' | ...
  storage_path  text not null,                -- bucket خاص — مش public أبدًا
  expires_on    date,
  status        doc_status not null default 'pending',
  reviewed_by   uuid references profiles(id),
  reviewed_at   timestamptz,
  created_at    timestamptz not null default now()
);
create index on driver_documents (driver_id, status);
-- للتعطيل التلقائي عند انتهاء الرخصة
create index on driver_documents (expires_on) where status = 'approved';

-- ------------------------------------------------------------
-- ٦) المتاجر (مرحلة ٢ — بس الجدول موجود من دلوقتي)
-- ------------------------------------------------------------
create table merchants (
  id             uuid primary key default gen_random_uuid(),
  name_ar        text not null,
  zone_id        int references service_zones(id),
  phone          text,
  address_text   text,
  commission_pct numeric(5,2) default 0,
  is_active      boolean not null default true
);

-- ------------------------------------------------------------
-- ٧) مشاوير المحلة المجدولة
-- ------------------------------------------------------------
create table scheduled_runs (
  id            uuid primary key default gen_random_uuid(),
  zone_id       int not null references service_zones(id),
  departs_at    timestamptz not null,
  cutoff_at     timestamptz not null,          -- إقفال الطلبات
  driver_id     uuid references drivers(id),
  capacity      int not null default 5,
  min_orders    int not null default 2,
  is_cancelled  boolean not null default false,
  created_at    timestamptz not null default now()
);
create index on scheduled_runs (departs_at) where not is_cancelled;

-- ------------------------------------------------------------
-- ٨) الطلبات
-- ------------------------------------------------------------
create table orders (
  id                uuid primary key default gen_random_uuid(),
  order_no          bigserial unique,           -- الرقم اللي بيتقال للعميل
  customer_id       uuid not null references profiles(id),
  type              order_type not null,
  status            order_status not null default 'draft',

  -- وصف حر + مرفقات: أهم من الكتالوج في النسخة الأولى
  description       text,
  photo_paths       text[] default '{}',
  voice_path        text,

  merchant_id       uuid references merchants(id),
  scheduled_run_id  uuid references scheduled_runs(id),

  -- الفلوس: ثمن البضاعة منفصل عن الأجرة. مفيش استثناء.
  goods_estimate    numeric(10,2),
  goods_actual      numeric(10,2),
  delivery_fee      numeric(10,2),
  surcharges        numeric(10,2) not null default 0,
  driver_payout     numeric(10,2),
  platform_margin   numeric(10,2),
  payment_method    payment_method not null default 'cash',

  delivery_code     char(4),                   -- يتولّد عند التأكيد
  confirmed_at      timestamptz,
  delivered_at      timestamptz,
  cancelled_reason  text,
  created_at        timestamptz not null default now()
);
create index on orders (customer_id, created_at desc);
create index on orders (status) where status not in ('delivered','cancelled');
create index on orders (scheduled_run_id);

create table order_stops (
  id          uuid primary key default gen_random_uuid(),
  order_id    uuid not null references orders(id) on delete cascade,
  kind        stop_kind not null,
  seq         int not null default 1,
  zone_id     int references service_zones(id),
  -- لقطة من العنوان وقت الطلب — عشان لو العميل عدّل عنوانه بعدين
  description text,
  landmark    text,
  contact_phone text,
  lat         numeric(10,7),
  lng         numeric(10,7)
);
create index on order_stops (order_id);

create table order_items (
  id            uuid primary key default gen_random_uuid(),
  order_id      uuid not null references orders(id) on delete cascade,
  description   text not null,
  qty           numeric(10,2) not null default 1,
  actual_price  numeric(10,2),
  receipt_path  text                            -- صورة الإيصال
);

create table quotes (
  id           uuid primary key default gen_random_uuid(),
  order_id     uuid not null references orders(id) on delete cascade,
  amount       numeric(10,2) not null,
  eta_note     text,
  terms        text,
  valid_until  timestamptz not null,
  created_by   uuid not null references profiles(id),
  accepted_at  timestamptz,
  created_at   timestamptz not null default now()
);

create table assignments (
  id           uuid primary key default gen_random_uuid(),
  order_id     uuid not null references orders(id) on delete cascade,
  driver_id    uuid not null references drivers(id),
  offered_at   timestamptz not null default now(),
  responded_at timestamptz,
  accepted     boolean,
  decline_reason text
);
create index on assignments (driver_id, accepted);
-- سائق واحد مقبول لكل طلب
create unique index on assignments (order_id) where accepted is true;

create table status_events (
  id          bigserial primary key,
  order_id    uuid not null references orders(id) on delete cascade,
  from_status order_status,
  to_status   order_status not null,
  actor_id    uuid references profiles(id),
  note        text,
  created_at  timestamptz not null default now()
);
create index on status_events (order_id, created_at);

-- ------------------------------------------------------------
-- ٩) الفلوس والتسويات
-- ------------------------------------------------------------
create table payments (
  id         uuid primary key default gen_random_uuid(),
  order_id   uuid not null references orders(id),
  method     payment_method not null,
  amount     numeric(10,2) not null,
  reference  text,
  collected_by uuid references profiles(id),
  created_at timestamptz not null default now()
);

create table driver_settlements (
  id             uuid primary key default gen_random_uuid(),
  driver_id      uuid not null references drivers(id),
  business_date  date not null,
  expected_cash  numeric(10,2) not null default 0,
  handed_cash    numeric(10,2) not null default 0,
  -- الفرق = المتوقع − المسلَّم. لازم صفر.
  variance       numeric(10,2) generated always as (expected_cash - handed_cash) stored,
  state          settlement_state not null default 'open',
  note           text,
  closed_by      uuid references profiles(id),
  closed_at      timestamptz,
  unique (driver_id, business_date)
);

create table settlement_lines (
  id             uuid primary key default gen_random_uuid(),
  settlement_id  uuid not null references driver_settlements(id) on delete cascade,
  order_id       uuid not null references orders(id),
  goods_paid     numeric(10,2) not null default 0,   -- اللي صرفه من العهدة
  cash_collected numeric(10,2) not null default 0,
  driver_payout  numeric(10,2) not null default 0
);

-- ------------------------------------------------------------
-- ١٠) الجودة والدعم والتدقيق
-- ------------------------------------------------------------
create table ratings (
  id         uuid primary key default gen_random_uuid(),
  order_id   uuid not null references orders(id) unique,
  stars      int not null check (stars between 1 and 5),
  comment    text,
  created_at timestamptz not null default now()
);

create table support_tickets (
  id           uuid primary key default gen_random_uuid(),
  order_id     uuid references orders(id),
  opened_by    uuid not null references profiles(id),
  category     text not null,
  body         text not null,
  resolution   text,
  refund_amount numeric(10,2) default 0,
  closed_at    timestamptz,
  created_at   timestamptz not null default now()
);

-- كل فعل إداري حساس. ده سجلك في أي خلاف.
create table audit_logs (
  id         bigserial primary key,
  actor_id   uuid references profiles(id),
  action     text not null,             -- 'override_delivery_code' | 'view_driver_doc' | ...
  entity     text not null,
  entity_id  text,
  meta       jsonb,
  created_at timestamptz not null default now()
);
create index on audit_logs (entity, entity_id);
create index on audit_logs (actor_id, created_at desc);

-- ------------------------------------------------------------
-- ١١) دوال مساعدة
-- ------------------------------------------------------------
create or replace function is_admin() returns boolean
language sql stable security definer set search_path = public as $$
  select exists (
    select 1 from profiles
    where id = auth.uid() and role = 'admin' and is_active
  );
$$;

create or replace function my_role() returns user_role
language sql stable security definer set search_path = public as $$
  select role from profiles where id = auth.uid();
$$;

-- رمز التسليم: ٤ أرقام عند التأكيد
create or replace function gen_delivery_code() returns char(4)
language sql volatile as $$
  select lpad((floor(random()*10000))::int::text, 4, '0')::char(4);
$$;

-- تسجيل تلقائي لكل انتقال حالة
create or replace function log_status_change() returns trigger
language plpgsql as $$
begin
  if tg_op = 'UPDATE' and new.status is distinct from old.status then
    insert into status_events (order_id, from_status, to_status, actor_id)
    values (new.id, old.status, new.status, auth.uid());
  end if;
  -- ولّد رمز التسليم أول ما الطلب يتأكد
  if new.status = 'confirmed' and new.delivery_code is null then
    new.delivery_code := gen_delivery_code();
    new.confirmed_at  := now();
  end if;
  return new;
end;
$$;

create trigger trg_order_status
  before update on orders
  for each row execute function log_status_change();

-- ------------------------------------------------------------
-- ١٢) سياسات الصلاحيات (RLS)
--     القاعدة: العميل يشوف طلباته هو، والسائق يشوف الطلب المسنَد له بس.
--     ده بيتطبق هنا في قاعدة البيانات — مش في الواجهة.
-- ------------------------------------------------------------
alter table profiles          enable row level security;
alter table addresses         enable row level security;
alter table orders            enable row level security;
alter table order_stops       enable row level security;
alter table order_items       enable row level security;
alter table assignments       enable row level security;
alter table status_events     enable row level security;
alter table driver_documents  enable row level security;
alter table drivers           enable row level security;
alter table payments          enable row level security;
alter table driver_settlements enable row level security;
alter table ratings           enable row level security;
alter table support_tickets   enable row level security;
alter table audit_logs        enable row level security;

-- الملف الشخصي
create policy p_profiles_self on profiles
  for select using (id = auth.uid() or is_admin());
create policy p_profiles_update_self on profiles
  for update using (id = auth.uid()) with check (id = auth.uid());

-- العناوين
create policy p_addresses_owner on addresses
  for all using (owner_id = auth.uid() or is_admin())
  with check (owner_id = auth.uid() or is_admin());

-- الطلبات: العميل صاحبها، أو السائق اللي قبلها، أو الإدارة
create policy p_orders_read on orders
  for select using (
    customer_id = auth.uid()
    or is_admin()
    or exists (
      select 1 from assignments a
      where a.order_id = orders.id
        and a.driver_id = auth.uid()
        and a.accepted is true
    )
  );

create policy p_orders_insert on orders
  for insert with check (customer_id = auth.uid() or is_admin());

-- العميل يعدّل طلبه وهو مسودة بس. أي حاجة بعد كده للإدارة.
create policy p_orders_update on orders
  for update using (
    is_admin()
    or (customer_id = auth.uid() and status in ('draft','awaiting_confirm'))
    or exists (
      select 1 from assignments a
      where a.order_id = orders.id and a.driver_id = auth.uid() and a.accepted is true
    )
  );

-- الوقفات والأصناف بتتبع صلاحية الطلب
create policy p_stops on order_stops for select using (
  exists (select 1 from orders o where o.id = order_stops.order_id)
);
create policy p_items on order_items for select using (
  exists (select 1 from orders o where o.id = order_items.order_id)
);

-- الإسنادات: السائق يشوف عروضه هو
create policy p_assignments on assignments
  for select using (driver_id = auth.uid() or is_admin());
create policy p_assignments_respond on assignments
  for update using (driver_id = auth.uid() or is_admin());

-- سجل الحالات: للقراءة بس، ومحدش يعدّله
create policy p_status_read on status_events
  for select using (
    is_admin() or exists (select 1 from orders o where o.id = status_events.order_id)
  );

-- 🔴 مستندات السائقين: السائق صاحبها والإدارة بس. تخزين خاص دايمًا.
create policy p_docs on driver_documents
  for select using (driver_id = auth.uid() or is_admin());
create policy p_docs_insert on driver_documents
  for insert with check (driver_id = auth.uid() or is_admin());

create policy p_drivers_self on drivers
  for select using (id = auth.uid() or is_admin());
create policy p_drivers_update on drivers
  for update using (id = auth.uid() or is_admin());

-- الفلوس: الإدارة، والسائق يشوف بتاعه
create policy p_payments on payments for select using (is_admin());
create policy p_settlements on driver_settlements
  for select using (driver_id = auth.uid() or is_admin());

create policy p_ratings_insert on ratings
  for insert with check (
    exists (select 1 from orders o where o.id = order_id and o.customer_id = auth.uid())
  );
create policy p_ratings_read on ratings for select using (true);

create policy p_tickets on support_tickets
  for select using (opened_by = auth.uid() or is_admin());
create policy p_tickets_insert on support_tickets
  for insert with check (opened_by = auth.uid());

-- 🔴 سجل التدقيق: الإدارة تقرا بس. محدش يعدّل ولا يمسح.
create policy p_audit_read on audit_logs for select using (is_admin());

-- ============================================================
-- اختبارات لازم تعديها قبل الإطلاق (اكتبها كاختبارات فعلية):
--   ١. عميل (أ) ما يقدرش يقرا طلب عميل (ب).
--   ٢. سائق ما يقدرش يقرا طلب مش مسنَد له.
--   ٣. سائق ما يقدرش يغيّر الحالة لـ delivered من غير رمز صحيح.
--   ٤. عميل ما يقدرش يعدّل delivery_fee من المتصفح.
--   ٥. غير الإدارة ما يقدرش يقرا driver_documents.
--   ٦. محدش يقدر يعمل delete في audit_logs.
-- ============================================================
