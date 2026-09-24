'use client';

import { useState, useTransition } from 'react';
import {
  addProvider, setVerified, setActive, hideRequest, handleReport, logout,
} from '@/app/actions';
import { SERVICE_LABELS, SERVICE_ORDER, type Zone } from '@/lib/types';
import { BottomNav } from '@/components/BottomNav';
import { Alert } from '@/components/icons';

type P = {
  id: string; display_name: string; phone: string; zone_name: string;
  servicesLabel: string; is_verified: boolean; is_active: boolean;
  is_available: boolean; since: string; token: string | null; openReports: number;
};
type R = {
  id: string; provider_name: string; reasonLabel: string; details: string | null;
  reporter_phone: string | null; since: string; handled_at: string | null;
};
type Q = { id: string; body: string; contact_phone: string; is_hidden: boolean; since: string };

export function AdminPanel({
  zones, providers, reports, requests,
}: { zones: Zone[]; providers: P[]; reports: R[]; requests: Q[] }) {
  const [tab, setTab] = useState<'list' | 'add' | 'reports' | 'requests'>('list');
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null);
  const [shown, setShown] = useState<string | null>(null);
  const [pending, start] = useTransition();

  const openReports = reports.filter((r) => !r.handled_at).length;

  return (
    <div className="app">
    <main className="wrap page">
      <h1>لوحة الإدارة</h1>

      <div className="tabs">
        <button className="zchip" aria-pressed={tab === 'list'} onClick={() => setTab('list')}>
          السواقين ({providers.length})
        </button>
        <button className="zchip" aria-pressed={tab === 'add'} onClick={() => setTab('add')}>
          إضافة سائق
        </button>
        <button className="zchip" aria-pressed={tab === 'reports'} onClick={() => setTab('reports')}>
          البلاغات{openReports > 0 ? ` (${openReports})` : ''}
        </button>
        <button className="zchip" aria-pressed={tab === 'requests'} onClick={() => setTab('requests')}>
          الطلبات
        </button>
      </div>

      {msg ? <p className={`msg ${msg.ok ? 'ok' : 'bad'}`}>{msg.text}</p> : null}

      {tab === 'add' ? (
        <form
          action={(fd) =>
            start(async () => {
              const r = await addProvider(fd);
              setMsg(r.ok
                ? { ok: true, text: 'اتضاف. افتح تبويب السواقين وانسخ لينكه.' }
                : { ok: false, text: r.message ?? 'مقدرناش نضيفه.' });
              if (r.ok) setTab('list');
            })
          }
        >
          <div className="note warn">
            <Alert className="ic" />
            <span>متضيفش حد قبل ما تشوف بطاقته ورخصته، وتاخد منه موافقة مكتوبة على نشر رقمه.</span>
          </div>
          <label className="field"><span>الاسم</span><input name="display_name" required /></label>
          <label className="field"><span>الموبايل</span>
            <input name="phone" type="tel" inputMode="tel" required placeholder="01xxxxxxxxx" /></label>
          <label className="field"><span>واتساب (لو مختلف)</span>
            <input name="whatsapp" type="tel" inputMode="tel" /></label>
          <label className="field"><span>القرية</span>
            <select name="zone_id" required defaultValue="">
              <option value="" disabled>اختار</option>
              {zones.map((z) => <option key={z.id} value={z.id}>{z.name_ar}</option>)}
            </select></label>
          <div className="field">
            <span>الخدمات</span>
            <div className="checks">
              {SERVICE_ORDER.map((k) => (
                <label key={k}><input type="checkbox" name="services" value={k} />{SERVICE_LABELS[k]}</label>
              ))}
            </div>
          </div>
          <label className="field"><span>المركبة</span>
            <input name="vehicle_note" placeholder="موتوسيكل / توك توك أزرق" /></label>
          <label className="field"><span>ملاحظة تظهر للناس</span>
            <input name="note" placeholder="بيشتغل من ٢ لـ ١٠" /></label>
          <button className="btn wide" type="submit" disabled={pending}>
            {pending ? 'بنضيف…' : 'ضيف السائق'}
          </button>
        </form>
      ) : null}

      {tab === 'list' ? (
        providers.length === 0 ? (
          <p className="lede">مفيش سواقين لسه. ابدأ من تبويب «إضافة سائق».</p>
        ) : providers.map((p) => (
          <div className="admin-row" key={p.id}>
            <div>
              <div className="who">
                {p.display_name}{' '}
                {p.is_verified ? <span className="pill on">موثّق</span> : null}
                {!p.is_active ? <span className="pill warn">موقوف</span> : null}
                {p.openReports > 0 ? <span className="pill warn">{p.openReports} بلاغ</span> : null}
              </div>
              <div className="sub">
                {p.phone} · {p.zone_name} · {p.servicesLabel} · {p.is_available ? 'فاتح' : 'قافل'} {p.since}
              </div>
              {shown === p.id && p.token ? (
                <div className="tokenbox">/d/{p.token}</div>
              ) : null}
            </div>
            <div className="actions">
              <button className="btn quiet" onClick={() => setShown(shown === p.id ? null : p.id)}>
                {shown === p.id ? 'إخفاء اللينك' : 'اللينك'}
              </button>
              <button className="btn quiet" disabled={pending}
                onClick={() => start(() => setVerified(p.id, !p.is_verified))}>
                {p.is_verified ? 'شيل التوثيق' : 'وثّق'}
              </button>
              <button className={`btn quiet${p.is_active ? ' danger' : ''}`} disabled={pending}
                onClick={() => start(() => setActive(p.id, !p.is_active))}>
                {p.is_active ? 'أوقف' : 'رجّع'}
              </button>
            </div>
          </div>
        ))
      ) : null}

      {tab === 'reports' ? (
        reports.length === 0 ? <p className="lede">مفيش بلاغات.</p> :
        reports.map((r) => (
          <div className="admin-row" key={r.id}>
            <div>
              <div className="who">
                {r.provider_name} — {r.reasonLabel}{' '}
                {r.handled_at ? <span className="pill on">اتعامل معاه</span> : null}
              </div>
              <div className="sub">
                {r.details ? `${r.details} · ` : ''}{r.reporter_phone ?? 'من غير رقم'} · {r.since}
              </div>
            </div>
            {!r.handled_at ? (
              <div className="actions">
                <button className="btn quiet" disabled={pending}
                  onClick={() => start(() => handleReport(r.id))}>علّم كمتعامَل معاه</button>
              </div>
            ) : null}
          </div>
        ))
      ) : null}

      {tab === 'requests' ? (
        requests.length === 0 ? <p className="lede">مفيش طلبات.</p> :
        requests.map((q) => (
          <div className="admin-row" key={q.id}>
            <div>
              <div className="who">
                {q.body.slice(0, 80)}{q.body.length > 80 ? '…' : ''}{' '}
                {q.is_hidden ? <span className="pill warn">مخفي</span> : null}
              </div>
              <div className="sub">{q.contact_phone} · {q.since}</div>
            </div>
            <div className="actions">
              <button className="btn quiet" disabled={pending}
                onClick={() => start(() => hideRequest(q.id, !q.is_hidden))}>
                {q.is_hidden ? 'رجّعه' : 'إخفاء'}
              </button>
            </div>
          </div>
        ))
      ) : null}

      <form action={logout} style={{ marginTop: 36 }}>
        <button className="btn ghost wide" type="submit">خروج</button>
      </form>
    </main>
    <BottomNav current="admin" />
    </div>
  );
}
