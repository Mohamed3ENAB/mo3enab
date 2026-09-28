/**
 * اختبار الرحلات الحقيقية على متصفح فعلي.
 *
 * التشغيل:
 *   npx playwright install chromium      (أول مرة بس)
 *   npm run build && npm start &
 *   node tests/e2e.mjs <BASE_URL> <DRIVER_TOKEN>
 *
 * محتاج قاعدة بيانات فيها بيانات الاختبار: npm run db:seed-demo
 */
import { chromium } from 'playwright';
import { writeFileSync, existsSync } from 'node:fs';
import zlib from 'node:zlib';

const BASE = process.argv[2] || 'http://127.0.0.1:3000';
const TOK = process.argv[3];
if (!TOK) {
  console.error('محتاج لينك سائق: node tests/e2e.mjs <BASE_URL> <DRIVER_TOKEN>');
  process.exit(1);
}
const ADMIN = process.env.ADMIN_PASSWORD || 'test-admin-pass';
// صورة اختبار بتتعمل وقت التشغيل — مش متخزنة في الريبو
const TEST_IMAGE = process.env.TEST_IMAGE || '/tmp/sekka-test.png';
let pass = 0, fail = 0;
const ok  = (m) => { pass++; console.log('✅ ' + m); };
const bad = (m) => { fail++; console.log('❌ ' + m); };

// صورة PNG صغيرة للاختبار
function makeTestPng(path) {
  if (existsSync(path)) return;
  const w = 600, h = 600, rows = [];
  for (let y = 0; y < h; y++) {
    const row = Buffer.alloc(1 + w * 3);
    for (let x = 0; x < w; x++) {
      row[1 + x * 3] = (x * 255 / w) | 0;
      row[2 + x * 3] = (y * 255 / h) | 0;
      row[3 + x * 3] = 140;
    }
    rows.push(row);
  }
  const chunk = (type, data) => {
    const len = Buffer.alloc(4); len.writeUInt32BE(data.length);
    const body = Buffer.concat([Buffer.from(type), data]);
    const crc = Buffer.alloc(4); crc.writeUInt32BE(zlib.crc32(body) >>> 0);
    return Buffer.concat([len, body, crc]);
  };
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(w, 0); ihdr.writeUInt32BE(h, 4);
  ihdr[8] = 8; ihdr[9] = 2;
  writeFileSync(path, Buffer.concat([
    Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
    chunk('IHDR', ihdr),
    chunk('IDAT', zlib.deflateSync(Buffer.concat(rows))),
    chunk('IEND', Buffer.alloc(0)),
  ]));
}
makeTestPng(TEST_IMAGE);

const b = await chromium.launch();
const settle = (pg, ms = 700) => pg.waitForTimeout(ms);
const ctx = await b.newContext({ viewport: { width: 390, height: 844 }, locale: 'ar-EG' });
const p = await ctx.newPage();

// ١) الزائر يشوف الدليل من غير تسجيل
await p.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await settle(p);
const liveBefore = await p.locator('.row.live').count();
liveBefore === 3 ? ok(`الزائر شاف الدليل بدون تسجيل (${liveBefore} متاحين)`) : bad(`متوقع 3 متاحين، لقينا ${liveBefore}`);

// ٢) زر الاتصال فيه tel: حقيقي
const tel = await p.locator('.row.live .btn.call').first().getAttribute('href');
/^tel:\d+/.test(tel ?? '') ? ok(`زر الاتصال بيفتح الاتصال (${tel})`) : bad(`href غلط: ${tel}`);

// ٣) السائق يقفل توفره → يختفي من المتاحين
await p.goto(`${BASE}/d/${TOK}`, { waitUntil: 'domcontentloaded' });
await p.locator('button.toggle').click();
await p.waitForTimeout(1200);
const offText = await p.locator('button.toggle').innerText();
offText.includes('مش متاح') ? ok('السائق قفل توفره') : bad(`نص الزرار: ${offText}`);

await p.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await settle(p);
const liveAfter = await p.locator('.row.live').count();
liveAfter === liveBefore - 1 ? ok(`اختفى من المتاحين فورًا (${liveBefore} ← ${liveAfter})`) : bad(`متوقع ${liveBefore-1}، لقينا ${liveAfter}`);

