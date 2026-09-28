import { getOpenRequests, getZones } from '@/lib/queries';
import { SERVICE_LABELS } from '@/lib/types';
import { sinceArabic, telHref } from '@/lib/format';
import { BottomNav } from '@/components/BottomNav';
import { RequestForm } from './form';
import { Phone, Clock, Pin, Info, Board, Shield } from '@/components/icons';

export const dynamic = 'force-dynamic';

export default async function RequestsPage() {
  const [reqs, zones] = await Promise.all([getOpenRequests(), getZones()]);

  return (
    <div className="app">
      <header className="hero">
        <div className="hero-top">
          <div className="hero-brand">
            <span className="hero-logo">
              <Board size={20} />
            </span>
            لوحة الطلبات
          </div>
        </div>
        <h1>مش لاقي حد متاح؟</h1>
        <p>اكتب اللي محتاجه، واللي هيفتح هيشوفه ويكلمك.</p>
      </header>

      <main className="wrap page" style={{ paddingTop: 22 }}>
        <div className="card">
          <RequestForm zones={zones} />
        </div>

        <h2>الطلبات المفتوحة</h2>
        {reqs.length === 0 ? (
          <div className="empty pop">
            <h3>مفيش طلبات دلوقتي</h3>
            <p>أول واحد يكتب.</p>
          </div>
        ) : (
          reqs.map((r, i) => (
            <div className="req rise" key={r.id} style={{ animationDelay: `${Math.min(i, 8) * 55}ms` }}>
              <p>{r.body}</p>
              <div className="when">
                {r.zone_name ? (
                  <>
                    <Pin size={13} /> {r.zone_name}
                  </>
                ) : null}
                {r.kind ? <span className="pill info">{SERVICE_LABELS[r.kind]}</span> : null}
                <Clock size={13} /> {sinceArabic(r.created_at)}
              </div>
              {r.contact_phone ? (
                <a className="btn ghost wide" href={telHref(r.contact_phone)} style={{ marginTop: 12 }}>
                  <Phone /> اتصل بصاحب الطلب
                </a>
              ) : (
                <div style={{ marginTop: 12 }}>
                  <p className="when" style={{ marginBottom: 8 }}>
                    <Shield size={14} /> {r.masked_phone} — صاحب الطلب مخفي رقمه
                  </p>
                  <p className="lede" style={{ margin: 0, fontSize: 14 }}>
                    لو انت سائق، افتح لينكك الخاص وهتلاقي زرار الرد على الطلب ده.
                  </p>
                </div>
              )}
            </div>
          ))
        )}

        <div className="note" style={{ marginTop: 20 }}>
          <Info className="ic" />
          <span>
            الطلب بيختفي لوحده بعد <strong>٦ ساعات</strong>. متكتبش بيانات أكتر من اللازم — الصفحة
            دي مفتوحة لأي حد.
          </span>
        </div>
      </main>

      <BottomNav current="requests" />
    </div>
  );
}
