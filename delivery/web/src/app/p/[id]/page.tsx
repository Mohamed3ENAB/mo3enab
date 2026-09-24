import { notFound } from 'next/navigation';
import Link from 'next/link';
import { getProvider } from '@/lib/queries';
import { SERVICE_LABELS } from '@/lib/types';
import { sinceArabic, telHref } from '@/lib/format';
import { TopBar } from '@/components/TopBar';
import { Foot } from '@/components/Foot';
import { FeedbackForms } from './forms';

export const dynamic = 'force-dynamic';

export default async function ProviderPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const p = await getProvider(id);
  if (!p) notFound();

  return (
    <>
      <TopBar />
      <main className="wrap page">
        <h1>{p.display_name}</h1>
        <p className="lede">
          {[p.vehicle_note, p.services.map((s) => SERVICE_LABELS[s]).join('، '), p.zone_name]
            .filter(Boolean)
            .join(' · ')}
          {p.is_verified ? ' · موثّق' : ''}
        </p>

        <p className={`status${p.live ? '' : ''}`} style={{ color: p.live ? 'var(--live)' : 'var(--muted)' }}>
          <span className="dot" aria-hidden="true" /> {p.live ? 'متاح' : 'مش متاح'}{' '}
          <span className="since">{sinceArabic(p.availability_updated_at)}</span>
        </p>

        <div className="actions" style={{ margin: '18px 0 30px' }}>
          <a className="btn" href={telHref(p.phone)}>
            اتصال
          </a>
          <Link className="btn ghost" href="/">
            رجوع للدليل
          </Link>
        </div>

        <FeedbackForms providerId={p.id} name={p.display_name} />
        <Foot />
      </main>
    </>
  );
}