// ٤) يرجّع يفتح → يرجع يظهر
await p.goto(`${BASE}/d/${TOK}`, { waitUntil: 'domcontentloaded' });
await p.locator('button.toggle').click();
await p.waitForTimeout(1200);
await p.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await settle(p);
const liveBack = await p.locator('.row.live').count();
liveBack === liveBefore ? ok('رجع يظهر لما فتح توفره') : bad(`متوقع ${liveBefore}، لقينا ${liveBack}`);

// ٤ب) فلتر الخدمة بيشتغل في المتصفح
await p.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await settle(p);
const allRows = await p.locator('.row').count();
await p.locator('.svc[aria-pressed="false"]').first().click();
await p.waitForTimeout(400);
const filtered = await p.locator('.row').count();
filtered < allRows ? ok(`فلتر الخدمة بيصفّي (${allRows} ← ${filtered})`) : bad('الفلتر مبيعملش حاجة');

// ٤ج) البحث بيشتغل
await p.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await settle(p);
await p.fill('input[type="search"]', 'محمود');
await p.waitForTimeout(400);
const found = await p.locator('.row').count();
found === 1 ? ok('البحث بالاسم بيشتغل') : bad(`متوقع نتيجة واحدة، لقينا ${found}`);

// ٥) لينك غلط
const r = await p.goto(BASE + '/d/not-a-real-token');
r.status() === 404 ? ok('اللينك الغلط بيرجع 404') : bad(`متوقع 404، رجع ${r.status()}`);

// ٦) نشر طلب في اللوحة
await p.goto(BASE + '/requests', { waitUntil: 'domcontentloaded' });
await settle(p);
const reqsBefore = await p.locator('.req').count();
await p.fill('textarea[name="body"]', 'اختبار: محتاج حد يجيبلي كيلو طماطم من السوق');
await p.fill('input[name="contact_phone"]', '01234567890');
await p.click('button[type="submit"]');
await p.waitForTimeout(1500);
await p.goto(BASE + '/requests', { waitUntil: 'domcontentloaded' });
await settle(p);
const reqsAfter = await p.locator('.req').count();
reqsAfter === reqsBefore + 1 ? ok(`الطلب اتنشر (${reqsBefore} ← ${reqsAfter})`) : bad(`متوقع ${reqsBefore+1}، لقينا ${reqsAfter}`);

// ٧) رقم موبايل غلط بيترفض
await p.fill('textarea[name="body"]', 'اختبار رقم غلط لازم يترفض');
await p.fill('input[name="contact_phone"]', '123');
await p.click('button[type="submit"]');
await p.waitForTimeout(1200);
const badMsg = await p.locator('.msg.bad').count();
badMsg > 0 ? ok('الرقم الغلط اترفض برسالة واضحة') : bad('الرقم الغلط عدّى');

// ٨) دليل المحلات بيشتغل من غير تسجيل
await p.goto(BASE + '/places', { waitUntil: 'domcontentloaded' });
await settle(p, 1200);
const placeCount = await p.locator('.row').count();
placeCount > 0 ? ok(`دليل المحلات شغال بدون تسجيل (${placeCount} محل)`) : bad('مفيش محلات ظاهرة');

// ٨ب) فلتر التصنيف
const beforeCat = await p.locator('.row').count();
await p.locator('.svc[aria-pressed="false"]').first().click();
await settle(p, 500);
const afterCat = await p.locator('.row').count();
afterCat < beforeCat ? ok(`فلتر التصنيف بيصفّي (${beforeCat} ← ${afterCat})`) : bad('فلتر التصنيف مبيعملش حاجة');

// ٨ج) ورقة «اطلب من هنا»: خطوتين + سواقين متاحين + رسالة جاهزة
await p.goto(BASE + '/places', { waitUntil: 'domcontentloaded' });
await settle(p, 1400);
await p.locator('button:has-text("اطلب من هنا")').first().click();
await p.waitForSelector('.sheet', { timeout: 6000 });
const steps = await p.locator('.sheet .step').count();
const sheetDrivers = await p.locator('.sheet .mini').count();
steps === 2 ? ok('ورقة الطلب فيها الخطوتين') : bad(`متوقع خطوتين، لقينا ${steps}`);
sheetDrivers > 0 ? ok(`الورقة بتعرض ${sheetDrivers} سواقين متاحين`) : bad('الورقة مش بتعرض سواقين');

