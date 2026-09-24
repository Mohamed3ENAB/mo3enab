import { getOpenRequests, getZones } from '@/lib/queries';
import { SERVICE_LABELS, SERVICE_ORDER } from '@/lib/types';
import { sinceArabic, telHref } from '@/lib/format';
import { TopBar } from '@/components/TopBar';
import { RequestForm } from './form';

export const dynamic = 'force-dynamic';

export default async function RequestsPage() {
  const [reqs, zones] = await Promise.all([getOpenRequests(), getZones()]);

  return (
    <>
      <TopBar current="requests" />
      <main className="wrap page">
        <h1>لوحة الطلبات</h1>
        <p className="lede">
          مش لاقي حد متاح؟ اكتب اللي محتاجه هنا، والسواقين اللي بيتابعوا اللوحة هيشوفوه
          ويكلموك.
        </p>

        <RequestForm zones={zones} />

        <h2>الطلبات المفتوحة</h2>
        {reqs.length === 0 ? (
          <p className="lede">مفيش طلبات مفتوحة دلوقتي. أول واحد يكتب.</p>
        ) : (
          reqs.map((r) => (
            <div className="req" key={r.id}>
              <p>{r.body}</p>
              <p className="when">
                {[r.zone_name, r.kind ? SERVICE_LABELS[r.kind] : null]
                  .filter(Boolean)
                  .join(' · ')}
                {r.zone_name || r.kind ? ' · ' : ''}
                {sinceArabic(r.created_at)}
              </p>
              <div className="actions">
                <a className="btn ghost" href={telHref(r.contact_phone)}>
                  اتصل بصاحب الطلب
                </a>
              </div>
            </div>
          ))
        )}

        <div className="note" style={{ marginTop: 26 }}>
          الطلب بيختفي لوحده بعد <strong>٦ ساعات</strong>. متكتبش بيانات أكتر من اللازم — الصفحة
          دي مفتوحة لأي حد.
        </div>
      </main>
    </>
  );
}
