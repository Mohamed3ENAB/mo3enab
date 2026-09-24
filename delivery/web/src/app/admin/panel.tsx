'use client';

import { useState, useTransition } from 'react';
import {
  addProvider, setVerified, setActive, hideRequest, handleReport, logout,
  addPlace, setPlaceActive, approveApplication, rejectApplication,
} from '@/app/actions';
import {
  SERVICE_LABELS, SERVICE_ORDER, PLACE_LABELS, PLACE_ORDER, type Zone,
} from '@/lib/types';
import { BottomNav } from '@/components/BottomNav';
import { Avatar } from '@/components/Avatar';
import { Alert, Check } from '@/components/icons';
import { PhotoField } from '@/components/PhotoField';

type P = {
  id: string; display_name: string; phone: string; zone_name: string;
  servicesLabel: string; is_verified: boolean; is_active: boolean;
  is_available: boolean; since: string; token: string | null; openReports: number;
  photo_id: string | null;
};
type R = {
  id: string; provider_name: string; reasonLabel: string; details: string | null;
  reporter_phone: string | null; since: string; handled_at: string | null;
};
type Q = { id: string; body: string; contact_phone: string; is_hidden: boolean; since: string };
type A = {
  id: string; kind: 'driver' | 'place'; status: string; name: string; phone: string;
  whatsapp: string | null; zone_name: string; note: string | null; photo_id: string | null;
  servicesLabel: string; vehicle_note: string | null; categoryLabel: string | null;
  address_note: string | null; hours_note: string | null; since: string;
};
type S = {
  drivers: number; live: number; places: number;
  pending: number; openReports: number; openRequests: number;
};
type L = {
  id: string; name_ar: string; categoryLabel: string; zone_name: string;
  phone: string | null; whatsapp: string | null; hours_note: string | null; is_active: boolean;
};

