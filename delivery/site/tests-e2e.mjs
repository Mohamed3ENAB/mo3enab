/**
 * اختبار الرحلات على متصفح حقيقي — ٢٨ اختبار.
 *
 * التشغيل:
 *   npx playwright install chromium        (أول مرة بس)
 *   php -S 127.0.0.1:8090 router.php &
 *   node tests-e2e.mjs <DRIVER_TOKEN>
 *
 * محتاج قاعدة بيانات فيها البيانات التجريبية (install.php مع الخيار مفعّل).
 */
import { chromium } from 'playwright';
import { writeFileSync, existsSync } from 'node:fs';
import zlib from 'node:zlib';
const BASE = process.env.BASE_URL || 'http://127.0.0.1:8090';
const TOK = process.argv[2];
let pass = 0, fail = 0;
const ok = m => { pass++; console.log('✅ ' + m); };
const bad = m => { fail++; console.log('❌ ' + m); };

const IMG = '/tmp/sekka-php.png';
if (!existsSync(IMG)) {
  const w = 500, h = 500, rows = [];
  for (let y = 0; y < h; y++) { const r = Buffer.alloc(1 + w * 3);
    for (let x = 0; x < w; x++) { r[1+x*3] = (x*255/w)|0; r[2+x*3] = (y*255/h)|0; r[3+x*3] = 150; }
    rows.push(r); }
  const ch = (t, d) => { const l = Buffer.alloc(4); l.writeUInt32BE(d.length);
    const b = Buffer.concat([Buffer.from(t), d]); const c = Buffer.alloc(4);
    c.writeUInt32BE(zlib.crc32(b) >>> 0); return Buffer.concat([l, b, c]); };
  const ih = Buffer.alloc(13); ih.writeUInt32BE(w,0); ih.writeUInt32BE(h,4); ih[8]=8; ih[9]=2;
  writeFileSync(IMG, Buffer.concat([Buffer.from([137,80,78,71,13,10,26,10]),
    ch('IHDR', ih), ch('IDAT', zlib.deflateSync(Buffer.concat(rows))), ch('IEND', Buffer.alloc(0))]));
}

const b = await chromium.launch();
const ctx = await b.newContext({ viewport: { width: 390, height: 844 }, locale: 'ar-EG' });
const p = await ctx.newPage();
const errs = [];
p.on('pageerror', e => errs.push(String(e).slice(0, 150)));
const go = async (u) => { await p.goto(BASE + u, { waitUntil: 'domcontentloaded' }); await p.waitForTimeout(500); };

// ١) الدليل بدون تسجيل
await go('/');
const live = await p.locator('.row.live').count();
live === 3 ? ok(`الدليل شغال بدون تسجيل (${live} متاحين)`) : bad(`متوقع 3، لقينا ${live}`);

// ٢) زر الاتصال
const tel = await p.locator('.row.live .btn.call').first().getAttribute('href');
/^tel:\d+/.test(tel ?? '') ? ok(`زر الاتصال شغال (${tel})`) : bad(`href غلط: ${tel}`);

// ٣) الفلتر بالمتصفح
const before = await p.locator('.row:visible').count();
await p.locator('.svc[data-set="kind:tuktuk"]').click(); await p.waitForTimeout(400);
const after = await p.locator('.row:visible').count();
after < before ? ok(`الفلتر بيصفّي (${before} ← ${after})`) : bad('الفلتر مبيعملش حاجة');

// ٤) البحث
await go('/');
await p.fill('#q', 'أحمد'); await p.waitForTimeout(400);
const found = await p.locator('.row:visible').count();
found === 1 ? ok('البحث بالاسم شغال') : bad(`متوقع 1، لقينا ${found}`);

// ٥) السائق يقفل توفره
await go('/d/' + TOK);
await p.locator('button.toggle').click(); await p.waitForTimeout(900);
(await p.locator('button.toggle').innerText()).includes('مش متاح')
  ? ok('السائق قفل توفره') : bad('التبديل مشتغلش');
