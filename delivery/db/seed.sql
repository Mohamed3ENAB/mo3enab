-- ============================================================
--  بيانات مبدئية — المناطق والأسعار
--  ⚠️ الأسعار دي أسعار اختبار. راجع 02-pricing-economics.md
--     وعدّلها بعد ما تقيس المسافات الحقيقية.
-- ============================================================

insert into service_zones (name_ar, sort_order) values
  ('القيصرية',      1),
  ('بطينة',         2),
  ('محلة أبو علي',  3),
  ('محلة زياد',     4),
  ('المحلة الكبرى', 5)
on conflict (name_ar) do nothing;

-- داخل نفس القرية
insert into zone_pricing (from_zone_id, to_zone_id, base_fare, driver_share_pct)
select z.id, z.id, 35.00, 80.00
from service_zones z
where z.name_ar in ('القيصرية','بطينة','محلة أبو علي','محلة زياد')
on conflict do nothing;

-- بين القيصرية والقرى المجاورة (الاتجاهين)
insert into zone_pricing (from_zone_id, to_zone_id, base_fare, driver_share_pct)
select a.id, b.id, 45.00, 80.00
from service_zones a, service_zones b
where a.name_ar = 'القيصرية'
  and b.name_ar in ('بطينة','محلة أبو علي','محلة زياد')
on conflict do nothing;

insert into zone_pricing (from_zone_id, to_zone_id, base_fare, driver_share_pct)
select b.id, a.id, 45.00, 80.00
from service_zones a, service_zones b
where a.name_ar = 'القيصرية'
  and b.name_ar in ('بطينة','محلة أبو علي','محلة زياد')
on conflict do nothing;

-- مشوار المحلة الكبرى: مجدول (٨٠) + فوري (١٣٠)
insert into zone_pricing
  (from_zone_id, to_zone_id, base_fare, driver_share_pct, is_scheduled_only, express_fare)
select a.id, b.id, 80.00, 80.00, true, 130.00
from service_zones a, service_zones b
where a.name_ar = 'القيصرية' and b.name_ar = 'المحلة الكبرى'
on conflict do nothing;

-- إضافات السعر
insert into pricing_rules (code, label_ar, amount, percent) values
  ('waiting_10min',   'انتظار كل ١٠ دقائق بعد أول ١٠',  10.00, null),
  ('extra_stop',      'وقفة إضافية في نفس النطاق',        15.00, null),
  ('purchase_fee',    'رسوم طلب شراء',                    10.00, 5.00),
  ('night_surcharge', 'طلب ليلي بعد ٩ مساءً',             null,  15.00),
  ('cancel_after_move','إلغاء بعد تحرك السائق',           20.00, null)
on conflict (code) do nothing;
