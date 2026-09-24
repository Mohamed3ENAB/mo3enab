/**
 * بيانات تجريبية للتطوير والاختبار — مش للإنتاج.
 * التشغيل: npm run db:seed-demo
 */
import pg from 'pg';

const url = process.env.DATABASE_URL;
if (!url) { console.error('DATABASE_URL مش متظبط. شوف .env'); process.exit(1); }
if (process.env.NODE_ENV === 'production') {
  console.error('متشغلهوش على الإنتاج.'); process.exit(1);
}

const c = new pg.Client({
  connectionString: url,
  ssl: /supabase|sslmode=require|neon\.tech/i.test(url) ? { rejectUnauthorized: false } : undefined,
});
await c.connect();

const demo = [
  ['محمود السيد',  '01021110001', '01021110001', 'القيصرية',     ['delivery','goods'],       'موتوسيكل',     'بيشتغل من 12 لـ 10',  true,  true,  12],
  ['كريم أبو زيد', '01021110002', null,          'القيصرية',     ['tuktuk','goods'],         'توك توك',      null,                   true,  true,  40],
  ['أحمد فرغلي',   '01021110003', '01021110003', 'القيصرية',     ['delivery','mahalla_run'], 'موتوسيكل',     'بيروح المحلة يوميًا', true,  true,  95],
  ['يوسف عبد الله','01021110004', null,          'القيصرية',     ['bicycle','delivery'],     'عجلة',         'الطلبات القريبة بس',   false, false, 300],
  ['سيد الشناوي',  '01021110005', '01021110005', 'بطينة',        ['delivery'],               'موتوسيكل',     null,                   true,  false, 600],
  ['عماد رزق',     '01021110006', null,          'محلة أبو علي', ['tuktuk'],                 'توك توك أحمر', null,                   false, false, 1500],
];

for (const [name, phone, wa, zone, svc, veh, note, ver, av, mins] of demo) {
  const r = await c.query(
    `insert into providers
       (display_name, phone, whatsapp, zone_id, services, vehicle_note, note,
        is_verified, verified_at, is_available, availability_updated_at)
     select $1, $2, $3, z.id, $4::service_kind[], $5, $6, $7,
            case when $7 then current_date end, $8, now() - ($9 || ' minutes')::interval
       from service_zones z where z.name_ar = $10
     returning id`,
    [name, phone, wa, svc, veh, note, ver, av, String(mins), zone]
  );
  if (r.rows[0]) {
    const t = await c.query(
      `insert into provider_tokens (provider_id) values ($1) returning token`, [r.rows[0].id]
    );
    console.log(`${name.padEnd(16)} /d/${t.rows[0].token}`);
    // تقييمات تجريبية عشان تشوف الشارة في الكارت
    const stars = { 'محمود السيد': [5, 4, 5], 'أحمد فرغلي': [5, 5], 'كريم أبو زيد': [4, 5, 4, 5] }[name];
    for (const n of stars ?? []) {
      await c.query('insert into ratings (provider_id, stars) values ($1, $2)', [r.rows[0].id, n]);
    }
  }
}
await c.end();
console.log('\nالبيانات التجريبية جاهزة. اللينكات فوق للاختبار.');
