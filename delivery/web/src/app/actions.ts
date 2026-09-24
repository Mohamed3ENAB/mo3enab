'use server';

import { revalidatePath } from 'next/cache';
import { redirect } from 'next/navigation';
import { q, q1 } from '@/lib/db';
import { checkPassword, createSession, destroySession, isLoggedIn } from '@/lib/auth';
import type { ServiceKind } from '@/lib/types';

const SERVICE_KINDS: ServiceKind[] = [
  'delivery', 'tuktuk', 'goods', 'bicycle', 'mahalla_run',
];
const REASONS = ['rude', 'overcharge', 'no_show', 'unsafe', 'wrong_number', 'other'];

type Result = { ok: boolean; message?: string };

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

  await q(
    `insert into requests (zone_id, kind, body, contact_phone)
     values ($1, $2::service_kind, $3, $4)`,
    [zoneId, kind, body, phone.replace(/\s/g, '')]
  );

  revalidatePath('/requests');
  return { ok: true };
}

/* ---------------- تقييم وبلاغ ---------------- */

export async function rateProvider(formData: FormData): Promise<Result> {
  const id = str(formData.get('provider_id'), 40);
  const stars = Number(str(formData.get('stars'), 2));
  const comment = str(formData.get('comment'), 300);
  if (!Number.isInteger(stars) || stars < 1 || stars > 5)
    return { ok: false, message: 'اختار من ١ لـ ٥ نجوم.' };

  await q(`insert into ratings (provider_id, stars, comment) values ($1, $2, nullif($3,''))`, [
    id, stars, comment,
  ]);
  revalidatePath('/');
  revalidatePath(`/p/${id}`);
  return { ok: true };
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

  const row = await q1<{ id: string }>(
    `insert into providers (display_name, phone, whatsapp, zone_id, services, vehicle_note, note)
     values ($1,$2,nullif($3,''),$4,$5::service_kind[],nullif($6,''),nullif($7,''))
     returning id`,
    [name, phone, whatsapp, zoneId, services, vehicle, note]
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