const waLink = await p.locator('.sheet .mini a[href*="wa.me"]').first().getAttribute('href');
const decoded = decodeURIComponent(waLink ?? '');
decoded.includes('ممكن تستلملي طلب من')
  ? ok('رسالة الاستلام بتتكتب لوحدها باسم المحل')
  : bad(`رسالة الواتساب مش مظبوطة: ${decoded.slice(0, 80)}`);

// ٨د) سعر البداية للعجلة ظاهر في الأسعار وفي صفحة الانضمام
for (const [path, label] of [['/prices', 'الأسعار'], ['/join', 'اشتغل معانا']]) {
  await p.goto(BASE + path, { waitUntil: 'domcontentloaded' });
  await settle(p, 600);
  const txt = await p.locator('.rates').first().innerText();
  txt.includes('عجلة') && txt.includes('5')
    ? ok(`سعر بداية العجلة ظاهر في ${label}`)
    : bad(`سعر بداية العجلة مش ظاهر في ${label}: ${txt.slice(0, 60)}`);
}

// ٩) /admin يرمي على تسجيل الدخول
await p.goto(BASE + '/admin', { waitUntil: 'domcontentloaded' });
await settle(p);
p.url().includes('/admin/login') ? ok('/admin محمية — بترمي على الدخول') : bad(`مرماش: ${p.url()}`);

// ١٠) كلمة سر غلط
await p.fill('input[name="password"]', 'wrong-password');
await p.click('button[type="submit"]');
await p.waitForTimeout(1200);
const loginErr = await p.locator('.msg.bad').count();
loginErr > 0 && !p.url().endsWith('/admin') ? ok('كلمة السر الغلط اترفضت') : bad('كلمة السر الغلط عدّت');

// ١١) كلمة السر الصح
await p.fill('input[name="password"]', ADMIN);
await p.click('button[type="submit"]');
await p.waitForTimeout(1800);
p.url().endsWith('/admin') ? ok('الإدارة دخلت') : bad(`مدخلتش: ${p.url()}`);

// ١٢) الإيقاف بيخفي السائق فورًا
await p.locator('.tabs button:has-text("السواقين (")').first().click();
await p.waitForTimeout(600);
await p.locator('.admin-row').first().locator('button:has-text("أوقف")').click();
await p.waitForTimeout(1500);
const ctx2 = await b.newContext({ viewport: { width: 390, height: 844 } });
const guest = await ctx2.newPage();
await guest.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await settle(guest);
const afterSuspend = await guest.locator('.row').count();
afterSuspend === 5 ? ok(`السائق الموقوف اختفى من الدليل فورًا (6 ← ${afterSuspend})`) : bad(`متوقع 5 صفوف، لقينا ${afterSuspend}`);

// ١٣) الزائر مش شايف لينكات السواقين في أي صفحة
const html = await guest.content();
html.includes(TOK) ? bad('🔴 لينك سائق ظاهر للزائر') : ok('لينكات السواقين مش ظاهرة للزائر');

// ١٤) إخفاء محل من الإدارة بيشيله من الدليل فورًا
await p.goto(BASE + '/admin', { waitUntil: 'domcontentloaded' });
await settle(p, 800);
await p.locator('.tabs button:has-text("المحلات (")').first().click();
await settle(p, 600);
await p.locator('.admin-row button:has-text("إخفاء")').first().click();
await settle(p, 1400);
await guest.goto(BASE + '/places', { waitUntil: 'domcontentloaded' });
await settle(guest, 1000);
const placesAfterHide = await guest.locator('.row').count();
placesAfterHide === placeCount - 1
  ? ok(`المحل المخفي اختفى فورًا (${placeCount} ← ${placesAfterHide})`)
  : bad(`متوقع ${placeCount - 1} محل، لقينا ${placesAfterHide}`);


/* ===== الصور · التسجيل الذاتي · الريفيوز · المحادثة ===== */

// ١) تسجيل ذاتي لسائق مع صورة
await p.goto(BASE + '/join/apply?kind=driver', { waitUntil: 'domcontentloaded' });
await settle(p, 1500);
await p.setInputFiles('input[type=file]', TEST_IMAGE);
await p.fill('input[name=name]', 'طارق المتقدم');
await p.fill('input[name=phone]', '01099887766');
await p.selectOption('select[name=zone_id]', { index: 1 });
await p.locator('.checks input[value=bicycle]').check();
await p.fill('input[name=vehicle_note]', 'عجلة');
await p.locator('input[name=consent]').check();
await p.click('button[type=submit]');
await p.waitForSelector('text=طلبك وصلنا', { timeout: 10000 });
ok('السائق سجّل نفسه ورفع صورة');

