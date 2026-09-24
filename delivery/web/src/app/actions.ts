'use server';

import { revalidatePath } from 'next/cache';
import { redirect } from 'next/navigation';
import { q, q1 } from '@/lib/db';
import { checkPassword, createSession, destroySession, isLoggedIn } from '@/lib/auth';
import { storeImage } from '@/lib/images';
import type { ServiceKind } from '@/lib/types';

const SERVICE_KINDS: ServiceKind[] = [
  'delivery', 'tuktuk', 'goods', 'bicycle', 'mahalla_run',
];
const REASONS = ['rude', 'overcharge', 'no_show', 'unsafe', 'wrong_number', 'other'];
const PLACE_CATEGORIES = [
  'restaurant', 'supermarket', 'grocery', 'herbalist', 'bakery', 'butcher',
  'produce', 'pharmacy', 'stationery', 'sweets', 'hardware', 'phones', 'other',
];

type Result = { ok: boolean; message?: string; threadToken?: string };

function str(v: FormDataEntryValue | null, max = 500) {
  return typeof v === 'string' ? v.trim().slice(0, max) : '';
}

/** كل فعل إداري بيعدي من هنا. مفيش استثناء. */
async function requireAdmin() {
  if (!(await isLoggedIn())) throw new Error('مش مسموح');
}

/* ---------------- السائق: فتح وقفل التوفر ---------------- */

export async function setAvailability(token: string, available: boolean): Promise<Result> {
  const t = token.trim();
  if (!t) return { ok: false, message: 'اللينك ناقص.' };

  try {
    const rows = await q<{ name: string; available: boolean }>(
      `select name, available from toggle_availability($1, $2)`,
      [t, available]
    );
    if (rows.length === 0) return { ok: false, message: 'اللينك مش صح.' };
  } catch {
    // الدالة بترمي خطأ للينك الغلط أو الحساب الموقوف
    return { ok: false, message: 'اللينك مش صح، أو الحساب موقوف. كلّم الإدارة.' };
  }

  revalidatePath('/');
  revalidatePath(`/d/${t}`);
  return { ok: true };
}

/* ---------------- لوحة الطلبات ---------------- */

export async function postRequest(formData: FormData): Promise<Result> {
  const body = str(formData.get('body'), 500);
  const phone = str(formData.get('contact_phone'), 20);
  const kindRaw = str(formData.get('kind'), 20);
  const zoneRaw = str(formData.get('zone_id'), 10);

  if (body.length < 5) return { ok: false, message: 'اكتب طلبك بتفصيل شوية (٥ حروف على الأقل).' };
  if (!/^0\d{9,10}$/.test(phone.replace(/\s/g, '')))
    return { ok: false, message: 'اكتب رقم موبايل صح، يبدأ بصفر.' };

  const kind = SERVICE_KINDS.includes(kindRaw as ServiceKind) ? kindRaw : null;
  const zoneId = /^\d+$/.test(zoneRaw) ? Number(zoneRaw) : null;

  const row = await q1<{ id: string }>(
    `insert into requests (zone_id, kind, body, contact_phone)
     values ($1, $2::service_kind, $3, $4) returning id`,
    [zoneId, kind, body, phone.replace(/\s/g, '')]
  );

  // التريجر بيعمل خيط المحادثة تلقائيًا؛ نرجّع لينكه لصاحب الطلب
  const thread = row
    ? await q1<{ owner_token: string }>(
        `select owner_token from threads where request_id = $1`, [row.id]
      )
    : null;

  revalidatePath('/requests');
  return { ok: true, threadToken: thread?.owner_token };
}

/* ---------------- تقييم وبلاغ ---------------- */

