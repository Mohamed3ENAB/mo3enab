'use client';

import { useMemo, useState } from 'react';
import Link from 'next/link';
import type { Place, PlaceCategory, Provider, Zone } from '@/lib/types';
import { PLACE_LABELS } from '@/lib/types';
import { PlaceCard } from './PlaceCard';
import { PLACE_ICON, Search, Close, Grid, Store } from './icons';

export function Places({
  places,
  drivers,
  zones,
}: {
  places: Place[];
  drivers: Provider[];
  zones: Zone[];
}) {
  const [cat, setCat] = useState<PlaceCategory | null>(null);
  const [zone, setZone] = useState<number | null>(null);
  const [term, setTerm] = useState('');

  // اعرض التصنيفات اللي فيها محلات فعلاً بس
  const cats = useMemo(() => {
    const seen = new Set(places.map((p) => p.category));
    return (Object.keys(PLACE_LABELS) as PlaceCategory[]).filter((c) => seen.has(c));
  }, [places]);

  const list = useMemo(() => {
    const t = term.trim();
    return places.filter(
      (p) =>
        (cat === null || p.category === cat) &&
        (zone === null || p.zone_id === zone) &&
        (t === '' || p.name_ar.includes(t) || (p.address_note ?? '').includes(t))
    );
  }, [places, cat, zone, term]);

  return (
    <>
      <div className="searchwrap">
        <div className="search">
          <Search />
          <input
            type="search"
            value={term}
            onChange={(e) => setTerm(e.target.value)}
            placeholder="دوّر على محل أو مطعم"
            aria-label="بحث"
          />
          {term ? (
            <button className="clear" onClick={() => setTerm('')} aria-label="مسح البحث">
              <Close />
            </button>
          ) : null}
        </div>
      </div>

      {cats.length > 0 ? (
        <section className="sec">
          <div className="sec-head">
            <h2>التصنيفات</h2>
          </div>
          <div className="services">
            <button className="svc" aria-pressed={cat === null} onClick={() => setCat(null)}>
              <span className="bubble b-all">
                <Grid size={22} />
              </span>
              <span>الكل</span>
            </button>
            {cats.map((c) => {
              const Icon = PLACE_ICON[c];
              return (
                <button
                  key={c}
                  className="svc"
                  aria-pressed={cat === c}
                  onClick={() => setCat(cat === c ? null : c)}
                >
                  <span className={`bubble b-${c}`}>
                    <Icon size={22} />
                  </span>
                  <span>{PLACE_LABELS[c]}</span>
                </button>
              );
            })}
          </div>
        </section>
      ) : null}

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
          <h2>{cat ? PLACE_LABELS[cat] : 'كل المحلات'}</h2>
          {list.length > 0 ? <span className="side">{list.length}</span> : null}
        </div>

        {list.length > 0 ? (
          <div className="cards">
            {list.map((p, i) => (
              <PlaceCard key={p.id} place={p} drivers={drivers} index={i} />
            ))}
          </div>
        ) : places.length === 0 ? (
          <div className="empty pop">
            <div className="ill">
              <Store size={30} />
            </div>
            <h3>لسه مفيش محلات مضافة</h3>
            <p>تعرف محل أو مطعم بيوصّل؟ قولنا عليه ونضيفه.</p>
            <Link className="btn call wide" href="/join">
              ضيف محلك
            </Link>
          </div>
        ) : (
          <div className="empty pop">
            <h3>مفيش نتايج</h3>
            <p>مفيش محل مطابق للاختيار ده.</p>
            <button
              className="btn ghost wide"
              onClick={() => {
                setCat(null);
                setZone(null);
                setTerm('');
              }}
            >
              شيل الفلاتر
            </button>
          </div>
        )}
      </section>
    </>
  );
}
