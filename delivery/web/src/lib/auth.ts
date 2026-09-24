import { createHmac, timingSafeEqual, randomBytes } from 'node:crypto';
import { cookies } from 'next/headers';

const COOKIE = 'sekka_admin';
const MAX_AGE = 60 * 60 * 12; // ١٢ ساعة

function secret() {
  const s = process.env.SESSION_SECRET;
  if (!s || s.length < 16) throw new Error('SESSION_SECRET مش متظبط أو قصير.');
  return s;
}

function sign(payload: string) {
  return createHmac('sha256', secret()).update(payload).digest('hex');
}

function safeEqual(a: string, b: string) {
  const ba = Buffer.from(a);
  const bb = Buffer.from(b);
  if (ba.length !== bb.length) return false;
  return timingSafeEqual(ba, bb);
}

/** كلمة السر بتتقارن بوقت ثابت عشان ماتتخمنش بالتوقيت. */
export function checkPassword(input: string) {
  const expected = process.env.ADMIN_PASSWORD;
  if (!expected || expected.length < 4) return false;
  return safeEqual(input, expected);
}

export async function createSession() {
  const exp = Date.now() + MAX_AGE * 1000;
  const payload = `${exp}.${randomBytes(8).toString('hex')}`;
  const value = `${payload}.${sign(payload)}`;
  const jar = await cookies();
  jar.set(COOKIE, value, {
    httpOnly: true,
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production',
    path: '/',
    maxAge: MAX_AGE,
  });
}

export async function destroySession() {
  const jar = await cookies();
  jar.delete(COOKIE);
}

export async function isLoggedIn(): Promise<boolean> {
  const jar = await cookies();
  const value = jar.get(COOKIE)?.value;
  if (!value) return false;
  const i = value.lastIndexOf('.');
  if (i < 0) return false;
  const payload = value.slice(0, i);
  const mac = value.slice(i + 1);
  if (!safeEqual(mac, sign(payload))) return false;
  const exp = Number(payload.split('.')[0]);
  return Number.isFinite(exp) && exp > Date.now();
}