export async function rateTarget(formData: FormData): Promise<Result> {
  const providerId = str(formData.get('provider_id'), 40);
  const placeId = str(formData.get('place_id'), 40);
  const stars = Number(str(formData.get('stars'), 2));
  const comment = str(formData.get('comment'), 300);
  const author = str(formData.get('author_name'), 40);

  if (!providerId && !placeId) return { ok: false, message: 'مفيش حاجة نقيّمها.' };
  if (!Number.isInteger(stars) || stars < 1 || stars > 5)
    return { ok: false, message: 'اختار من ١ لـ ٥ نجوم.' };

  await q(
    `insert into ratings (provider_id, place_id, stars, comment, author_name)
     values (nullif($1,'')::uuid, nullif($2,'')::uuid, $3, nullif($4,''), nullif($5,''))`,
    [providerId, placeId, stars, comment, author]
  );
  revalidatePath('/');
  revalidatePath('/places');
  if (providerId) revalidatePath(`/p/${providerId}`);
  if (placeId) revalidatePath(`/m/${placeId}`);
  return { ok: true };
}

export async function hideRating(id: string, hidden: boolean) {
  await requireAdmin();
  await q(`update ratings set is_hidden = $2 where id = $1`, [id, hidden]);
  revalidatePath('/admin');
}

export async function reportProvider(formData: FormData): Promise<Result> {
  const id = str(formData.get('provider_id'), 40);
  const reason = str(formData.get('reason'), 20);
  const details = str(formData.get('details'), 500);
  const phone = str(formData.get('reporter_phone'), 20);
  if (!REASONS.includes(reason)) return { ok: false, message: 'اختار سبب البلاغ.' };

  await q(
    `insert into reports (provider_id, reason, details, reporter_phone)
     values ($1, $2::report_reason, nullif($3,''), nullif($4,''))`,
    [id, reason, details, phone]
  );
  return { ok: true };
}

/* ---------------- الدخول ---------------- */

export async function login(formData: FormData): Promise<Result> {
  const password = str(formData.get('password'), 200);
  if (!checkPassword(password)) return { ok: false, message: 'كلمة السر غلط.' };
  await createSession();
  redirect('/admin');
}

export async function logout(): Promise<void> {
  await destroySession();
  redirect('/admin/login');
}

/* ---------------- الإدارة ---------------- */

export async function addProvider(formData: FormData): Promise<Result> {
  await requireAdmin();
  const name = str(formData.get('display_name'), 80);
  const phone = str(formData.get('phone'), 20).replace(/\s/g, '');
  const whatsapp = str(formData.get('whatsapp'), 20).replace(/\s/g, '');
  const zoneId = Number(str(formData.get('zone_id'), 10));
  const vehicle = str(formData.get('vehicle_note'), 60);
  const note = str(formData.get('note'), 120);
  const services = formData.getAll('services')
    .map((s) => String(s))
    .filter((s): s is ServiceKind => SERVICE_KINDS.includes(s as ServiceKind));

  if (name.length < 2) return { ok: false, message: 'اكتب اسم السائق.' };
  if (!/^0\d{9,10}$/.test(phone)) return { ok: false, message: 'رقم الموبايل مش صح.' };
  if (!Number.isInteger(zoneId)) return { ok: false, message: 'اختار القرية.' };
  if (services.length === 0) return { ok: false, message: 'اختار خدمة واحدة على الأقل.' };

  const photo = formData.get('photo');
  let photoId: string | null = null;
  if (photo instanceof File) {
    const res = await storeImage(photo);
    if (res && !res.ok) return { ok: false, message: res.message };
    photoId = res?.ok ? res.id : null;
  }

  const row = await q1<{ id: string }>(
    `insert into providers (display_name, phone, whatsapp, zone_id, services, vehicle_note, note, photo_id)
     values ($1,$2,nullif($3,''),$4,$5::service_kind[],nullif($6,''),nullif($7,''),$8)
     returning id`,
    [name, phone, whatsapp, zoneId, services, vehicle, note, photoId]
  );
  if (row) await q(`insert into provider_tokens (provider_id) values ($1)`, [row.id]);

  revalidatePath('/admin');
  revalidatePath('/');
  return { ok: true };
}