await go('/');
const live2 = await p.locator('.row.live').count();
live2 === live - 1 ? ok(`اختفى من المتاحين فورًا (${live} ← ${live2})`) : bad(`متوقع ${live-1}, لقينا ${live2}`);
await go('/d/' + TOK); await p.locator('button.toggle').click(); await p.waitForTimeout(900);

// ٦) ورقة الطلب من محل
await go('/places');
await p.locator('button[data-sheet]').first().click(); await p.waitForTimeout(500);
const steps = await p.locator('.scrim:visible .step').count();
steps === 2 ? ok('ورقة الطلب فيها الخطوتين') : bad(`متوقع خطوتين، لقينا ${steps}`);
const waHref = await p.locator('.scrim:visible .mini a[href*="wa.me"]').first().getAttribute('href');
decodeURIComponent(waHref ?? '').includes('ممكن تستلملي طلب من')
  ? ok('رسالة الاستلام بتتكتب لوحدها') : bad('رسالة الواتساب مش مظبوطة');

// ٧) طلب برقم مخفي
await go('/requests');
await p.fill('#body', 'محتاج حد يجيبلي دوا من الصيدلية');
await p.fill('#contact_phone', '01277889900');
await p.click('button[type=submit]');
await p.waitForSelector('a[href*="/t/"]', { timeout: 8000 });
ok('الطلب اتنشر وصاحبه خد لينك المحادثة');
const threadHref = await p.locator('a[href*="/t/"]').first().getAttribute('href');

const guest = await (await b.newContext({ viewport: { width: 390, height: 844 } })).newPage();
await guest.goto(BASE + '/requests', { waitUntil: 'domcontentloaded' }); await guest.waitForTimeout(600);
const html = await guest.content();
html.includes('01277889900') ? bad('🔴 الرقم الحقيقي ظاهر في HTML') : ok('الرقم الحقيقي مش موجود في HTML خالص');
html.includes('0127••••00') ? ok('الرقم بيظهر ناقص') : bad('القناع مش ظاهر');

// ٨) المحادثة
await go(threadHref.replace(BASE, ''));
await p.fill('textarea[name=body]', 'لو حد فاضي يكلمني');
await p.click('button[type=submit]'); await p.waitForTimeout(900);
(await p.locator('.bubble-msg').count()) > 0 ? ok('صاحب الطلب بعت رسالة') : bad('الرسالة مبعتتش');

await go('/d/' + TOK);
const reply = await p.locator('a[href*="/t/p/"]').first().getAttribute('href');
await go(reply.replace(BASE, ''));
const seen = await p.locator('.bubble-msg').count();
seen > 0 ? ok(`السائق شايف رسالة صاحب الطلب (${seen})`) : bad('السائق مش شايف الرسايل');
await p.fill('textarea[name=body]', 'أنا جاي خلال ربع ساعة');
await p.click('button[type=submit]'); await p.waitForTimeout(900);
(await p.locator('.bubble-msg').count()) > seen ? ok('السائق رد') : bad('رد السائق مظهرش');

// ٩) التسجيل الذاتي بصورة
await go('/join/apply?kind=driver');
await p.setInputFiles('input[type=file]', IMG);
await p.fill('#name', 'طارق المتقدم');
await p.fill('#phone', '01099887766');
await p.selectOption('#zone_id', { index: 1 });
await p.locator('.checks input[value=bicycle]').check();
await p.locator('input[name=consent]').check();
await p.click('button[type=submit]');
await p.waitForSelector('text=طلبك وصلنا', { timeout: 8000 });
ok('السائق سجّل نفسه ورفع صورة');

await go('/');
(await p.content()).includes('طارق المتقدم')
  ? bad('🔴 ظهر في الدليل قبل الموافقة') : ok('الطلب مستني الموافقة ومش ظاهر في الدليل');