// ٢) الطلب مش بيظهر في الدليل قبل الموافقة
await p.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await settle(p);
const before = await p.locator('.row').count();
(await p.content()).includes('طارق المتقدم')
  ? bad('🔴 المتقدم ظهر في الدليل قبل الموافقة')
  : ok(`الطلب مستني الموافقة ومش ظاهر في الدليل (${before} صف)`);

// ٣) نفس الرقم مايبعتش طلب تاني
await p.goto(BASE + '/join/apply?kind=driver', { waitUntil: 'domcontentloaded' });
await settle(p, 1400);
await p.fill('input[name=name]', 'طارق تاني');
await p.fill('input[name=phone]', '01099887766');
await p.selectOption('select[name=zone_id]', { index: 1 });
await p.locator('.checks input[value=bicycle]').check();
await p.locator('input[name=consent]').check();
await p.click('button[type=submit]');
await settle(p, 1500);
(await p.locator('.msg.bad').count()) > 0
  ? ok('الطلب المكرر بنفس الرقم اترفض')
  : bad('الطلب المكرر عدّى');

// ٤) الإدارة تقبل الطلب (الجلسة مفتوحة من قبل كده في السويت)
await p.goto(BASE + '/admin', { waitUntil: 'domcontentloaded' });
await settle(p, 1200);
if (p.url().includes('/admin/login')) {
  await p.fill('input[name=password]', ADMIN);
  await p.click('button[type=submit]');
  await settle(p, 2000);
}
await p.locator('.tabs button:has-text("طلبات الانضمام")').first().click();
await settle(p, 700);
const statPending = await p.locator('.stat').first().innerText();
statPending.includes('1') ? ok('اللوحة بتعد الطلبات المستنية') : bad(`عداد غلط: ${statPending}`);
await p.locator('button:has-text("اقبل وفعّل")').first().click();
await settle(p, 2200);
ok('الإدارة قبلت الطلب');

// ٥) بقى ظاهر في الدليل + الصورة اتحفظت
await p.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await settle(p, 1200);
const afterApproval = await p.content();
afterApproval.includes('طارق المتقدم')
  ? ok('المتقدم ظهر في الدليل بعد الموافقة')
  : bad('مظهرش بعد الموافقة');
const imgSrc = await p.locator('.avatar.photo img').first().getAttribute('src');
if (imgSrc) {
  const r = await p.request.get(BASE + imgSrc);
  const len = Number(r.headers()['content-length'] ?? 0);
  r.ok() && len > 0 && len < 40000
    ? ok(`الصورة بتتقدّم مصغّرة (${len} بايت من 104233)`)
    : bad(`الصورة: status ${r.status()} size ${len}`);
  r.headers()['cache-control']?.includes('immutable')
    ? ok('الصورة بتتكاش للأبد')
    : bad('مفيش كاش على الصورة');
} else bad('مفيش صورة في الدليل');

// ٦) تقييم محل + الريفيو بيظهر
await p.goto(BASE + '/places', { waitUntil: 'domcontentloaded' });
await settle(p, 1400);
await p.locator('a[href^="/m/"]').first().click();
await p.waitForSelector('text=قيّم', { timeout: 8000 });
await p.selectOption('select[name=stars]', '4');
await p.fill('textarea[name=comment]', 'تعامل محترم والأسعار كويسة');
await p.fill('input[name=author_name]', 'أم أحمد');
await p.click('button:has-text("ابعت رأيك")');
await settle(p, 2000);
await p.reload({ waitUntil: 'domcontentloaded' });
await settle(p, 1200);
const rv = await p.content();
rv.includes('تعامل محترم') && rv.includes('أم أحمد')
  ? ok('الريفيو اتسجل وبيظهر بالاسم')
  : bad('الريفيو مظهرش');

// ٧) المحادثة: صاحب الطلب ينشر وياخد لينك
await p.goto(BASE + '/requests', { waitUntil: 'domcontentloaded' });
await settle(p, 1200);
await p.fill('textarea[name=body]', 'محتاج حد يجيبلي عيش من الفرن');
await p.fill('input[name=contact_phone]', '01234500011');
await p.click('button[type=submit]');
await p.waitForSelector('a[href^="/t/"]', { timeout: 9000 });
const threadHref = await p.locator('a[href^="/t/"]').first().getAttribute('href');
ok(`صاحب الطلب خد لينك محادثته (${threadHref})`);

