import Link from 'next/link';
import type { Provider } from '@/lib/types';
import { SERVICE_LABELS } from '@/lib/types';
import { sinceArabic, telHref, waHref } from '@/lib/format';

export function ProviderRow({ p }: { p: Provider }) {
  const services = p.services.map((s) => SERVICE_LABELS[s]).join('، ');
  const rating = Number(p.rating_count) > 0 ? p.rating_avg : null;

  return (
    <article className={`row${p.live ? ' live' : ''}`}>
      <div className="state" aria-hidden="true" />
      <div className="body">
        <h3>
          {p.display_name}
          {p.is_verified ? <span className="verified">موثّق</span> : null}
        </h3>

        <p className="meta">
          {[p.vehicle_note, services].filter(Boolean).join(' · ')}
          {p.note ? ` · ${p.note}` : ''}
        </p>

        <p className="status">
          <span className="dot" aria-hidden="true" />
          {p.live ? 'متاح' : 'مش متاح'}
          <span className="since">{sinceArabic(p.availability_updated_at)}</span>
        </p>

        {rating ? (
          <p className="stars">
            {rating} من 5 ({p.rating_count} تقييم)
          </p>
        ) : null}

        <div className="actions">
          <a className="btn call" href={telHref(p.phone)}>
            اتصل بـ {p.display_name.split(' ')[0]}
          </a>
          {p.whatsapp ? (
            <a className="btn ghost" href={waHref(p.whatsapp)} target="_blank" rel="noreferrer">
              واتساب
            </a>
          ) : null}
        </div>

        <Link className="linkish" href={`/p/${p.id}`}>
          تقييم أو بلاغ
        </Link>
      </div>
    </article>
  );
}
