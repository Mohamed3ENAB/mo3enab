'use client';

import { useState } from 'react';
import Link from 'next/link';
import type { Provider } from '@/lib/types';
import { SERVICE_LABELS } from '@/lib/types';
import { sinceArabic, telHref, waHref } from '@/lib/format';
import { Avatar } from './Avatar';
import { Check, Phone, WhatsApp, Star, Pin, Chevron, SERVICE_ICON } from './icons';

export function ProviderCard({ p, index = 0 }: { p: Provider; index?: number }) {
  const [sheet, setSheet] = useState(false);
  const Vehicle = SERVICE_ICON[p.services[0] ?? 'delivery'] ?? SERVICE_ICON.delivery;
  const services = p.services.map((s) => SERVICE_LABELS[s]).join('، ');
  const rating = Number(p.rating_count) > 0 ? p.rating_avg : null;
  const first = p.display_name.split(' ')[0];

  return (
    <>
      <article
        className={`row rise${p.live ? ' live' : ''}`}
        style={{ animationDelay: `${Math.min(index, 8) * 55}ms` }}
      >
        <Avatar name={p.display_name} photoId={p.photo_id} verified={p.is_verified}>
          <Check size={12} />
        </Avatar>

        <div>
          <div className="head">
            <h3>{p.display_name}</h3>
            {rating ? (
              <span className="rate">
                <Star /> {rating}
              </span>
            ) : null}
          </div>

          <p className="meta">
            <Vehicle size={16} />
            {p.vehicle_note || services}
            <Pin size={13} />
            {p.zone_name}
          </p>

          {p.vehicle_note ? <p className="subnote">{services}</p> : null}
          {p.note ? <p className="subnote">{p.note}</p> : null}

          <span className={`state ${p.live ? 'on' : 'off'}`}>
            <span className="beacon">
              <i />
              <i />
            </span>
            {p.live ? 'متاح' : 'مش متاح'}
            <span className="since">{sinceArabic(p.availability_updated_at)}</span>
          </span>

          <div className="actions">
            <a className="btn call" href={telHref(p.phone)} aria-label={`اتصل بـ ${first}`}>
              <Phone /> اتصال
            </a>
            {p.whatsapp ? (
              <a
                className="btn wa"
                href={waHref(p.whatsapp, `السلام عليكم، لقيت رقمك في «في السكة»`)}
                target="_blank"
                rel="noreferrer"
                aria-label={`واتساب ${first}`}
              >
                <WhatsApp />
              </a>
            ) : null}
            <button className="btn quiet" onClick={() => setSheet(true)} aria-label="خيارات">
              <Chevron size={16} />
            </button>
          </div>
        </div>
      </article>

      {sheet ? (
        <div className="scrim" onClick={() => setSheet(false)} role="dialog" aria-modal="true">
          <div className="sheet" onClick={(e) => e.stopPropagation()}>
            <div className="grab" />
            <h2 style={{ margin: '0 0 4px', fontSize: 19, fontWeight: 800 }}>{p.display_name}</h2>
            <p style={{ margin: '0 0 18px', color: 'var(--ink-500)', fontSize: 14.5 }}>
              {services} · {p.zone_name}
            </p>
            <a className="btn call wide" href={telHref(p.phone)} style={{ marginBottom: 10 }}>
              <Phone /> {p.phone}
            </a>
            <Link className="btn ghost wide" href={`/p/${p.id}`} style={{ marginBottom: 10 }}>
              تقييم أو بلاغ
            </Link>
            <button className="btn quiet wide" onClick={() => setSheet(false)}>
              إغلاق
            </button>
          </div>
        </div>
      ) : null}
    </>
  );
}
