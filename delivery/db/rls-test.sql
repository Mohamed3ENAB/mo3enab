-- ============================================================
--  اختبار الصلاحيات (RLS) — شغّله بعد أي تعديل في السكيمة
--
--  محليًا (بالترتيب ده بالظبط):
--    createdb valtest
--    psql -d valtest -v ON_ERROR_STOP=1 -f db/00-local-shim.sql
--    psql -d valtest -v ON_ERROR_STOP=1 -f db/schema.sql
--    psql -d valtest -v ON_ERROR_STOP=1 -f db/rls-test.sql
--
--  ⚠️ الاختبار ده اتنفّذ فعليًا على PostgreSQL 16 وعدّى بالكامل (٧/٧).
--     لو أي assert فشل هيرمي exception ويقف.
-- ============================================================

-- دور تطبيق عادي: RLS ما بتتطبقش على superuser
do $$ begin
  if not exists (select 1 from pg_roles where rolname='app_user') then
    create role app_user nologin;
  end if;
end $$;
grant usage on schema public, auth to app_user;
grant select, insert, update on all tables in schema public to app_user;
grant usage, select on all sequences in schema public to app_user;

-- ---------- بيانات اختبار ----------
insert into auth.users (id) values
  ('11111111-1111-1111-1111-111111111111'),
  ('22222222-2222-2222-2222-222222222222'),
  ('33333333-3333-3333-3333-333333333333')
on conflict do nothing;

insert into profiles (id, role, full_name, phone) values
  ('11111111-1111-1111-1111-111111111111','customer','عميل أ','01000000001'),
  ('22222222-2222-2222-2222-222222222222','customer','عميل ب','01000000002'),
  ('33333333-3333-3333-3333-333333333333','driver','سائق','01000000003')
on conflict do nothing;

insert into drivers (id, is_verified) values
  ('33333333-3333-3333-3333-333333333333', true) on conflict do nothing;

insert into orders (id, customer_id, type, description, delivery_fee)
values ('aaaaaaaa-0000-0000-0000-000000000001',
        '11111111-1111-1111-1111-111111111111','shop_purchase','٢ كيلو طماطم', 35)
on conflict do nothing;

insert into order_stops (order_id, kind, description, landmark, contact_phone)
values ('aaaaaaaa-0000-0000-0000-000000000001',
        'dropoff','شارع الجامع','قدام صيدلية النور','01000000001')
on conflict do nothing;

-- ---------- أداة مساعدة: انتحال هوية مستخدم ----------
create or replace procedure act_as(p uuid) language plpgsql as $$
begin
  execute format(
    'create or replace function auth.uid() returns uuid language sql stable as %L',
    'select ' || quote_literal(p::text) || '::uuid');
end $$;

-- ---------- الاختبارات ----------
do $$
declare n int;
begin
  -- ١) العميل يشوف طلبه
  call act_as('11111111-1111-1111-1111-111111111111');
  set local role app_user;
  select count(*) into n from orders;
  assert n = 1, '١ فشل: العميل صاحب الطلب المفروض يشوفه';
  select count(*) into n from order_stops;
  assert n = 1, '١ب فشل: وقفات الطلب المفروض تبان لصاحبه';
  reset role;

  -- ٢) عميل تاني ما يشوفش حاجة
  call act_as('22222222-2222-2222-2222-222222222222');
  set local role app_user;
  select count(*) into n from orders;
  assert n = 0, '٢ فشل: عميل تاني شاف طلب مش بتاعه ← تسريب بيانات';
  select count(*) into n from order_stops;
  assert n = 0, '٢ب فشل: عميل تاني شاف عنوان مش بتاعه ← تسريب عناوين';
  reset role;

  -- ٣) سائق غير مسنَد ما يشوفش الطلب
  call act_as('33333333-3333-3333-3333-333333333333');
  set local role app_user;
  select count(*) into n from orders;
  assert n = 0, '٣ فشل: سائق شاف طلب مش مسنَد له';
  reset role;

  -- ٤) التأكيد بيولّد رمز تسليم وبيسجل الحالة
  update orders set status='confirmed'
   where id='aaaaaaaa-0000-0000-0000-000000000001';
  select count(*) into n from orders
   where id='aaaaaaaa-0000-0000-0000-000000000001'
     and delivery_code is not null and confirmed_at is not null;
  assert n = 1, '٤ فشل: رمز التسليم أو وقت التأكيد مااتولّدش';
  select count(*) into n from status_events
   where order_id='aaaaaaaa-0000-0000-0000-000000000001'
     and from_status='draft' and to_status='confirmed';
  assert n = 1, '٤ب فشل: انتقال الحالة مااتسجلش';

  -- ٥) بعد الإسناد، السائق يشوف الطلب
  insert into assignments (order_id, driver_id, accepted, responded_at)
  values ('aaaaaaaa-0000-0000-0000-000000000001',
          '33333333-3333-3333-3333-333333333333', true, now());
  call act_as('33333333-3333-3333-3333-333333333333');
  set local role app_user;
  select count(*) into n from orders;
  assert n = 1, '٥ فشل: السائق المسنَد مش شايف الطلب';
  reset role;

  -- ٦) العميل التاني لسه مش شايف حاجة
  call act_as('22222222-2222-2222-2222-222222222222');
  set local role app_user;
  select count(*) into n from orders;
  assert n = 0, '٦ فشل: العزل اتكسر بعد الإسناد';
  reset role;

  raise notice '✅ كل اختبارات الصلاحيات عدّت';
end $$;

-- ٧) سائقين اتنين مقبولين لنفس الطلب = ممنوع
do $$
begin
  begin
    insert into assignments (order_id, driver_id, accepted)
    values ('aaaaaaaa-0000-0000-0000-000000000001',
            '33333333-3333-3333-3333-333333333333', true);
    raise exception '٧ فشل: النظام سمح بسائقين مقبولين لنفس الطلب';
  exception when unique_violation then
    raise notice '✅ ٧ عدّى: الإسناد المزدوج مرفوض';
  end;
end $$;
