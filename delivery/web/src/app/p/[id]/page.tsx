import { notFound } from 'next/navigation';
import Link from 'next/link';
import { getProvider } from '@/lib/queries';
import { SERVICE_LABELS } from '@/lib/types';
import { sinceArabic, telHref } from '@/lib/format';
import { Foot } from '@/components/Foot';
import { Avatar } from '@/components/Avatar';
import { FeedbackForms } from './forms';
import { Check, Phone, Star, Pin, Home } from '@/components/icons';

export const dynamic = 'force-dynamic';

export default async function ProviderPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const p = await getProvider(id);
  if (!p) notFound();
  const rating = Number(p.rating_count) > 0 ? p.rating_avg : null;

  return (
    <main className="wrap page">
      <Link className="linkish" href="/" style={{ marginBottom: 14 }}>
        <Home size={16} /> رجوع للدليل
      </Link>

      <div style={{ display: 'flex', alignItems: 'center', gap: 13, margin: '10px 0 18px' }}>
        <Avatar name={p.display_name} verified={p.is_verified}>
          <Check size={12} />
        </Avatar>
        <div>
          <h1 style={{ margin: 0, display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
            {p.display_name}
            {rating ? (
              <span className="rate">
                <Star /> {rating}
              </span>
            ) : null}
          </h1>
          <p className="lede" style={{ margin: 0, display: 'flex', alignItems: 'center', gap: 5 }}>
            <Pin size={13} /> {p.zone_name} · {p.services.map((s) => SERVICE_LABELS[s]).join('، ')}
          </p>
        </div>
      </div>

      <span className={`state ${p.live ? 'on' : 'off'}`}>
        <span className="beacon">
          <i />
          <i />
        </span>
        {p.live ? 'متاح' : 'مش متاح'}
        <span className="since">{sinceArabic(p.availability_updated_at)}</span>
      </span>

      <a className="btn call wide" href={telHref(p.phone)} style={{ margin: '18px 0 26px' }}>
        <Phone /> اتصل بـ {p.display_name.split(' ')[0]}
      </a>

      <FeedbackForms providerId={p.id} name={p.display_name} />
      <Foot />
    </main>
  );
}
