import { notFound } from 'next/navigation';
import Link from 'next/link';
import { getProviderByToken, getOpenRequests, getRequestThreadCounts } from '@/lib/queries';
import { SERVICE_LABELS } from '@/lib/types';
import { sinceArabic } from '@/lib/format';
import { AvailabilityToggle } from './toggle';
import { Avatar } from '@/components/Avatar';
import { Check, Info, Alert, Pin, Chat, Clock } from '@/components/icons';

export const dynamic = 'force-dynamic';

export default async function DriverPage({ params }: { params: Promise<{ token: string }> }) {
  const { token } = await params;
  const me = await getProviderByToken(token);
  if (!me) notFound();

  const [requests, counts] = await Promise.all([getOpenRequests(), getRequestThreadCounts()]);
  const countBy = new Map(counts.map((c) => [c.request_id, Number(c.n)]));

  const live =
    me.is_available &&
    me.availability_updated_at !== null &&
    Date.now() - new Date(me.availability_updated_at).getTime() < 4 * 60 * 60 * 1000;

  return (
    <main className="wrap page">
      <div style={{ display: 'flex', alignItems: 'center', gap: 13, marginBottom: 24 }}>
        <Avatar name={me.display_name} verified={me.is_verified}>
          <Check size={12} />
        </Avatar>
        <div>
          <h1 style={{ margin: 0 }}>أهلاً {me.display_name.split(' ')[0]}</h1>
          <p className="lede" style={{ margin: 0, display: 'flex', alignItems: 'center', gap: 5 }}>
            <Pin size={13} /> {me.zone_name} · {me.services.map((s) => SERVICE_LABELS[s]).join('، ')}
          </p>
        </div>
      </div>

      <AvailabilityToggle
        token={token}
        initial={live}
        since={sinceArabic(me.availability_updated_at)}
      />

      <div className="note" style={{ marginTop: 22 }}>
        <Info className="ic" />
        <span>
          بيتقفل لوحده بعد <strong>٤ ساعات</strong> لو نسيت. ده عشان القايمة تفضل صادقة — الناس
          بتبطل تثق في التطبيق لو لقت حد مكتوب إنه متاح وهو مش متاح.
        </span>
      </div>

      <div className="note warn">
        <Alert className="ic" />
        <span>
          <strong>اللينك ده ليك انت بس.</strong> متبعتهوش لحد ومتحطهوش في جروب. أي حد معاه اللينك
          يقدر يفتح ويقفل توفرك.
        </span>
      </div>

      <h2>طلبات مفتوحة دلوقتي</h2>
      {requests.length === 0 ? (
        <p className="lede">مفيش طلبات دلوقتي.</p>
      ) : (
        requests.map((r) => (
          <div className="req" key={r.id}>
            <p>{r.body}</p>
            <div className="when">
              {r.zone_name ? (
                <>
                  <Pin size={13} /> {r.zone_name}
                </>
              ) : null}
              <Clock size={13} /> {sinceArabic(r.created_at)}
              {countBy.get(r.id) ? (
                <span className="pill info">{countBy.get(r.id)} رسالة</span>
              ) : null}
            </div>
            <Link
              className="btn ghost wide"
              href={`/t/p/${token}/${r.id}`}
              style={{ marginTop: 12 }}
            >
              <Chat size={18} /> رد داخل التطبيق
            </Link>
          </div>
        ))
      )}
    </main>
  );
}
