-- ============================================================
--  اختبارات الصلاحيات — شغّلها بعد أي تعديل في السكيمة
--
--  محليًا (بالترتيب ده):
--    psql -d dalil -v ON_ERROR_STOP=1 -f db/00-local-shim.sql
--    psql -d dalil -v ON_ERROR_STOP=1 -f db/schema.sql
--    psql -d dalil -v ON_ERROR_STOP=1 -f db/seed.sql
--    psql -d dalil -v ON_ERROR_STOP=1 -f db/rls-test.sql
-- ============================================================

-- ---------- بيانات اختبار ----------
insert into providers (id, display_name, phone, zone_id, services, is_verified, is_active)
select 'bbbbbbbb-0000-0000-0000-000000000001',
       'سائق تجريبي', '01000000001', z.id,
       array['delivery','mahalla_run']::service_kind[], true, true
from service_zones z where z.name_ar='القيصرية'
on conflict do nothing;

insert into provider_tokens (provider_id, token)
values ('bbbbbbbb-0000-0000-0000-000000000001','TESTTOKEN123')
on conflict do nothing;

insert into reports (provider_id, reason, details)
values ('bbbbbbbb-0000-0000-0000-000000000001','other','بلاغ تجريبي')
on conflict do nothing;

-- ---------- الاختبارات ----------
do $$
declare n int; v_live boolean;
begin
  -- ١) الزائر يقرا الدليل من غير أي تسجيل
  set local role anon;
  select count(*) into n from providers_public;
  assert n >= 1, '١ فشل: الزائر مش شايف الدليل — ده الخدمة نفسها';
  reset role;

  -- ٢) الزائر ما يقدرش يقرا لينكات السواقين
  set local role anon;
  begin
    select count(*) into n from provider_tokens;
    raise exception '٢ فشل: الزائر قرا التوكنات ← أي حد يقدر يتحكم في توفر أي سائق';
  exception when insufficient_privilege then null;
  end;
  reset role;

  -- ٣) الزائر ما يقدرش يقرا البلاغات
  set local role anon;
  begin
    select count(*) into n from reports;
    assert n = 0, '٣ فشل: الزائر شاف البلاغات ← دي سرية';
  exception when insufficient_privilege then null;
  end;
  reset role;

  -- ٤) الزائر ما يقدرش يعدّل توفر سائق مباشرة
  set local role anon;
  begin
    update providers set is_available = true
     where id='bbbbbbbb-0000-0000-0000-000000000001';
    if found then
      raise exception '٤ فشل: الزائر عدّل بيانات سائق مباشرة';
    end if;
  exception when insufficient_privilege then null;
  end;
  reset role;

  raise notice '✅ اختبارات القراءة والكتابة عدّت';
end $$;

-- ٥) اللينك الخاص بيشتغل
do $$
declare r record;
begin
  set local role anon;
  select * into r from toggle_availability('TESTTOKEN123', true);
  assert r.available is true, '٥ فشل: اللينك الخاص مش بيفتح التوفر';
  reset role;
  raise notice '✅ ٥ عدّى: اللينك الخاص بيغيّر التوفر';
end $$;

-- ٦) لينك غلط بيترفض
do $$
begin
  set local role anon;
  begin
    perform toggle_availability('WRONG-TOKEN', true);
    raise exception '٦ فشل: لينك غلط اتقبل';
  exception when others then
    if sqlerrm like '%لينك غير صحيح%' then
      raise notice '✅ ٦ عدّى: اللينك الغلط مرفوض';
    else raise;
    end if;
  end;
  reset role;
end $$;

-- ٧) السائق بقى ظاهر كـ live بعد ما فتح توفره
do $$
declare v boolean;
begin
  set local role anon;
  select live into v from providers_public
   where id='bbbbbbbb-0000-0000-0000-000000000001';
  assert v is true, '٧ فشل: السائق فتح توفره بس مش ظاهر live';
  reset role;
  raise notice '✅ ٧ عدّى: التوفر بيظهر في الدليل';
end $$;

-- ٨) التوفر بينتهي تلقائيًا بعد ٤ ساعات
do $$
declare v boolean;
begin
  update providers
     set availability_updated_at = now() - interval '5 hours'
   where id='bbbbbbbb-0000-0000-0000-000000000001';
  set local role anon;
  select live into v from providers_public
   where id='bbbbbbbb-0000-0000-0000-000000000001';
  assert v is false, '٨ فشل: توفر قديم لسه بيتعرض ← قايمة كذابة بتضيّع الثقة';
  reset role;
  raise notice '✅ ٨ عدّى: التوفر القديم بينتهي لوحده';
end $$;

-- ٩) أي حد يقدر يكتب طلب في اللوحة
do $$
declare n int;
begin
  set local role anon;
  insert into requests (kind, body, contact_phone)
  values ('delivery','محتاج حد يجيبلي دوا من المحلة','01000000009');
  select count(*) into n from requests where expires_at > now();
  assert n >= 1, '٩ فشل: لوحة الطلبات مش شغالة للزائر';
  reset role;
  raise notice '✅ ٩ عدّى: لوحة الطلبات مفتوحة للكل';
end $$;
