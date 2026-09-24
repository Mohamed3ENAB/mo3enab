import { getPrices } from '@/lib/queries';
import { SERVICE_LABELS } from '@/lib/types';
import { TopBar } from '@/components/TopBar';
import { Foot } from '@/components/Foot';

export const revalidate = 300;

export default async function PricesPage() {
  const rows = await getPrices();

  return (
    <>
      <TopBar current="prices" />
      <main className="wrap page">
        <h1>الأسعار الاسترشادية</h1>
        <p className="lede">
          دي متوسط اللي بيتدفع في البلد، عشان حد يعرف السعر العادل قبل ما يتفق. الاتفاق النهائي
          بينك وبين السائق.
        </p>

        <div className="note warn">
          <strong>دي مش تسعيرة ملزمة.</strong> إحنا مش بنحدد أسعار ومش بناخد عمولة. لو السائق طلب
          أكتر أو أقل، ده شأنكم انتوا الاتنين.
        </div>

        <table className="prices">
          <thead>
            <tr>
              <th>الخدمة</th>
              <th>المشوار</th>
              <th>المعتاد</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r, i) => (
              <tr key={i}>
                <td>{r.kind ? SERVICE_LABELS[r.kind] : '—'}</td>
                <td>
                  {r.from_zone === r.to_zone
                    ? `جوه ${r.from_zone}`
                    : `${r.from_zone} ← ${r.to_zone}`}
                  {r.note_ar ? <div className="sub">{r.note_ar}</div> : null}
                </td>
                <td>
                  {r.typical_min}–{r.typical_max} ج
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        <h2>حاجات بتزوّد السعر</h2>
        <p className="lede">
          انتظار طويل · أكتر من وقفة · بعد المغرب · حمل تقيل · مطر أو طين. اتفق على ده قبل ما
          السائق يتحرك، مش بعدين.
        </p>

        <h2>لو محتاج حاجة من المحلة</h2>
        <p className="lede">
          مشوار المحلة ذهاب وعودة تكلفته الحقيقية على السائق ٣٥–٤٥ جنيه بنزين وصيانة، قبل ما ياخد
          مليم. لو تلات ناس محتاجين حاجة من المحلة في نفس اليوم، مشوار واحد يخدمكم كلكم: كل واحد
          يدفع أقل والسائق ياخد أكتر. اكتبوا في لوحة الطلبات واتفقوا.
        </p>

        <Foot />
      </main>
    </>
  );
}