export async function setVerified(id: string, verified: boolean) {
  await requireAdmin();
  await q(
    `update providers set is_verified = $2, verified_at = case when $2 then current_date else null end
      where id = $1`,
    [id, verified]
  );
  revalidatePath('/admin');
  revalidatePath('/');
}

/** السائق الموقوف بيختفي من الدليل فورًا — مش بعد مراجعة. */
export async function setActive(id: string, active: boolean) {
  await requireAdmin();
  await q(
    `update providers set is_active = $2, is_available = case when $2 then is_available else false end
      where id = $1`,
    [id, active]
  );
  revalidatePath('/admin');
  revalidatePath('/');
}

export async function hideRequest(id: string, hidden: boolean) {
  await requireAdmin();
  await q(`update requests set is_hidden = $2 where id = $1`, [id, hidden]);
  revalidatePath('/admin');
  revalidatePath('/requests');
}

export async function handleReport(id: string) {
  await requireAdmin();
  await q(`update reports set handled_at = now() where id = $1`, [id]);
  revalidatePath('/admin');
}

/* ---------------- الإدارة: المحلات ---------------- */

export async function addPlace(formData: FormData): Promise<Result> {
  await requireAdmin();
  const name = str(formData.get('name_ar'), 80);
  const category = str(formData.get('category'), 20);
  const zoneId = Number(str(formData.get('zone_id'), 10));
  const phone = str(formData.get('phone'), 20).replace(/\s/g, '');
  const whatsapp = str(formData.get('whatsapp'), 20).replace(/\s/g, '');
  const address = str(formData.get('address_note'), 120);
  const hours = str(formData.get('hours_note'), 60);
  const note = str(formData.get('note'), 120);

  if (name.length < 2) return { ok: false, message: 'اكتب اسم المحل.' };
  if (!PLACE_CATEGORIES.includes(category)) return { ok: false, message: 'اختار التصنيف.' };
  if (!Number.isInteger(zoneId)) return { ok: false, message: 'اختار القرية.' };

  const phoneOk = /^0\d{8,10}$/.test(phone);
  const waOk = /^0\d{9,10}$/.test(whatsapp);
  if (!phoneOk && !waOk)
    return { ok: false, message: 'لازم رقم تليفون أو واتساب صح على الأقل.' };
  if (phone && !phoneOk) return { ok: false, message: 'رقم التليفون مش صح.' };
  if (whatsapp && !waOk) return { ok: false, message: 'رقم الواتساب مش صح.' };

  const photo = formData.get('photo');
  let photoId: string | null = null;
  if (photo instanceof File) {
    const res = await storeImage(photo);
    if (res && !res.ok) return { ok: false, message: res.message };
    photoId = res?.ok ? res.id : null;
  }

  await q(
    `insert into places (name_ar, category, zone_id, phone, whatsapp, address_note, hours_note, note, photo_id)
     values ($1, $2::place_category, $3, nullif($4,''), nullif($5,''),
             nullif($6,''), nullif($7,''), nullif($8,''), $9)`,
    [name, category, zoneId, phone, whatsapp, address, hours, note, photoId]
  );

  revalidatePath('/admin');
  revalidatePath('/places');
  return { ok: true };
}

export async function setPlaceActive(id: string, active: boolean) {
  await requireAdmin();
  await q(`update places set is_active = $2 where id = $1`, [id, active]);
  revalidatePath('/admin');
  revalidatePath('/places');
}

/* ---------------- تسجيل ذاتي: سائق أو محل ---------------- */

/**
 * أي حد يقدر يسجّل نفسه، بس الطلب بيروح للإدارة الأول.
 * مفيش حاجة بتظهر في الدليل قبل موافقة إنسان — ده اللي بيحافظ
 * على معنى «موثّق» وعلى ثقة الناس في القايمة.
 */
