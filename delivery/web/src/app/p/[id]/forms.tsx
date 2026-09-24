'use client';

import { useState, useTransition } from 'react';
import { rateProvider, reportProvider } from '@/app/actions';
import { REPORT_REASONS } from '@/lib/types';
import { Star, Alert } from '@/components/icons';

export function FeedbackForms({ providerId, name }: { providerId: string; name: string }) {
  const [rateMsg, setRateMsg] = useState<{ ok: boolean; text: string } | null>(null);
  const [repMsg, setRepMsg] = useState<{ ok: boolean; text: string } | null>(null);
  const [pending, start] = useTransition();

  return (
    <>
      <h2 style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
        <Star size={17} /> قيّم {name.split(' ')[0]}
      </h2>
      <form
        className="card"
        action={(fd) =>
          start(async () => {
            const r = await rateProvider(fd);
            setRateMsg(
              r.ok
                ? { ok: true, text: 'شكرًا. تقييمك اتسجل.' }
                : { ok: false, text: r.message ?? 'مقدرناش نسجل التقييم.' }
            );
          })
        }
      >
        <input type="hidden" name="provider_id" value={providerId} />
        {rateMsg ? <p className={`msg ${rateMsg.ok ? 'ok' : 'bad'}`}>{rateMsg.text}</p> : null}

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
          <span>تحب تضيف حاجة؟ (اختياري)</span>
          <textarea name="comment" maxLength={300} placeholder="جه بسرعة والمعاملة كويسة" />
        </label>

        <button className="btn wide" type="submit" disabled={pending}>
          ابعت التقييم
        </button>
      </form>

      <h2 style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
        <Alert size={17} /> بلّغ عن مشكلة
      </h2>
      <p className="lede">البلاغ بيوصل للإدارة بس، ومحدش تاني بيشوفه.</p>
      <form
        className="card"
        action={(fd) =>
          start(async () => {
            const r = await reportProvider(fd);
            setRepMsg(
              r.ok
                ? { ok: true, text: 'البلاغ وصل. هنراجعه.' }
                : { ok: false, text: r.message ?? 'مقدرناش نسجل البلاغ.' }
            );
          })
        }
      >
        <input type="hidden" name="provider_id" value={providerId} />
        {repMsg ? <p className={`msg ${repMsg.ok ? 'ok' : 'bad'}`}>{repMsg.text}</p> : null}

        <label className="field">
          <span>إيه اللي حصل؟</span>
          <select name="reason" defaultValue="rude">
            {Object.entries(REPORT_REASONS).map(([k, v]) => (
              <option key={k} value={k}>
                {v}
              </option>
            ))}
          </select>
        </label>

        <label className="field">
          <span>تفاصيل (اختياري)</span>
          <textarea name="details" maxLength={500} />
        </label>

        <label className="field">
          <span>رقمك لو محتاجين نكلمك (اختياري)</span>
          <input name="reporter_phone" type="tel" inputMode="tel" placeholder="01xxxxxxxxx" />
        </label>

        <button className="btn danger wide" type="submit" disabled={pending}>
          ابعت البلاغ
        </button>
      </form>
    </>
  );
}
