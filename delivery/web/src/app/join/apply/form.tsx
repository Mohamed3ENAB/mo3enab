'use client';

import { useState, useTransition } from 'react';
import Link from 'next/link';
import { submitApplication } from '@/app/actions';
import {
  SERVICE_LABELS, SERVICE_ORDER, PLACE_LABELS, PLACE_ORDER, type Zone,
} from '@/lib/types';
import { PhotoField } from '@/components/PhotoField';
import { Check } from '@/components/icons';

export function ApplyForm({ kind, zones }: { kind: 'driver' | 'place'; zones: Zone[] }) {
  const [done, setDone] = useState(false);
  const [msg, setMsg] = useState<string | null>(null);
  const [pending, start] = useTransition();

  if (done) {
    return (
      <div className="empty pop">
        <div className="ill">
          <Check size={26} />
        </div>
        <h3>طلبك وصلنا</h3>
        <p>هنراجعه ونكلمك على الرقم اللي كتبته. لو ورقك تمام، هتلاقي نفسك في الدليل.</p>
        <Link className="btn call wide" href="/">
          رجوع للدليل
        </Link>
      </div>
    );
  }

  return (
    <form
      action={(fd) =>
        start(async () => {
          const r = await submitApplication(fd);
          if (r.ok) setDone(true);
          else setMsg(r.message ?? 'مقدرناش نبعت الطلب. جرّب تاني.');
        })
      }
    >
      <input type="hidden" name="kind" value={kind} />
      {msg ? <p className="msg bad">{msg}</p> : null}

      <PhotoField
        label={kind === 'driver' ? 'صورتك' : 'صورة المحل'}
        hint="اختياري — بتظهر جنب اسمك في الدليل"
      />

      <label className="field">
        <span>{kind === 'driver' ? 'اسمك' : 'اسم المحل'}</span>
        <input name="name" required maxLength={80} />
      </label>

      <label className="field">
        <span>التليفون</span>
        <input name="phone" type="tel" inputMode="tel" required placeholder="01xxxxxxxxx" />
      </label>

      <label className="field">
        <span>واتساب (لو مختلف)</span>
        <input name="whatsapp" type="tel" inputMode="tel" placeholder="01xxxxxxxxx" />
      </label>

      <label className="field">
        <span>القرية</span>
        <select name="zone_id" required defaultValue="">
          <option value="" disabled>
            اختار
          </option>
          {zones.map((z) => (
            <option key={z.id} value={z.id}>
              {z.name_ar}
            </option>
          ))}
        </select>
      </label>

      {kind === 'driver' ? (
        <>
          <div className="field">
            <span>هتشتغل إيه؟</span>
            <div className="checks">
              {SERVICE_ORDER.map((k) => (
                <label key={k}>
                  <input type="checkbox" name="services" value={k} />
                  {SERVICE_LABELS[k]}
                </label>
              ))}
            </div>
          </div>
          <label className="field">
            <span>المركبة</span>
            <input name="vehicle_note" maxLength={60} placeholder="موتوسيكل / عجلة / توك توك" />
          </label>
        </>
      ) : (
        <>
          <label className="field">
            <span>تصنيف المحل</span>
            <select name="category" required defaultValue="">
              <option value="" disabled>
                اختار
              </option>
              {PLACE_ORDER.map((c) => (
                <option key={c} value={c}>
                  {PLACE_LABELS[c]}
                </option>
              ))}
            </select>
          </label>
          <label className="field">
            <span>مكان المحل</span>
            <input name="address_note" maxLength={120} placeholder="جنب الجامع الكبير" />
          </label>
          <label className="field">
            <span>المواعيد</span>
            <input name="hours_note" maxLength={60} placeholder="من 10ص لـ 12 بالليل" />
          </label>
        </>
      )}

      <label className="field">
        <span>حاجة تحب تقولها؟ (اختياري)</span>
        <textarea name="note" maxLength={300} />
      </label>

      <label className="checks" style={{ marginBottom: 18 }}>
        <label>
          <input type="checkbox" name="consent" value="1" required />
          موافق على نشر اسمي ورقمي في الدليل
        </label>
      </label>

      <button className="btn call wide" type="submit" disabled={pending}>
        {pending ? 'بنبعت…' : 'ابعت الطلب'}
      </button>
    </form>
  );
}
