import Link from 'next/link';
import { getPrices, getVehicleRates } from '@/lib/queries';
import { SERVICE_LABELS } from '@/lib/types';
import { money } from '@/lib/format';
import { BottomNav } from '@/components/BottomNav';
import { Foot } from '@/components/Foot';
import { Alert, Money, Info, SERVICE_ICON } from '@/components/icons';

export const revalidate = 300;

const BUBBLE: Record<string, string> = {
  delivery: 'b-delivery',
  tuktuk: 'b-tuktuk',
  goods: 'b-goods',
  bicycle: 'b-bicycle',
  mahalla_run: 'b-mahalla',
};

const VEHICLE_BUBBLE: Record<string, string> = {
  delivery: 'b-delivery', tuktuk: 'b-tuktuk', goods: 'b-goods',
  bicycle: 'b-bicycle', mahalla_run: 'b-mahalla',
};

export default async function PricesPage() {
  const [rows, rates] = await Promise.all([getPrices(), getVehicleRates()]);

  return (
    <div className="app">
      <header className="hero">
        <div className="hero-top">
          <div className="hero-brand">
            <span className="hero-logo">
              <Money size={20} />
            </span>
            الأسعار
          </div>
        </div>
        <h1>اعرف السعر العادل قبل ما تتفق</h1>
        <p>دي متوسط اللي بيتدفع في البلد.</p>
      </header>

      <main className="wrap page" style={{ paddingTop: 22 }}>
        <div className="note warn">
          <Alert className="ic" />
          <span>
            <strong>دي مش تسعيرة ملزمة.</strong> إحنا مش بنحدد أسعار ومش بناخد عمولة. الاتفاق
            النهائي بينك وبين السائق.
          </span>
        </div>

        <h2 style={{ marginTop: 0 }}>يبدأ من</h2>
        <div className="rates">
          {rates.map((r) => {
            const Icon = SERVICE_ICON[r.kind];
            return (
              <div className="rate-row" key={r.kind}>
                <span className={`bubble ${VEHICLE_BUBBLE[r.kind] ?? 'b-all'}`}>
                  <Icon size={22} />
                </span>
                <span className="t">
                  <b>{SERVICE_LABELS[r.kind]}</b>
                  {r.note_ar ? <span>{r.note_ar}</span> : null}
                </span>
                <span className="v">
                  <em>من</em>
                  <b>{money(r.starts_from)} ج</b>
                </span>
              </div>
            );
          })}
        </div>

        <div className="note" style={{ marginTop: 16 }}>
          <Info className="ic" />
          <span>
            العجلة رخيصة عن قصد. الطلب القريب جوه البلد مش محتاج موتوسيكل، وده بيخلي
            أي حد عنده عجلة يقدر يشتغل ويكسب. <Link href="/join" style={{ fontWeight: 800 }}>اعرف إزاي تشتغل معانا</Link>.
          </span>
        </div>

        <h2>الأسعار حسب المشوار</h2>
        <div className="card">
          {rows.map((r, i) => {
            const Icon = r.kind ? SERVICE_ICON[r.kind] : Money;
            return (
              <div className="price" key={i}>
                <span className={`bubble ${r.kind ? BUBBLE[r.kind] : 'b-all'}`}>
                  <Icon size={20} />
                </span>
                <span className="t">
                  <b>
                    {r.from_zone === r.to_zone
                      ? `جوه ${r.from_zone}`
                      : `${r.from_zone} ← ${r.to_zone}`}
                  </b>
                  <span>
                    {r.kind ? SERVICE_LABELS[r.kind] : ''}
                    {r.note_ar ? ` · ${r.note_ar}` : ''}
                  </span>
                </span>
                <span className="v">
                  {money(r.typical_min)}–{money(r.typical_max)} ج
                </span>
              </div>
            );
          })}
        </div>

        <h2>حاجات بتزوّد السعر</h2>
        <div className="card">
          <p className="lede" style={{ margin: 0 }}>
            انتظار طويل · أكتر من وقفة · بعد المغرب · حمل تقيل · مطر أو طين. اتفق على ده قبل ما
            السائق يتحرك، مش بعدين.
          </p>
        </div>

        <h2>لو محتاج حاجة من المحلة</h2>
        <div className="card">
          <p className="lede" style={{ margin: 0 }}>
            مشوار المحلة ذهاب وعودة تكلفته الحقيقية على السائق ٣٥–٤٥ جنيه بنزين وصيانة، قبل ما ياخد
            مليم. لو تلات ناس محتاجين حاجة من المحلة في نفس اليوم، مشوار واحد يخدمكم كلكم: كل واحد
            يدفع أقل والسائق ياخد أكتر. اكتبوا في لوحة الطلبات واتفقوا.
          </p>
        </div>

        <Foot />
      </main>

      <BottomNav current="prices" />
    </div>
  );
}