// ١٠) الإدارة
await go('/admin/login');
await p.fill('#password', 'wrong'); await p.click('button[type=submit]'); await p.waitForTimeout(700);
(await p.locator('.msg.bad').count()) > 0 ? ok('كلمة السر الغلط اترفضت') : bad('كلمة السر الغلط عدّت');
await p.fill('#password', process.env.ADMIN_PASSWORD || 'test-admin-pass'); await p.click('button[type=submit]'); await p.waitForTimeout(1100);
p.url().includes('/admin') && !p.url().includes('login') ? ok('الإدارة دخلت') : bad(`مدخلتش: ${p.url()}`);
(await p.locator('.stat').first().innerText()).includes('1') ? ok('اللوحة بتعد الطلبات المستنية') : bad('العداد غلط');

await p.locator('button:has-text("اقبل وفعّل")').first().click(); await p.waitForTimeout(1200);
await go('/');
(await p.content()).includes('طارق المتقدم') ? ok('المتقدم ظهر بعد الموافقة') : bad('مظهرش بعد الموافقة');

const src = await p.locator('.avatar.photo img').first().getAttribute('src');
if (src) {
  const r = await p.request.get(BASE + '/' + src.replace(/^\//, ''));
  const len = Number(r.headers()['content-length'] ?? 0);
  r.ok() && len > 0 && len < 40000 ? ok(`الصورة اتصغّرت (${len} بايت)`) : bad(`صورة: ${r.status()} ${len}`);
  (r.headers()['cache-control'] ?? '').includes('immutable') ? ok('الصورة بتتكاش') : bad('مفيش كاش');
} else bad('مفيش صورة في الدليل');

// ١١) التقييم
const pid = await p.locator('a[href*="/p/"]').first().getAttribute('href');
await go(pid.replace(BASE, ''));
await p.selectOption('#stars', '4');
await p.fill('#comment', 'جه بسرعة والمعاملة كويسة');
await p.fill('#author_name', 'أم أحمد');
await p.click('button:has-text("ابعت رأيك")'); await p.waitForTimeout(1000);
const rv = await p.content();
rv.includes('جه بسرعة') && rv.includes('أم أحمد') ? ok('الريفيو اتسجل وبيظهر بالاسم') : bad('الريفيو مظهرش');

// ١٢) الإيقاف
await go('/admin?tab=drivers');
await p.locator('button:has-text("أوقف")').first().click(); await p.waitForTimeout(1000);
await guest.goto(BASE + '/', { waitUntil: 'domcontentloaded' }); await guest.waitForTimeout(600);
const rowsAfter = await guest.locator('.row').count();
rowsAfter === 5 ? ok(`الموقوف اختفى فورًا (6 ← ${rowsAfter})`) : bad(`متوقع 5 صفوف، لقينا ${rowsAfter}`);

// ١٣) توكنات السواقين مش ظاهرة للزائر
const gh = await guest.content();
gh.includes(TOK) ? bad('🔴 لينك سائق ظاهر للزائر') : ok('لينكات السواقين مش ظاهرة للزائر');

// ١٤) PWA
const mf = await p.request.get(BASE + '/manifest.webmanifest');
const mj = await mf.json();
mj.icons?.length >= 8 && mj.display === 'standalone' && mj.icons.some(i => i.purpose === 'maskable')
  ? ok(`المانيفست سليم (${mj.icons.length} أيقونة + maskable)`) : bad('المانيفست ناقص');
await go('/');
const head = await p.content();
head.includes('apple-touch-icon') && head.includes('favicon.ico') && head.includes('theme-color')
  ? ok('favicon + أيقونة الأيفون + theme-color موجودين') : bad('حاجة ناقصة في الـhead');

errs.length === 0 ? ok('مفيش أخطاء جافاسكربت') : bad('أخطاء JS: ' + errs.join(' | '));
await b.close();
console.log(`\n${pass} عدّت · ${fail} فشلت`);
process.exit(fail > 0 ? 1 : 0);
