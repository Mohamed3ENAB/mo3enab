/** «من ١٢ دقيقة» — الصدق في التوقيت هو اللي بيبني الثقة في القايمة. */
export function sinceArabic(iso: string | null): string {
  if (!iso) return 'مش معروف';
  const mins = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
  if (mins < 1) return 'دلوقتي';
  if (mins === 1) return 'من دقيقة';
  if (mins === 2) return 'من دقيقتين';
  if (mins < 11) return `من ${mins} دقايق`;
  if (mins < 60) return `من ${mins} دقيقة`;
  const hrs = Math.floor(mins / 60);
  if (hrs === 1) return 'من ساعة';
  if (hrs === 2) return 'من ساعتين';
  if (hrs < 11) return `من ${hrs} ساعات`;
  if (hrs < 24) return `من ${hrs} ساعة`;
  const days = Math.floor(hrs / 24);
  if (days === 1) return 'من إمبارح';
  return `من ${days} يوم`;
}

/** «٣ سواقين متاحين» — العربي بيعد غير الإنجليزي. */
export function countArabic(n: number, one: string, two: string, few: string, many: string) {
  if (n === 0) return `مفيش ${many}`;
  if (n === 1) return one;
  if (n === 2) return two;
  if (n <= 10) return `${n} ${few}`;
  return `${n} ${many}`;
}

export function telHref(phone: string) {
  return `tel:${phone.replace(/[^\d+]/g, '')}`;
}

export function waHref(phone: string, text?: string) {
  let p = phone.replace(/[^\d]/g, '');
  if (p.startsWith('0')) p = '2' + p; // مصر
  const q = text ? `?text=${encodeURIComponent(text)}` : '';
  return `https://wa.me/${p}${q}`;
}
