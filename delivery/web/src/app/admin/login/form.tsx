'use client';

import { useState, useTransition } from 'react';
import { login } from '@/app/actions';

export function LoginForm() {
  const [msg, setMsg] = useState<string | null>(null);
  const [pending, start] = useTransition();

  return (
    <form
      action={(fd) =>
        start(async () => {
          const r = await login(fd);
          if (r && !r.ok) setMsg(r.message ?? 'مقدرناش نسجل دخولك.');
        })
      }
    >
      {msg ? <p className="msg bad">{msg}</p> : null}
      <label className="field">
        <span>كلمة السر</span>
        <input name="password" type="password" required autoComplete="current-password" />
      </label>
      <button className="btn wide" type="submit" disabled={pending}>
        {pending ? 'بندخل…' : 'ادخل'}
      </button>
    </form>
  );
}
