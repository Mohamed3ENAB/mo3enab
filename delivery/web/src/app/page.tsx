import Link from 'next/link';
import { getProviders, getZones } from '@/lib/queries';
import { SERVICE_LABELS, SERVICE_ORDER, type ServiceKind } from '@/lib/types';
import { ProviderRow } from '@/components/ProviderRow';
import { TopBar } from '@/components/TopBar';
import { Foot } from '@/components/Foot';

export const dynamic = 'force-dynamic'; // التوفر لازم يبقى لحظي

type Search = { [k: string]: string | string[] | undefined };

function one(v: string | string[] | undefined) {
  return Array.isArray(v) ? v[0] : v;
}

export default async function Home({ searchParams }: { searchParams: Promise<Search> }) {
  const sp = await searchParams;
  const zoneParam = one(sp.zone);
  const kindParam = one(sp.kind) as ServiceKind | undefined;

  const zones = await getZones();
  const zoneId = zoneParam && /^\d+$/.test(zoneParam) ? Number(zoneParam) : undefined;
  const kind = kindParam && SERVICE_ORDER.includes(kindParam) ? kindParam : undefined;

  const all = await getProviders({ zoneId, kind });
  const live = all.filter((p) => p.live);
  const off = all.filter((p) => !p.live);

  const zoneName = zones.find((z) => z.id === zoneId)?.name_ar;
  const where = zoneName ? `في ${zoneName}` : 'في القرى الخمسة';

  const qs = (patch: Record<string, string | undefined>) => {
    const u = new URLSearchParams();
    const next = { zone: zoneParam, kind: kindParam, ...patch };
    if (next.zone) u.set('zone', next.zone);
    if (next.kind) u.set('kind', next.kind);
    const s = u.toString();
    return s ? `/?${s}` : '/';
  };

  return (
    <>
      <TopBar current="home" />
      <main className="wrap">
        <h1 className={`answer${live.length === 0 ? ' none' : ''}`}>
          {live.length === 0 ? (
            <>مفيش حد متاح دلوقتي {where}</>
          ) : live.length === 1 ? (
            <>
              فيه <span className="n">سائق واحد</span> متاح دلوقتي {where}
            </>
          ) : live.length === 2 ? (
            <>
              فيه <span className="n">سائقين</span> متاحين دلوقتي {where}
            </>
          ) : (
            <>
              فيه <span className="n">{live.length}</span> {live.length <= 10 ? 'سواقين' : 'سائق'}{' '}
              متاحين دلوقتي {where}
            </>
          )}
          <small>اضغط اتصال وكلّمه على طول. مفيش عمولة ومفيش وسيط.</small>
        </h1>

        <div className="zonebar" role="group" aria-label="القرية">
          <Link className="chip" href={qs({ zone: undefined })} aria-pressed={!zoneId}>
            كل القرى
          </Link>
          {zones.map((z) => (
            <Link
              key={z.id}
              className="chip"
              href={qs({ zone: String(z.id) })}
              aria-pressed={zoneId === z.id}
            >
              {z.name_ar}
            </Link>
          ))}
        </div>

        <div className="filters" role="group" aria-label="نوع الخدمة">
          <Link className="chip" href={qs({ kind: undefined })} aria-pressed={!kind}>
            كل الخدمات
          </Link>
          {SERVICE_ORDER.map((k) => (
            <Link key={k} className="chip" href={qs({ kind: k })} aria-pressed={kind === k}>
              {SERVICE_LABELS[k]}
            </Link>
          ))}
        </div>

        {live.length > 0 ? (
          <div className="rows">
            {live.map((p) => (
              <ProviderRow key={p.id} p={p} />
            ))}
          </div>
        ) : (
          <div className="empty">
            <p>مفيش حد فاتح دلوقتي.</p>
            <p className="hint">
              جرّب قرية جنبك، أو اكتب طلبك في لوحة الطلبات وكل السواقين هيشوفوه.
            </p>
            <div className="actions">
              <Link className="btn" href="/requests">
                اكتب طلبك في اللوحة
              </Link>
            </div>
          </div>
        )}

        {off.length > 0 ? (
          <details className="offline">
            <summary>
              مش متاحين دلوقتي ({off.length})
            </summary>
            <div className="rows">
              {off.map((p) => (
                <ProviderRow key={p.id} p={p} />
              ))}
            </div>
          </details>
        ) : null}

        {all.length === 0 && (zoneId || kind) ? (
          <div className="empty">
            <p>مفيش سواقين مسجلين في الاختيار ده.</p>
            <p className="hint">شيل الفلتر وشوف كل السواقين.</p>
            <div className="actions">
              <Link className="btn ghost" href="/">
                اعرض الكل
              </Link>
            </div>
          </div>
        ) : null}

        <Foot />
      </main>
    </>
  );
}
