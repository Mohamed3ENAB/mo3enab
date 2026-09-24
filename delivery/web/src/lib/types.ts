export type ServiceKind = 'delivery' | 'tuktuk' | 'goods' | 'bicycle' | 'mahalla_run';

export const SERVICE_LABELS: Record<ServiceKind, string> = {
  delivery: 'دليفري',
  tuktuk: 'توك توك',
  goods: 'نقل بضاعة',
  bicycle: 'عجلة',
  mahalla_run: 'مشوار المحلة',
};

export const SERVICE_ORDER: ServiceKind[] = [
  'delivery',
  'tuktuk',
  'mahalla_run',
  'goods',
  'bicycle',
];

export type Zone = { id: number; name_ar: string };

export type Provider = {
  id: string;
  display_name: string;
  phone: string;
  whatsapp: string | null;
  zone_id: number;
  zone_name: string;
  services: ServiceKind[];
  vehicle_note: string | null;
  note: string | null;
  is_verified: boolean;
  live: boolean;
  availability_updated_at: string | null;
  rating_avg: string | null;
  rating_count: string;
  photo_id: string | null;
};

export type OpenRequest = {
  id: string;
  zone_name: string | null;
  kind: ServiceKind | null;
  body: string;
  /** null لما صاحب الطلب يخفي رقمه — الرقم الحقيقي مبيخرجش من السيرفر أصلًا */
  contact_phone: string | null;
  masked_phone: string | null;
  hide_phone: boolean;
  created_at: string;
  expires_at: string;
};

export type PriceRow = {
  from_zone: string | null;
  to_zone: string | null;
  kind: ServiceKind | null;
  typical_min: string | null;
  typical_max: string | null;
  note_ar: string | null;
};

export const REPORT_REASONS = {
  rude: 'معاملة وحشة',
  overcharge: 'طلب سعر مبالغ فيه',
  no_show: 'اتفق ومجاش',
  unsafe: 'قيادة خطر أو سلوك مقلق',
  wrong_number: 'الرقم غلط أو مش بيرد',
  other: 'حاجة تانية',
} as const;

export type ReportReason = keyof typeof REPORT_REASONS;

/* ---------------- المحلات ---------------- */

export type PlaceCategory =
  | 'restaurant' | 'supermarket' | 'grocery' | 'herbalist' | 'bakery'
  | 'butcher' | 'produce' | 'pharmacy' | 'stationery' | 'sweets'
  | 'hardware' | 'phones' | 'other';

export const PLACE_LABELS: Record<PlaceCategory, string> = {
  restaurant: 'مطاعم',
  supermarket: 'سوبر ماركت',
  grocery: 'بقالة',
  herbalist: 'عطارة',
  bakery: 'مخبز',
  butcher: 'جزارة',
  produce: 'خضار وفاكهة',
  pharmacy: 'صيدلية',
  stationery: 'مكتبة',
  sweets: 'حلويات',
  hardware: 'أدوات ومستلزمات',
  phones: 'موبايلات',
  other: 'غير كده',
};

/** ترتيب العرض: اللي بيتطلب أكتر الأول. */
export const PLACE_ORDER: PlaceCategory[] = [
  'restaurant', 'supermarket', 'grocery', 'produce', 'bakery',
  'herbalist', 'butcher', 'sweets', 'pharmacy', 'stationery',
  'hardware', 'phones', 'other',
];

export type Place = {
  id: string;
  name_ar: string;
  category: PlaceCategory;
  zone_id: number;
  zone_name: string;
  phone: string | null;
  whatsapp: string | null;
  address_note: string | null;
  hours_note: string | null;
  note: string | null;
  photo_id: string | null;
  rating_avg: string | null;
  rating_count: string;
};

export type VehicleRate = {
  kind: ServiceKind;
  starts_from: string;
  note_ar: string | null;
};
