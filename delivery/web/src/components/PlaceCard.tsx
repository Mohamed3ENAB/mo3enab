'use client';

import { useState } from 'react';
import type { Place, Provider } from '@/lib/types';
import { PLACE_LABELS } from '@/lib/types';
import { telHref, waHref } from '@/lib/format';
import { PLACE_ICON, SERVICE_ICON, Phone, WhatsApp, Pin, Clock, Star } from './icons';
import Link from 'next/link';

export function PlaceCard({
  place,
  drivers,
  index = 0,
}: {
  place: Place;
  drivers: Provider[];
  index?: number;
}) {
  const [sheet, setSheet] = useState(false);
  const Icon = PLACE_ICON[place.category];
  const nearby = drivers.filter((d) => d.live).slice(0, 3);

  const pickupMsg = `السلام عليكم، ممكن تستلملي طلب من ${place.name_ar}${
    place.address_note ? ` (${place.address_note})` : ''
  } في ${place.zone_name}؟`;

  return (
    <>
      <article
        className="row rise"
        style={{ animationDelay: `${Math.min(index, 8) * 50}ms` }}
      >
        {place.photo_id ? (
          <span className="avatar photo">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={`/img/${place.photo_id}`} alt="" loading="lazy" decoding="async" />
          </span>
        ) : (
          <span className={`avatar b-${place.category}`} aria-hidden="true">
            <Icon size={24} />
          </span>
        )}

        <div>
          <div className="head">
            <h3>{place.name_ar}</h3>
            {Number(place.rating_count) > 0 ? (
              <span className="rate">
                <Star /> {place.rating_avg}
              </span>
            ) : null}
          </div>

          <p className="meta">
            {PLACE_LABELS[place.category]}
            <Pin size={13} />
            {place.zone_name}
          </p>

          {place.address_note ? <p className="subnote">{place.address_note}</p> : null}
          {place.hours_note ? (
            <p className="subnote" style={{ display: 'flex', alignItems: 'center', gap: 5 }}>
              <Clock size={13} /> {place.hours_note}
            </p>
          ) : null}
          {place.note ? <p className="subnote">{place.note}</p> : null}

          <div className="actions">
            <button className="btn call" onClick={() => setSheet(true)}>
              اطلب من هنا
            </button>
            <Link className="btn quiet" href={`/m/${place.id}`} aria-label="تفاصيل وتقييم">
              <Star size={16} />
            </Link>
            {place.whatsapp ? (
              <a
                className="btn wa"
                href={waHref(place.whatsapp, `السلام عليكم، عايز أطلب من ${place.name_ar}`)}
                target="_blank"
                rel="noreferrer"
                aria-label={`واتساب ${place.name_ar}`}
              >
                <WhatsApp />
              </a>
            ) : null}
          </div>
        </div>
      </article>

      {sheet ? (
        <div className="scrim" onClick={() => setSheet(false)} role="dialog" aria-modal="true">
          <div className="sheet" onClick={(e) => e.stopPropagation()}>
            <div className="grab" />
            <h2 style={{ margin: '0 0 3px', fontSize: 19, fontWeight: 800 }}>{place.name_ar}</h2>
            <p style={{ margin: '0 0 20px', color: 'var(--ink-500)', fontSize: 14 }}>
              {PLACE_LABELS[place.category]} · {place.zone_name}
            </p>

            <div className="step">
              <span className="n">1</span>
              <div className="c">
                <b>كلّم المحل واطلب</b>
                <p>قوله على طلبك، واسأله هيبقى جاهز إمتى وبكام.</p>
                <div className="actions">
                  {place.phone ? (
                    <a className="btn call" href={telHref(place.phone)} style={{ flex: 1 }}>
                      <Phone /> {place.phone}
                    </a>
                  ) : null}
                  {place.whatsapp ? (
                    <a
                      className="btn wa"
                      href={waHref(place.whatsapp, `السلام عليكم، عايز أطلب من ${place.name_ar}`)}
                      target="_blank"
                      rel="noreferrer"
                      aria-label="واتساب المحل"
                    >
                      <WhatsApp />
                    </a>
                  ) : null}
                </div>
              </div>
            </div>

            <div className="step">
              <span className="n">2</span>
              <div className="c">
                <b>كلّم سائق يستلم منهم</b>
                {nearby.length === 0 ? (
                  <p>
                    مفيش سائق متاح دلوقتي. اكتب طلبك في لوحة الطلبات، أو شوف الدليل بعد شوية.
                  </p>
                ) : (
                  <>
                    <p>الرسالة هتتكتب لوحدها — بس ابعتها.</p>
                    <div>
                      {nearby.map((d) => {
                        const Veh = SERVICE_ICON[d.services[0] ?? 'delivery'] ?? SERVICE_ICON.delivery;
                        return (
                        <div className="mini" key={d.id}>
                          <Veh size={18} />
                          <span className="t">
                            <b>{d.display_name}</b>
                            <span>{d.vehicle_note ?? ''}</span>
                          </span>
                          <a className="btn quiet" href={telHref(d.phone)} aria-label={`اتصل بـ ${d.display_name}`}>
                            <Phone size={17} />
                          </a>
                          {d.whatsapp ? (
                            <a
                              className="btn wa"
                              style={{ width: 46, minHeight: 44 }}
                              href={waHref(d.whatsapp, pickupMsg)}
                              target="_blank"
                              rel="noreferrer"
                              aria-label={`واتساب ${d.display_name}`}
                            >
                              <WhatsApp size={19} />
                            </a>
                          ) : null}
                        </div>
                        );
                      })}
                    </div>
                  </>
                )}
              </div>
            </div>

            <button className="btn quiet wide" onClick={() => setSheet(false)}>
              إغلاق
            </button>
          </div>
        </div>
      ) : null}
    </>
  );
}