await p.goto(BASE + threadHref, { waitUntil: 'domcontentloaded' });
await settle(p, 1200);
await p.fill('textarea[name=body]', 'لو حد فاضي يكلمني');
await p.click('button[type=submit]');
await settle(p, 1800);
(await p.locator('.bubble-msg').count()) > 0
  ? ok('صاحب الطلب بعت رسالة')
  : bad('الرسالة مبعتتش');

// ٨) السائق يرد من لينكه
const reqId = threadHref.split('/')[2];
await p.goto(`${BASE}/d/${TOK}`, { waitUntil: 'domcontentloaded' });
await settle(p, 1400);
const replyLink = await p.locator('a[href^="/t/p/"]').first().getAttribute('href');
if (!replyLink) { bad('السائق مش شايف زرار الرد'); }
else {
  await p.goto(BASE + replyLink, { waitUntil: 'domcontentloaded' });
  await settle(p, 1200);
  const seen = await p.locator('.bubble-msg').count();
  seen > 0 ? ok(`السائق شايف رسالة صاحب الطلب (${seen})`) : bad('السائق مش شايف الرسايل');
  await p.fill('textarea[name=body]', 'أنا جاي خلال ربع ساعة');
  await p.click('button[type=submit]');
  await settle(p, 1800);
  (await p.locator('.bubble-msg').count()) > seen ? ok('السائق رد') : bad('رد السائق مظهرش');
}

// ٩) لينك محادثة غلط
const r404 = await p.goto(BASE + '/t/not-a-real-token');
r404.status() === 404 ? ok('لينك المحادثة الغلط بيرجع 404') : bad(`رجع ${r404.status()}`);


/* ===== خصوصية رقم صاحب الطلب ===== */

// ١) الإخفاء هو الافتراضي
await p.goto(BASE+'/requests',{waitUntil:'domcontentloaded'}); await settle(p, 1400);
(await p.locator('input[name=hide_phone]').isChecked())
  ? ok('إخفاء الرقم شغّال افتراضيًا') : bad('الإخفاء مش افتراضي');

// ٢) طلب برقم مخفي
await p.fill('textarea[name=body]','محتاج حد يجيبلي دوا من الصيدلية');
await p.fill('input[name=contact_phone]','01277889900');
await p.click('button[type=submit]');
await p.waitForSelector('a[href^="/t/"]',{timeout:9000});
ok('الطلب اتنشر برقم مخفي');

// ٣) الرقم الحقيقي مش موجود في صفحة الطلبات خالص

await guest.goto(BASE+'/requests',{waitUntil:'domcontentloaded'}); await settle(guest, 1300);
const boardHtml = await guest.content();
boardHtml.includes('01277889900')
  ? bad('🔴 الرقم الحقيقي ظاهر في الصفحة')
  : ok('الرقم الحقيقي مش موجود في HTML الصفحة خالص');
boardHtml.includes('0127••••00')
  ? ok('الرقم بيظهر ناقص 0127••••00')
  : bad('القناع مش ظاهر');
(await guest.locator('a[href^="tel:0127"]').count()) === 0
  ? ok('مفيش زرار اتصال للطلب المخفي') : bad('زرار الاتصال لسه موجود');

// ٤) طلب برقم ظاهر لسه بيشتغل عادي
await p.goto(BASE+'/requests',{waitUntil:'domcontentloaded'}); await settle(p, 1400);
await p.fill('textarea[name=body]','محتاج توصيلة للمحلة وممكن حد يكلمني عادي');
await p.fill('input[name=contact_phone]','01255443322');
await p.locator('input[name=hide_phone]').uncheck();
await p.click('button[type=submit]');
await p.waitForSelector('a[href^="/t/"]',{timeout:9000});
await guest.goto(BASE+'/requests',{waitUntil:'domcontentloaded'}); await settle(guest, 1300);
(await guest.locator('a[href="tel:01255443322"]').count()) > 0
  ? ok('اللي اختار يظهر رقمه، رقمه ظاهر وزرار الاتصال شغال')
  : bad('الرقم الظاهر مبقاش شغال');

await b.close();
console.log(`\n${pass} عدّت · ${fail} فشلت`);
process.exit(fail > 0 ? 1 : 0);
