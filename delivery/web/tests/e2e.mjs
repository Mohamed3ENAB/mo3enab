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
const ctx = await b.newContext({ viewport: { width: 390, height: 844 }, locale: 'ar-EG' });
const p = await ctx.newPage();

// ١) الزائر يشوف الدليل من غير تسجيل
await p.goto(BASE + '/', { waitUntil: 'networkidle' });
const liveBefore = await p.locator('.row.live').count();
liveBefore === 3 ? ok(`الزائر شاف الدليل بدون تسجيل (${liveBefore} متاحين)`) : bad(`متوقع 3 متاحين، لقينا ${liveBefore}`);

// ٢) زر الاتصال فيه tel: حقيقي
const tel = await p.locator('.row.live .btn.call').first().getAttribute('href');
/^tel:\d+/.test(tel ?? '') ? ok(`زر الاتصال بيفتح الاتصال (${tel})`) : bad(`href غلط: ${tel}`);

// ٣) السائق يقفل توفره → يختفي من المتاحين
await p.goto(`${BASE}/d/${TOK}`, { waitUntil: 'networkidle' });
await p.locator('button.toggle').click();
await p.waitForTimeout(1200);
const offText = await p.locator('button.toggle').innerText();
offText.includes('مش متاح') ? ok('السائق قفل توفره') : bad(`نص الزرار: ${offText}`);

await p.goto(BASE + '/', { waitUntil: 'networkidle' });
const liveAfter = await p.locator('.row.live').count();
liveAfter === liveBefore - 1 ? ok(`اختفى من المتاحين فورًا (${liveBefore} ← ${liveAfter})`) : bad(`متوقع ${liveBefore-1}، لقينا ${liveAfter}`);

// ٤) يرجّع يفتح → يرجع يظهر
await p.goto(`${BASE}/d/${TOK}`, { waitUntil: 'networkidle' });
await p.locator('button.toggle').click();
await p.waitForTimeout(1200);
await p.goto(BASE + '/', { waitUntil: 'networkidle' });
const liveBack = await p.locator('.row.live').count();
liveBack === liveBefore ? ok('رجع يظهر لما فتح توفره') : bad(`متوقع ${liveBefore}، لقينا ${liveBack}`);

// ٥) لينك غلط
const r = await p.goto(BASE + '/d/not-a-real-token');
r.status() === 404 ? ok('اللينك الغلط بيرجع 404') : bad(`متوقع 404، رجع ${r.status()}`);

// ٦) نشر طلب في اللوحة
await p.goto(BASE + '/requests', { waitUntil: 'networkidle' });
const reqsBefore = await p.locator('.req').count();
await p.fill('textarea[name="body"]', 'اختبار: محتاج حد يجيبلي كيلو طماطم من السوق');
await p.fill('input[name="contact_phone"]', '01234567890');
await p.click('button[type="submit"]');
await p.waitForTimeout(1500);
await p.goto(BASE + '/requests', { waitUntil: 'networkidle' });
const reqsAfter = await p.locator('.req').count();
reqsAfter === reqsBefore + 1 ? ok(`الطلب اتنشر (${reqsBefore} ← ${reqsAfter})`) : bad(`متوقع ${reqsBefore+1}، لقينا ${reqsAfter}`);

// ٧) رقم موبايل غلط بيترفض
await p.fill('textarea[name="body"]', 'اختبار رقم غلط لازم يترفض');
await p.fill('input[name="contact_phone"]', '123');
await p.click('button[type="submit"]');
await p.waitForTimeout(1200);
const badMsg = await p.locator('.msg.bad').count();
badMsg > 0 ? ok('الرقم الغلط اترفض برسالة واضحة') : bad('الرقم الغلط عدّى');

// ٨) /admin يرمي على تسجيل الدخول
await p.goto(BASE + '/admin', { waitUntil: 'networkidle' });
p.url().includes('/admin/login') ? ok('/admin محمية — بترمي على الدخول') : bad(`مرماش: ${p.url()}`);

// ٩) كلمة سر غلط
await p.fill('input[name="password"]', 'wrong-password');
await p.click('button[type="submit"]');
await p.waitForTimeout(1200);
const loginErr = await p.locator('.msg.bad').count();
loginErr > 0 && !p.url().endsWith('/admin') ? ok('كلمة السر الغلط اترفضت') : bad('كلمة السر الغلط عدّت');

// ١٠) كلمة السر الصح
await p.fill('input[name="password"]', ADMIN);
await p.click('button[type="submit"]');
await p.waitForTimeout(1800);
p.url().endsWith('/admin') ? ok('الإدارة دخلت') : bad(`مدخلتش: ${p.url()}`);

// ١١) الإيقاف بيخفي السائق فورًا
await p.click('text=السواقين');
await p.waitForTimeout(500);
await p.locator('.admin-row').first().locator('button:has-text("أوقف")').click();
await p.waitForTimeout(1500);
const ctx2 = await b.newContext({ viewport: { width: 390, height: 844 } });
const guest = await ctx2.newPage();
await guest.goto(BASE + '/', { waitUntil: 'networkidle' });
const afterSuspend = await guest.locator('.row').count();
afterSuspend === 5 ? ok(`السائق الموقوف اختفى من الدليل فورًا (6 ← ${afterSuspend})`) : bad(`متوقع 5 صفوف، لقينا ${afterSuspend}`);

// ١٢) الزائر مش شايف لينكات السواقين في أي صفحة
const html = await guest.content();
html.includes(TOK) ? bad('🔴 لينك سائق ظاهر للزائر') : ok('لينكات السواقين مش ظاهرة للزائر');

await b.close();
console.log(`\n${pass} عدّت · ${fail} فشلت`);
process.exit(fail > 0 ? 1 : 0);
