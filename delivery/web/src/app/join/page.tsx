import Link from 'next/link';
import { getVehicleRates } from '@/lib/queries';
import { SERVICE_LABELS } from '@/lib/types';
import { money, waHref, telHref } from '@/lib/format';
import { BottomNav } from '@/components/BottomNav';
import { Foot } from '@/components/Foot';
import { Bicycle, Phone, WhatsApp, Check, Info, SERVICE_ICON } from '@/components/icons';

export const revalidate = 300;

const VEHICLE_BUBBLE: Record<string, string> = {
  delivery: 'b-delivery', tuktuk: 'b-tuktuk', goods: 'b-goods',
  bicycle: 'b-bicycle', mahalla_run: 'b-mahalla',
};

const NEEDED = [
  'بطاقة الرقم القومي سارية',
  'رخصة قيادة ورخصة مركبة (مش مطلوبة للعجلة)',
  'موبايل فيه نت',
  'موافقتك على نشر اسمك ورقمك في الدليل',
];

export default async function JoinPage() {
  const rates = await getVehicleRates();
  const phone = process.env.NEXT_PUBLIC_SUPPORT_PHONE;
  const msg = 'السلام عليكم، عايز أشتغل مع «في السكة». معايا ';

  return (
    <div className="app">
      <header className="hero">
        <div className="hero-top">
          <div className="hero-brand">
            <span className="hero-logo">
              <Bicycle size={20} />
            </span>
            اشتغل معانا
          </div>
        </div>
        <h1>عندك عجلة؟ تقدر تبدأ من النهاردة</h1>
        <p>مفيش عمولة ومفيش اشتراك. الفلوس كلها تروح لك.</p>
      </header>

      <main className="wrap page" style={{ paddingTop: 22 }}>
        <div className="note">
          <Info className="ic" />
          <span>
            إحنا <strong>مش شركة توصيل</strong> ومش بناخد ولا جنيه. إحنا بنعرض اسمك ورقمك
            في دليل، والناس بتكلمك على طول وتتفق معاك. اللي تتفق عليه ياخده انت كله.
          </span>
        </div>

        <h2>الطلب بياخد كام؟</h2>
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

        <h2>محتاج إيه</h2>
        <div className="card">
          {NEEDED.map((n) => (
            <div className="mini" key={n}>
              <span
                style={{
                  width: 24, height: 24, borderRadius: '50%', flex: '0 0 auto',
                  background: 'var(--brand-tint)', color: 'var(--brand-600)',
                  display: 'grid', placeItems: 'center',
                }}
              >
                <Check size={13} />
              </span>
              <span className="t">
                <b style={{ fontWeight: 700, fontSize: 14.5 }}>{n}</b>
              </span>
            </div>
          ))}
        </div>

        <h2>بيشتغل إزاي</h2>
        <div className="card">
          <div className="step">
            <span className="n">1</span>
            <div className="c">
              <b>كلّمنا وابعت بياناتك</b>
              <p>الاسم والرقم ونوع المركبة والقرية اللي بتشتغل فيها.</p>
            </div>
          </div>
          <div className="step">
            <span className="n">2</span>
            <div className="c">
              <b>نشوف ورقك</b>
              <p>البطاقة والرخصة. ده اللي بيخلي الناس تطمن وتكلمك.</p>
            </div>
          </div>
          <div className="step">
            <span className="n">3</span>
            <div className="c">
              <b>تاخد لينك خاص بيك</b>
              <p>
                تفتحه وتضغط زرار واحد «أنا متاح» لما تبقى فاضي، و«مش متاح» لما تخلص. وبس.
              </p>
            </div>
          </div>
          <div className="step" style={{ marginBottom: 0 }}>
            <span className="n">4</span>
            <div className="c">
              <b>الناس تكلمك على طول</b>
              <p>مفيش وسيط ومفيش نسبة. تتفق مع العميل وتاخد فلوسك كاملة.</p>
            </div>
          </div>
        </div>

        {phone ? (
          <>
            <a
              className="btn call wide"
              href={waHref(phone, msg)}
              target="_blank"
              rel="noreferrer"
              style={{ marginBottom: 10 }}
            >
              <WhatsApp /> ابعتلنا واتساب
            </a>
            <a className="btn ghost wide" href={telHref(phone)}>
              <Phone /> اتصل بينا
            </a>
          </>
        ) : null}

        <h2>عندك محل أو مطعم؟</h2>
        <div className="card">
          <p className="lede" style={{ margin: 0 }}>
            نضيف اسم محلك ورقمك في <Link href="/places" style={{ fontWeight: 800 }}>دليل المحلات</Link>{' '}
            ببلاش، والناس تطلب منك مباشرة. كلّمنا على نفس الرقم.
          </p>
        </div>

        <Foot />
      </main>

      <BottomNav />
    </div>
  );
}
