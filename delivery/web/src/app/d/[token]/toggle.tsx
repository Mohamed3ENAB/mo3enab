'use client';

import { useState, useTransition } from 'react';
import { setAvailability } from '@/app/actions';

export function AvailabilityToggle({
  token,
  initial,
  since,
}: {
  token: string;
  initial: boolean;
  since: string;
}) {
  const [on, setOn] = useState(initial);
  const [msg, setMsg] = useState<string | null>(null);
  const [justChanged, setJustChanged] = useState(false);
  const [pending, start] = useTransition();

  function flip() {
    const next = !on;
    start(async () => {
      const res = await setAvailability(token, next);
      if (res.ok) {
        setOn(next);
        setJustChanged(true);
        setMsg(null);
      } else {
        setMsg(res.message ?? 'مقدرناش نحفظ التغيير. جرّب تاني.');
      }
    });
  }

  return (
    <>
      {msg ? <p className="msg bad">{msg}</p> : null}

      <button
        type="button"
        className={`toggle ${on ? 'on' : 'off'}`}
        onClick={flip}
        disabled={pending}
        aria-pressed={on}
      >
        {pending ? 'بنحفظ…' : on ? 'أنا متاح' : 'مش متاح'}
        <small>{pending ? '' : on ? 'اضغط لما تخلص' : 'اضغط لما تبقى فاضي'}</small>
      </button>

      <p className="lede" style={{ marginTop: 14 }}>
        {justChanged
          ? on
            ? 'تمام. اسمك ظاهر دلوقتي في الدليل.'
            : 'تمام. اسمك اتشال من المتاحين.'
          : `آخر تحديث ${since}`}
      </p>
    </>
  );
}
