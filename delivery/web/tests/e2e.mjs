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

const BASE = process.argv[2] || 'http://127.0.0.1:3000';
const TOK = process.argv[3];
if (!TOK) {
  console.error('محتاج لينك سائق: node tests/e2e.mjs <BASE_URL> <DRIVER_TOKEN>');
  process.exit(1);
}
const ADMIN = process.env.ADMIN_PASSWORD || 'test-admin-pass';
let pass = 0, fail = 0;
const ok  = (m) => { pass++; console.log('✅ ' + m); };
const bad = (m) => { fail++; console.log('❌ ' + m); };

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
await p.click('text=السواقين');
await p.waitForTimeout(500);
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
await p.locator('button:has-text("المحلات (")').first().click();
await settle(p, 600);
await p.locator('.admin-row button:has-text("إخفاء")').first().click();
await settle(p, 1400);
await guest.goto(BASE + '/places', { waitUntil: 'domcontentloaded' });
await settle(guest, 1000);
const placesAfterHide = await guest.locator('.row').count();
placesAfterHide === placeCount - 1
  ? ok(`المحل المخفي اختفى فورًا (${placeCount} ← ${placesAfterHide})`)
  : bad(`متوقع ${placeCount - 1} محل، لقينا ${placesAfterHide}`);

await b.close();
console.log(`\n${pass} عدّت · ${fail} فشلت`);
process.exit(fail > 0 ? 1 : 0);
