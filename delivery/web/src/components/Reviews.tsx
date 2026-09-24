import type { Review } from '@/lib/queries';
import { sinceArabic } from '@/lib/format';
import { Star } from './icons';

function Stars({ n }: { n: number }) {
  return (
    <span className="stars-row" aria-label={`${n} من 5`}>
      {[1, 2, 3, 4, 5].map((i) => (
        <Star key={i} size={13} className={i <= n ? undefined : 'off'} />
      ))}
    </span>
  );
}

export function Reviews({ reviews }: { reviews: Review[] }) {
  if (reviews.length === 0) {
    return <p className="lede">لسه مفيش آراء مكتوبة. كن أول واحد.</p>;
  }
  return (
    <div className="card">
      {reviews.map((r) => (
        <div className="review" key={r.id}>
          <div className="top">
            <Stars n={r.stars} />
            <span className="who">{r.author_name ?? 'من غير اسم'}</span>
            <span className="when">{sinceArabic(r.created_at)}</span>
          </div>
          <p>{r.comment}</p>
        </div>
      ))}
    </div>
  );
}