export async function submitApplication(formData: FormData): Promise<Result> {
  const kind = str(formData.get('kind'), 10);
  if (kind !== 'driver' && kind !== 'place')
    return { ok: false, message: 'نوع الطلب مش واضح.' };

  const name = str(formData.get('name'), 80);
  const phone = str(formData.get('phone'), 20).replace(/\s/g, '');
  const whatsapp = str(formData.get('whatsapp'), 20).replace(/\s/g, '');
  const zoneId = Number(str(formData.get('zone_id'), 10));
  const note = str(formData.get('note'), 300);
  const consent = formData.get('consent');

  if (name.length < 2) return { ok: false, message: 'اكتب الاسم.' };
  if (!/^0\d{8,10}$/.test(phone)) return { ok: false, message: 'رقم التليفون مش صح.' };
  if (whatsapp && !/^0\d{9,10}$/.test(whatsapp))
    return { ok: false, message: 'رقم الواتساب مش صح.' };
  if (!Number.isInteger(zoneId)) return { ok: false, message: 'اختار القرية.' };
  if (!consent)
    return { ok: false, message: 'لازم توافق على نشر اسمك ورقمك في الدليل.' };

  let services: string[] = [];
  let vehicle = '';
  let category: string | null = null;
  let address = '';
  let hours = '';

  if (kind === 'driver') {
    services = formData.getAll('services').map(String)
      .filter((v) => SERVICE_KINDS.includes(v as ServiceKind));
    if (services.length === 0) return { ok: false, message: 'اختار نوع الخدمة اللي هتشتغلها.' };
    vehicle = str(formData.get('vehicle_note'), 60);
  } else {
    category = str(formData.get('category'), 20);
    if (!PLACE_CATEGORIES.includes(category)) return { ok: false, message: 'اختار تصنيف المحل.' };
    address = str(formData.get('address_note'), 120);
    hours = str(formData.get('hours_note'), 60);
  }

  const photo = formData.get('photo');
  let photoId: string | null = null;
  if (photo instanceof File) {
    const res = await storeImage(photo);
    if (res && !res.ok) return { ok: false, message: res.message };
    photoId = res?.ok ? res.id : null;
  }

  // مانع سبام بسيط: نفس الرقم مايبعتش طلب معلّق تاني
  const dup = await q1<{ id: string }>(
    `select id from applications where phone = $1 and status = 'pending' limit 1`,
    [phone]
  );
  if (dup) return { ok: false, message: 'طلبك وصلنا خلاص وبنراجعه. هنكلمك قريب.' };

  await q(
    `insert into applications
       (kind, name, phone, whatsapp, zone_id, note, photo_id,
        services, vehicle_note, category, address_note, hours_note)
     values ($1::application_kind, $2, $3, nullif($4,''), $5, nullif($6,''), $7,
             $8::service_kind[], nullif($9,''), $10::place_category, nullif($11,''), nullif($12,''))`,
    [kind, name, phone, whatsapp, zoneId, note, photoId,
     kind === 'driver' ? services : null, vehicle,
     category, address, hours]
  );

  revalidatePath('/admin');
  return { ok: true };
}

/* ---------------- الإدارة: مراجعة الطلبات ---------------- */

