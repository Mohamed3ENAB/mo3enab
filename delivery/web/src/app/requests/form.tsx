'use client';

import { useState, useTransition } from 'react';
import { postRequest } from '@/app/actions';
import Link from 'next/link';
import { SERVICE_LABELS, SERVICE_ORDER, type Zone } from '@/lib/types';
import { Chat, Alert } from '@/components/icons';

export function RequestForm({ zones }: { zones: Zone[] }) {
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null);
  const [thread, setThread] = useState<string | null>(null);
  // الإخفاء هو الافتراضي: الرقم على صفحة مفتوحة مش حاجة تتساب بالغلط
  const [hide, setHide] = useState(true);
  const [pending, start] = useTransition();

  function onSubmit(formData: FormData) {
    start(async () => {
      const res = await postRequest(formData);
      if (res.ok) {
        setMsg({ ok: true, text: 'اتنشر. السواقين هيشوفوه دلوقتي.' });
        setThread(res.threadToken ?? null);
      } else {
        setMsg({ ok: false, text: res.message ?? 'مقدرناش ننشر الطلب. جرّب تاني.' });
      }
    });
  }

  if (thread) {
    return (
      <div>
        <p className="msg ok">طلبك اتنشر.</p>
        <Link className="btn call wide" href={`/t/${thread}`} style={{ marginBottom: 12 }}>
          <Chat size={19} /> افتح محادثة طلبك
        </Link>
        <div className="note warn">
          <Alert className="ic" />
          <span>
            <strong>احفظ اللينك ده.</strong> ده الطريقة الوحيدة ترجع للمحادثة، وأي حد معاه
            يقدر يقراها ويرد باسمك.
          </span>
        </div>
      </div>
    );
  }

  return (
    <form action={onSubmit}>
      {msg ? <p className={`msg ${msg.ok ? 'ok' : 'bad'}`}>{msg.text}</p> : null}

      <label className="field">
        <span>محتاج إيه؟</span>
        <textarea
          name="body"
          required
          maxLength={500}
          placeholder="مثال: محتاج حد يجيبلي دوا من صيدلية في المحلة النهاردة بالليل"
        />
      </label>

      <label className="field">
        <span>رقم موبايلك</span>
        <input name="contact_phone" type="tel" inputMode="tel" required placeholder="01xxxxxxxxx" />
      </label>

      <div className="checks" style={{ marginBottom: 16 }}>
        <label>
          <input type="checkbox" name="hide_phone" value="1" defaultChecked={hide}
            onChange={(e) => setHide(e.target.checked)} />
          اخفي رقمي
        </label>
      </div>
      <p className="lede" style={{ marginTop: -8, fontSize: 13.5 }}>
        {hide
          ? 'رقمك هيظهر ناقص كده 0101••••78، والسواقين هيكلموك من جوه التطبيق.'
          : 'رقمك هيظهر كامل لأي حد يفتح الصفحة، وأي سائق يقدر يتصل بيك على طول.'}
      </p>

      <label className="field">
        <span>القرية</span>
        <select name="zone_id" defaultValue="">
          <option value="">مش مهم</option>
          {zones.map((z) => (
            <option key={z.id} value={z.id}>
              {z.name_ar}
            </option>
          ))}
        </select>
      </label>

      <label className="field">
        <span>نوع الخدمة</span>
        <select name="kind" defaultValue="">
          <option value="">مش مهم</option>
          {SERVICE_ORDER.map((k) => (
            <option key={k} value={k}>
              {SERVICE_LABELS[k]}
            </option>
          ))}
        </select>
      </label>

      <button className="btn wide" type="submit" disabled={pending}>
        {pending ? 'بننشر…' : 'انشر الطلب'}
      </button>
    </form>
  );
}
