import { notFound } from 'next/navigation';
import Link from 'next/link';
import { getPlace, getReviews } from '@/lib/queries';
import { PLACE_LABELS } from '@/lib/types';
import { telHref, waHref } from '@/lib/format';
import { Foot } from '@/components/Foot';
import { Reviews } from '@/components/Reviews';
import { PlaceFeedback } from './forms';
import { PLACE_ICON, Phone, WhatsApp, Star, Pin, Clock, Store } from '@/components/icons';

export const dynamic = 'force-dynamic';

export default async function PlacePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const place = await getPlace(id);
  if (!place) notFound();
  const reviews = await getReviews({ placeId: id });
  const Icon = PLACE_ICON[place.category];
  const rated = Number(place.rating_count) > 0;

  return (
    <main className="wrap page">
      <Link className="linkish" href="/places" style={{ marginBottom: 14 }}>
        <Store size={16} /> رجوع للمحلات
      </Link>

      <div style={{ display: 'flex', alignItems: 'center', gap: 13, margin: '10px 0 18px' }}>
        {place.photo_id ? (
          <span className="avatar photo" style={{ width: 62, height: 62 }}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={`/img/${place.photo_id}`} alt="" />
          </span>
        ) : (
          <span className={`avatar b-${place.category}`} style={{ width: 62, height: 62 }}>
            <Icon size={28} />
          </span>
        )}
        <div>
          <h1 style={{ margin: 0, display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' }}>
            {place.name_ar}
            {rated ? (
              <span className="rate">
                <Star /> {place.rating_avg}
              </span>
            ) : null}
          </h1>
          <p className="lede" style={{ margin: 0, display: 'flex', alignItems: 'center', gap: 5 }}>
            {PLACE_LABELS[place.category]} <Pin size={13} /> {place.zone_name}
          </p>
        </div>
      </div>

      {place.address_note ? <p className="lede">{place.address_note}</p> : null}
      {place.hours_note ? (
        <p className="lede" style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
          <Clock size={14} /> {place.hours_note}
        </p>
      ) : null}
      {place.note ? <p className="lede">{place.note}</p> : null}

      <div className="actions" style={{ margin: '16px 0 26px' }}>
        {place.phone ? (
          <a className="btn call" href={telHref(place.phone)} style={{ flex: 1 }}>
            <Phone /> اتصال
          </a>
        ) : null}
        {place.whatsapp ? (
          <a
            className="btn wa"
            href={waHref(place.whatsapp, `السلام عليكم، عايز أطلب من ${place.name_ar}`)}
            target="_blank"
            rel="noreferrer"
            aria-label="واتساب"
          >
            <WhatsApp />
          </a>
        ) : null}
      </div>

      <h2>آراء الناس {rated ? `(${place.rating_count})` : ''}</h2>
      <Reviews reviews={reviews} />

      <PlaceFeedback placeId={place.id} name={place.name_ar} />
      <Foot />
    </main>
  );
}
