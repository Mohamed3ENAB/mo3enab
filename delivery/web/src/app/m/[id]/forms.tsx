'use client';

import { useState, useTransition } from 'react';
import { rateTarget } from '@/app/actions';
import { Star } from '@/components/icons';

export function PlaceFeedback({ placeId, name }: { placeId: string; name: string }) {
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null);
  const [pending, start] = useTransition();

  return (
    <>
      <h2 style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
        <Star size={17} /> قيّم {name}
      </h2>
      <form
        className="card"
        action={(fd) =>
          start(async () => {
            const r = await rateTarget(fd);
            setMsg(
              r.ok
                ? { ok: true, text: 'شكرًا. رأيك اتسجل.' }
                : { ok: false, text: r.message ?? 'مقدرناش نسجل التقييم.' }
            );
          })
        }
      >
        <input type="hidden" name="place_id" value={placeId} />
        {msg ? <p className={`msg ${msg.ok ? 'ok' : 'bad'}`}>{msg.text}</p> : null}

        <label className="field">
          <span>التقييم</span>
          <select name="stars" defaultValue="5">
            <option value="5">٥ — ممتاز</option>
            <option value="4">٤ — كويس</option>
            <option value="3">٣ — عادي</option>
            <option value="2">٢ — ضعيف</option>
            <option value="1">١ — وحش</option>
          </select>
        </label>

        <label className="field">
          <span>رأيك (اختياري)</span>
          <textarea name="comment" maxLength={300} placeholder="الأكل حلو والأسعار معقولة" />
        </label>

        <label className="field">
          <span>اسمك (اختياري)</span>
          <input name="author_name" maxLength={40} />
        </label>

        <button className="btn call wide" type="submit" disabled={pending}>
          ابعت رأيك
        </button>
      </form>
    </>
  );
}
