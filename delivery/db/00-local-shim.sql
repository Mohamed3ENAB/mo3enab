-- ============================================================
--  شيم للتشغيل المحلي فقط — لمحاكاة Supabase على PostgreSQL عادي.
--  ⚠️ متشغّلوش على Supabase — هي أصلاً عندها schema auth و auth.uid().
--  الترتيب محليًا:  00-local-shim.sql → schema.sql → seed.sql → rls-test.sql
-- ============================================================
create schema if not exists auth;
create table if not exists auth.users (id uuid primary key default gen_random_uuid());
create or replace function auth.uid() returns uuid language sql stable as $$ select null::uuid $$;
