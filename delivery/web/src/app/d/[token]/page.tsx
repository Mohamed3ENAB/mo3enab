import { notFound } from 'next/navigation';
import { getProviderByToken } from '@/lib/queries';
import { SERVICE_LABELS } from '@/lib/types';
import { sinceArabic } from '@/lib/format';
import { AvailabilityToggle } from './toggle';

export const dynamic = 'force-dynamic';

export default async function DriverPage({ params }: { params: Promise<{ token: string }> }) {
  const { token } = await params;
  const me = await getProviderByToken(token);
  if (!me) notFound();

  const live =
    me.is_available &&
    me.availability_updated_at !== null &&
    Date.now() - new Date(me.availability_updated_at).getTime() < 4 * 60 * 60 * 1000;

  return (
    <main className="wrap page">
      <h1>أهلاً {me.display_name.split(' ')[0]}</h1>
      <p className="lede">
        {me.zone_name} · {me.services.map((s) => SERVICE_LABELS[s]).join('، ')}
        {me.is_verified ? ' · موثّق' : ''}
      </p>

      <AvailabilityToggle
        token={token}
        initial={live}
        since={sinceArabic(me.availability_updated_at)}
      />

      <div className="note">
        بيتقفل لوحده بعد <strong>٤ ساعات</strong> لو نسيت. ده عشان القايمة تفضل صادقة — الناس
        بتبطل تثق في التطبيق لو لقت حد مكتوب إنه متاح وهو مش متاح.
      </div>

      <div className="note warn">
        <strong>اللينك ده ليك انت بس.</strong> متبعتهوش لحد ومتحطهوش في جروب. أي حد معاه اللينك
        يقدر يفتح ويقفل توفرك.
      </div>
    </main>
  );
}
