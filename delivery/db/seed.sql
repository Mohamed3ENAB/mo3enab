-- ============================================================
--  بيانات مبدئية: القرى والأسعار الاسترشادية
--  ⚠️ الأسعار دي استرشادية وتقديرية. راجع 02-prices.md وعدّلها
--     بعد ما تسأل ٥ سواقين و١٠ ناس عن اللي بيتدفع فعلاً.
-- ============================================================

insert into service_zones (name_ar, sort_order) values
  ('القيصرية',      1),
  ('بطينة',         2),
  ('محلة أبو علي',  3),
  ('محلة زياد',     4),
  ('المحلة الكبرى', 5)
on conflict (name_ar) do nothing;

-- داخل نفس القرية
insert into price_guide (from_zone_id, to_zone_id, kind, typical_min, typical_max, note_ar)
select z.id, z.id, 'delivery', 20, 35, 'حسب المسافة جوه البلد'
from service_zones z
where z.name_ar in ('القيصرية','بطينة','محلة أبو علي','محلة زياد');

-- بين القيصرية والقرى المجاورة
insert into price_guide (from_zone_id, to_zone_id, kind, typical_min, typical_max, note_ar)
select a.id, b.id, 'delivery', 35, 50, 'بين القرى المتجاورة'
from service_zones a, service_zones b
where a.name_ar = 'القيصرية'
  and b.name_ar in ('بطينة','محلة أبو علي','محلة زياد');

-- مشوار المحلة الكبرى
insert into price_guide (from_zone_id, to_zone_id, kind, typical_min, typical_max, note_ar)
select a.id, b.id, 'mahalla_run', 70, 100, 'ذهاب وعودة — الانتظار الطويل بيزوّد'
from service_zones a, service_zones b
where a.name_ar = 'القيصرية' and b.name_ar = 'المحلة الكبرى';

-- عجلة للطلبات القريبة جدًا
insert into price_guide (from_zone_id, to_zone_id, kind, typical_min, typical_max, note_ar)
select z.id, z.id, 'bicycle', 15, 25, 'للطلبات القريبة جوه البلد'
from service_zones z where z.name_ar = 'القيصرية';

-- ============================================================
--  سعر البداية لكل مركبة
--  ⚠️ أرقام مبدئية — راجعها مع السواقين قبل ما تنشرها.
--  الفكرة: العجلة رخيصة عشان الشباب يقدروا يبدأوا بيها،
--  والطلب القريب يفضل مجدي للطرفين.
-- ============================================================
insert into vehicle_rates (kind, starts_from, note_ar, sort_order) values
  ('bicycle',      5.00,  'للطلبات القريبة جوه البلد',        1),
  ('delivery',    10.00,  'موتوسيكل — جوه البلد والقرى القريبة', 2),
  ('tuktuk',      15.00,  'للحاجات اللي محتاجة مساحة',        3),
  ('goods',       20.00,  'حسب الحجم والوزن — اتفق قبل',      4),
  ('mahalla_run', 70.00,  'ذهاب وعودة للمحلة الكبرى',         5)
on conflict (kind) do nothing;
