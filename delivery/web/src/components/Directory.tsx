'use client';

import { useMemo, useState } from 'react';
import type { Provider, Zone, ServiceKind } from '@/lib/types';
import { SERVICE_LABELS, SERVICE_ORDER } from '@/lib/types';
import { ProviderCard } from './ProviderCard';
import { Search, Close, Chevron, Grid, Sleep, Board, SERVICE_ICON } from './icons';
import Link from 'next/link';

const BUBBLE: Record<string, string> = {
  all: 'b-all',
  delivery: 'b-delivery',
  tuktuk: 'b-tuktuk',
  goods: 'b-goods',
  bicycle: 'b-bicycle',
  mahalla_run: 'b-mahalla',
};

export function Directory({ providers, zones }: { providers: Provider[]; zones: Zone[] }) {
  const [zone, setZone] = useState<number | null>(null);
  const [kind, setKind] = useState<ServiceKind | null>(null);
  const [term, setTerm] = useState('');

  const list = useMemo(() => {
    const t = term.trim();
    return providers.filter(
      (p) =>
        (zone === null || p.zone_id === zone) &&
        (kind === null || p.services.includes(kind)) &&
        (t === '' || p.display_name.includes(t) || (p.vehicle_note ?? '').includes(t))
    );
  }, [providers, zone, kind, term]);

  const live = list.filter((p) => p.live);
  const off = list.filter((p) => !p.live);
  const zoneName = zones.find((z) => z.id === zone)?.name_ar;
  const where = zoneName ? `في ${zoneName}` : 'في القرى الخمسة';

  return (
    <>
      <div className="searchwrap">
        <div className="search">
          <Search />
          <input
            type="search"
            value={term}
            onChange={(e) => setTerm(e.target.value)}
            placeholder="دوّر على اسم سائق أو مركبة"
            aria-label="بحث"
          />
          {term ? (
            <button className="clear" onClick={() => setTerm('')} aria-label="مسح البحث">
              <Close />
            </button>
          ) : null}
        </div>
      </div>

      <section className="sec">
        <div className="sec-head">
          <h2>الخدمات</h2>
        </div>
        <div className="services">
          <button className="svc" aria-pressed={kind === null} onClick={() => setKind(null)}>
            <span className={`bubble ${BUBBLE.all}`}>
              <Grid size={22} />
            </span>
            <span>الكل</span>
          </button>
          {SERVICE_ORDER.map((k) => {
            const Icon = SERVICE_ICON[k];
            return (
              <button
                key={k}
                className="svc"
                aria-pressed={kind === k}
                onClick={() => setKind(kind === k ? null : k)}
              >
                <span className={`bubble ${BUBBLE[k]}`}>
                  <Icon size={22} />
                </span>
                <span>{SERVICE_LABELS[k]}</span>
              </button>
            );
          })}
        </div>
      </section>

      <section className="sec">
        <div className="zones">
          <button className="zchip" aria-pressed={zone === null} onClick={() => setZone(null)}>
            كل القرى
          </button>
          {zones.map((z) => (
            <button
              key={z.id}
              className="zchip"
              aria-pressed={zone === z.id}
              onClick={() => setZone(zone === z.id ? null : z.id)}
            >
              {z.name_ar}
            </button>
          ))}
        </div>
      </section>

      <section className="sec">
        <div className="sec-head">
          <h2>{live.length > 0 ? `متاحين دلوقتي ${where}` : `مفيش حد متاح ${where}`}</h2>
          {live.length > 0 ? <span className="side">{live.length}</span> : null}
        </div>

        {live.length > 0 ? (
          <div className="cards">
            {live.map((p, i) => (
              <ProviderCard key={p.id} p={p} index={i} />
            ))}
          </div>
        ) : (
          <div className="empty pop">
            <div className="ill">
              <Sleep />
            </div>
            <h3>مفيش حد فاتح دلوقتي</h3>
            <p>جرّب قرية جنبك، أو اكتب طلبك واللي هيفتح هيشوفه.</p>
            <Link className="btn call wide" href="/requests">
              <Board size={19} /> اكتب طلبك
            </Link>
          </div>
        )}

        {off.length > 0 ? (
          <details className="offline">
            <summary>
              مش متاحين دلوقتي ({off.length})
              <Chevron className="chev" />
            </summary>
            <div className="cards">
              {off.map((p, i) => (
                <ProviderCard key={p.id} p={p} index={i} />
              ))}
            </div>
          </details>
        ) : null}

        {list.length === 0 && (zone !== null || kind !== null || term !== '') ? (
          <div className="empty pop" style={{ marginTop: 12 }}>
            <h3>مفيش نتايج</h3>
            <p>مفيش سائق مطابق للاختيار ده.</p>
            <button
              className="btn ghost wide"
              onClick={() => {
                setZone(null);
                setKind(null);
                setTerm('');
              }}
            >
              شيل الفلاتر
            </button>
          </div>
        ) : null}
      </section>
    </>
  );
}
