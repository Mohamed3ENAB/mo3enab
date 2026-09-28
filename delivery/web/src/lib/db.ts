import { Pool } from 'pg';

/**
 * المتصفح مبيكلمش قاعدة البيانات مباشرة — كل القراءة والكتابة بتحصل هنا
 * في السيرفر. ده مكان التحكم في الصلاحيات، مش الواجهة.
 */
const url = process.env.DATABASE_URL;
if (!url) throw new Error('DATABASE_URL مش متظبط. انسخ .env.example لـ .env وظبطه.');

// المزودين المُدارين (Supabase وغيره) بيطلبوا SSL.
const needsSsl = /supabase|sslmode=require|neon\.tech/i.test(url);

declare global {
  // eslint-disable-next-line no-var
  var __sekkaPool: Pool | undefined;
}

export const pool =
  global.__sekkaPool ??
  new Pool({
    connectionString: url,
    ssl: needsSsl ? { rejectUnauthorized: false } : undefined,
    max: 5,
  });

if (process.env.NODE_ENV !== 'production') global.__sekkaPool = pool;

export async function q<T>(text: string, params: unknown[] = []): Promise<T[]> {
  const res = await pool.query(text, params);
  return res.rows as T[];
}

export async function q1<T>(text: string, params: unknown[] = []): Promise<T | null> {
  const rows = await q<T>(text, params);
  return rows[0] ?? null;
}
