/**
 * بيجهّز قاعدة البيانات من الصفر: الجداول + القرى والأسعار.
 * التشغيل: npm run db:setup
 * بيشتغل على أي PostgreSQL، وعلى Supabase كمان.
 */
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import pg from 'pg';

const here = dirname(fileURLToPath(import.meta.url));
const db = join(here, '..', '..', 'db');

const url = process.env.DATABASE_URL;
if (!url) {
  console.error('DATABASE_URL مش متظبط. انسخ .env.example لـ .env وحط الرابط فيه.');
  process.exit(1);
}

const client = new pg.Client({
  connectionString: url,
  ssl: /supabase|sslmode=require|neon\.tech/i.test(url) ? { rejectUnauthorized: false } : undefined,
});

await client.connect();
for (const file of ['schema.sql', 'seed.sql']) {
  process.stdout.write(`${file} … `);
  await client.query(readFileSync(join(db, file), 'utf8'));
  console.log('تمام');
}
await client.end();
console.log('\nقاعدة البيانات جاهزة. شغّل: npm run dev');