export async function approveApplication(id: string): Promise<Result> {
  await requireAdmin();

  const a = await q1<{
    kind: 'driver' | 'place'; name: string; phone: string; whatsapp: string | null;
    zone_id: number; note: string | null; photo_id: string | null;
    services: string[] | null; vehicle_note: string | null;
    category: string | null; address_note: string | null; hours_note: string | null;
  }>(
    `select kind::text as kind, name, phone, whatsapp, zone_id, note, photo_id,
            services::text[] as services, vehicle_note,
            category::text as category, address_note, hours_note
       from applications where id = $1 and status = 'pending'`,
    [id]
  );
  if (!a) return { ok: false, message: 'الطلب مش موجود أو اتراجع خلاص.' };

  if (a.kind === 'driver') {
    const row = await q1<{ id: string }>(
      `insert into providers
         (display_name, phone, whatsapp, zone_id, services, vehicle_note, note, photo_id)
       values ($1,$2,$3,$4,$5::service_kind[],$6,$7,$8) returning id`,
      [a.name, a.phone, a.whatsapp, a.zone_id, a.services ?? [], a.vehicle_note, a.note, a.photo_id]
    );
    if (row) await q(`insert into provider_tokens (provider_id) values ($1)`, [row.id]);
  } else {
    await q(
      `insert into places
         (name_ar, category, zone_id, phone, whatsapp, address_note, hours_note, note, photo_id)
       values ($1,$2::place_category,$3,$4,$5,$6,$7,$8,$9)`,
      [a.name, a.category, a.zone_id, a.phone, a.whatsapp,
       a.address_note, a.hours_note, a.note, a.photo_id]
    );
  }

  await q(`update applications set status='approved', reviewed_at=now() where id=$1`, [id]);
  revalidatePath('/admin');
  revalidatePath('/');
  revalidatePath('/places');
  return { ok: true };
}

export async function rejectApplication(id: string, reason: string): Promise<Result> {
  await requireAdmin();
  await q(
    `update applications set status='rejected', reviewed_at=now(), reject_reason=nullif($2,'')
      where id = $1 and status = 'pending'`,
    [id, reason.slice(0, 200)]
  );
  revalidatePath('/admin');
  return { ok: true };
}

/* ---------------- المحادثة على الطلب ---------------- */

/**
 * مفيش حسابات هنا كمان: صاحب الطلب بيكتب بلينكه السري، والسائق
 * بلينكه هو. نفس فكرة زرار التوفر — أي حد معاه اللينك هو صاحبه.
 */
export async function sendMessage(formData: FormData): Promise<Result> {
  const body = str(formData.get('body'), 1000);
  const ownerToken = str(formData.get('owner_token'), 64);
  const providerToken = str(formData.get('provider_token'), 64);
  const requestId = str(formData.get('request_id'), 40);

  if (body.length < 1) return { ok: false, message: 'اكتب رسالة الأول.' };

  if (ownerToken) {
    const t = await q1<{ id: string; closed_at: string | null }>(
      `select t.id, t.closed_at from threads t
         join requests r on r.id = t.request_id
        where t.owner_token = $1 and r.expires_at > now()`,
      [ownerToken]
    );
    if (!t) return { ok: false, message: 'المحادثة مش موجودة أو الطلب انتهى.' };
    if (t.closed_at) return { ok: false, message: 'المحادثة اتقفلت.' };
    await q(`insert into messages (thread_id, from_owner, body) values ($1, true, $2)`, [t.id, body]);
    revalidatePath(`/t/${ownerToken}`);
    return { ok: true };
  }

  if (providerToken && requestId) {
    const t = await q1<{ thread_id: string; provider_id: string; closed_at: string | null }>(
      `select t.id as thread_id, p.id as provider_id, t.closed_at
         from threads t
         join requests r on r.id = t.request_id and r.expires_at > now()
         join provider_tokens pt on pt.token = $2
         join providers p on p.id = pt.provider_id and p.is_active
        where t.request_id = $1`,
      [requestId, providerToken]
    );
    if (!t) return { ok: false, message: 'مقدرناش نفتح المحادثة. اتأكد من اللينك.' };
    if (t.closed_at) return { ok: false, message: 'المحادثة اتقفلت.' };
    await q(
      `insert into messages (thread_id, from_owner, provider_id, body) values ($1, false, $2, $3)`,
      [t.thread_id, t.provider_id, body]
    );
    revalidatePath(`/t/p/${providerToken}/${requestId}`);
    return { ok: true };
  }

  return { ok: false, message: 'مقدرناش نحدد مين بيبعت.' };
}

export async function hideMessage(id: string, hidden: boolean) {
  await requireAdmin();
  await q(`update messages set is_hidden = $2 where id = $1`, [id, hidden]);
  revalidatePath('/admin');
}