export function AdminPanel({
  zones, providers, reports, requests, places, applications, stats,
}: {
  zones: Zone[]; providers: P[]; reports: R[]; requests: Q[];
  places: L[]; applications: A[]; stats: S;
}) {
  const [tab, setTab] = useState<
    'list' | 'add' | 'places' | 'addPlace' | 'apps' | 'reports' | 'requests'
  >(stats.pending > 0 ? 'apps' : 'list');
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null);
  const [shown, setShown] = useState<string | null>(null);
  const [pending, start] = useTransition();

  const openReports = reports.filter((r) => !r.handled_at).length;

  return (
    <div className="app">
    <main className="wrap page">
      <h1>لوحة الإدارة</h1>

      <div className="stats">
        <div className={`stat${stats.pending > 0 ? ' alert' : ''}`}>
          <b>{stats.pending}</b>
          <span>طلب مستني</span>
        </div>
        <div className="stat good">
          <b>{stats.live}</b>
          <span>سائق متاح</span>
        </div>
        <div className="stat">
          <b>{stats.drivers}</b>
          <span>إجمالي السواقين</span>
        </div>
        <div className="stat">
          <b>{stats.places}</b>
          <span>محل</span>
        </div>
        <div className={`stat${stats.openReports > 0 ? ' alert' : ''}`}>
          <b>{stats.openReports}</b>
          <span>بلاغ مفتوح</span>
        </div>
        <div className="stat">
          <b>{stats.openRequests}</b>
          <span>طلب على اللوحة</span>
        </div>
      </div>

      <div className="tabs">
        <button className="zchip" aria-pressed={tab === 'apps'} onClick={() => setTab('apps')}>
          طلبات الانضمام{stats.pending > 0 ? ` (${stats.pending})` : ''}
        </button>
        <button className="zchip" aria-pressed={tab === 'list'} onClick={() => setTab('list')}>
          السواقين ({providers.length})
        </button>
        <button className="zchip" aria-pressed={tab === 'add'} onClick={() => setTab('add')}>
          إضافة سائق
        </button>
        <button className="zchip" aria-pressed={tab === 'places'} onClick={() => setTab('places')}>
          المحلات ({places.length})
        </button>
        <button className="zchip" aria-pressed={tab === 'addPlace'} onClick={() => setTab('addPlace')}>
          إضافة محل
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
          <PhotoField label="صورة السائق" hint="اختياري" />
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
            <div style={{ display: 'flex', gap: 11, alignItems: 'flex-start' }}>
              <Avatar name={p.display_name} photoId={p.photo_id} size={40} />
              <div style={{ flex: 1, minWidth: 0 }}>
              <div className="who">
                {p.display_name}{' '}
                {p.is_verified ? <span className="pill on">موثّق</span> : null}
                {!p.is_active ? <span className="pill warn">موقوف</span> : null}
                {p.openReports > 0 ? <span className="pill warn">{p.openReports} بلاغ</span> : null}
              </div>
              <div className="sub">
                {p.phone} · {p.zone_name} · {p.servicesLabel} ·{' '}
                {p.is_available ? `فاتح ${p.since}` : p.since === 'لسه مافتحش' ? p.since : `قافل ${p.since}`}
              </div>
              {shown === p.id && p.token ? (
                <div className="tokenbox">/d/{p.token}</div>
              ) : null}
              </div>
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

      {tab === 'apps' ? (
        applications.length === 0 ? (
          <p className="lede">مفيش طلبات انضمام.</p>
        ) : applications.map((a) => (
          <div className="admin-row" key={a.id}>
            <div style={{ display: 'flex', gap: 12, alignItems: 'flex-start' }}>
              <Avatar name={a.name} photoId={a.photo_id} size={46} />
              <div style={{ flex: 1, minWidth: 0 }}>
                <div className="who">
                  {a.name}
                  <span className="pill info">{a.kind === 'driver' ? 'سائق' : 'محل'}</span>
                  {a.status === 'pending' ? <span className="pill warn">مستني</span> : null}
                  {a.status === 'approved' ? <span className="pill on">اتقبل</span> : null}
                </div>
                <div className="sub">
                  {[a.phone,
                    a.whatsapp && a.whatsapp !== a.phone ? `واتساب ${a.whatsapp}` : null,
                    a.zone_name,
                    a.kind === 'driver' ? a.vehicle_note : a.categoryLabel,
                    a.kind === 'driver' ? a.servicesLabel : a.address_note,
                    a.hours_note, a.since,
                  ].filter(Boolean).join(' · ')}
                </div>
                {a.note ? <div className="sub">«{a.note}»</div> : null}
              </div>
            </div>
            {a.status === 'pending' ? (
              <div className="actions">
                <button className="btn call" style={{ flex: 1 }} disabled={pending}
                  onClick={() => start(async () => {
                    const r = await approveApplication(a.id);
                    setMsg(r.ok
                      ? { ok: true, text: `${a.name} اتفعّل في الدليل.` }
                      : { ok: false, text: r.message ?? 'مقدرناش نفعّله.' });
                  })}>
                  <Check size={15} /> اقبل وفعّل
                </button>
                <button className="btn quiet danger" disabled={pending}
                  onClick={() => start(async () => {
                    const reason = window.prompt('سبب الرفض (اختياري)') ?? '';
                    await rejectApplication(a.id, reason);
                    setMsg({ ok: true, text: 'الطلب اترفض.' });
                  })}>
                  ارفض
                </button>
              </div>
            ) : null}
          </div>
        ))
      ) : null}

      {tab === 'addPlace' ? (
        <form
          action={(fd) =>
            start(async () => {
              const r = await addPlace(fd);
              setMsg(r.ok
                ? { ok: true, text: 'المحل اتضاف.' }
                : { ok: false, text: r.message ?? 'مقدرناش نضيفه.' });
              if (r.ok) setTab('places');
            })
          }
        >
          <div className="note">
            <Alert className="ic" />
            <span>
              رقم المحل رقم تجاري معلن عادةً، بس برضه استأذن صاحبه قبل ما تنشره — ده بيخليه
              متعاون معاك بعدين.
            </span>
          </div>
          <PhotoField label="صورة المحل" hint="اختياري" />
          <label className="field"><span>اسم المحل</span><input name="name_ar" required /></label>
          <label className="field"><span>التصنيف</span>
            <select name="category" required defaultValue="">
              <option value="" disabled>اختار</option>
              {PLACE_ORDER.map((c) => <option key={c} value={c}>{PLACE_LABELS[c]}</option>)}
            </select></label>
          <label className="field"><span>القرية</span>
            <select name="zone_id" required defaultValue="">
              <option value="" disabled>اختار</option>
              {zones.map((z) => <option key={z.id} value={z.id}>{z.name_ar}</option>)}
            </select></label>
          <label className="field"><span>التليفون</span>
            <input name="phone" type="tel" inputMode="tel" placeholder="01xxxxxxxxx أو أرضي" /></label>
          <label className="field"><span>واتساب (لو فيه)</span>
            <input name="whatsapp" type="tel" inputMode="tel" placeholder="01xxxxxxxxx" /></label>
          <label className="field"><span>مكانه</span>
            <input name="address_note" placeholder="جنب الجامع الكبير" /></label>
          <label className="field"><span>المواعيد</span>
            <input name="hours_note" placeholder="من 10ص لـ 12 بالليل" /></label>
          <label className="field"><span>ملاحظة</span>
            <input name="note" placeholder="بيوصّل بنفسه للقرية" /></label>
          <button className="btn wide" type="submit" disabled={pending}>
            {pending ? 'بنضيف…' : 'ضيف المحل'}
          </button>
        </form>
      ) : null}

      {tab === 'places' ? (
        places.length === 0 ? (
          <p className="lede">مفيش محلات لسه. ابدأ من «إضافة محل».</p>
        ) : places.map((pl) => (
          <div className="admin-row" key={pl.id}>
            <div>
              <div className="who">
                {pl.name_ar}
                <span className="pill info">{pl.categoryLabel}</span>
                {!pl.is_active ? <span className="pill warn">مخفي</span> : null}
              </div>
              <div className="sub">
                {[pl.phone, pl.whatsapp && pl.whatsapp !== pl.phone ? `واتساب ${pl.whatsapp}` : null,
                  pl.zone_name, pl.hours_note].filter(Boolean).join(' · ')}
              </div>
            </div>
            <div className="actions">
              <button className={`btn quiet${pl.is_active ? ' danger' : ''}`} disabled={pending}
                onClick={() => start(() => setPlaceActive(pl.id, !pl.is_active))}>
                {pl.is_active ? 'إخفاء' : 'رجّعه'}
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
