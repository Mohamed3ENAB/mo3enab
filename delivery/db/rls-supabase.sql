-- ============================================================
--  تقوية اختيارية: صلاحيات على مستوى الصفوف (RLS)
--
--  ⚠️ مش محتاجها لو بتستخدم تطبيق delivery/web زي ما هو — التحكم
--     في الصلاحيات بيحصل في السيرفر والمتصفح مبيوصلش لقاعدة البيانات.
--
--  شغّل الملف ده لو قررت تخلي المتصفح يكلم Supabase مباشرة بـ anon key.
--  التشغيل: schema.sql → seed.sql → rls-supabase.sql → rls-test.sql
--
--  ✅ الملف ده اتنفّذ واتختبر فعليًا على PostgreSQL 16.
-- ============================================================

-- على Supabase الدالة دي موجودة. محليًا بنعمل بديل بسيط.
create schema if not exists auth;
create table if not exists auth_admins (id uuid primary key);
create or replace function is_admin() returns boolean
language sql stable security definer set search_path = public as $$
  select coalesce(current_setting('app.is_admin', true) = 'on', false);
$$;

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
