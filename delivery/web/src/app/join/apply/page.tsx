import Link from 'next/link';
import { getZones } from '@/lib/queries';
import { BottomNav } from '@/components/BottomNav';
import { ApplyForm } from './form';
import { UserPlus, Info } from '@/components/icons';

export const dynamic = 'force-dynamic';

type Search = { [k: string]: string | string[] | undefined };

export default async function ApplyPage({ searchParams }: { searchParams: Promise<Search> }) {
  const sp = await searchParams;
  const raw = Array.isArray(sp.kind) ? sp.kind[0] : sp.kind;
  const kind: 'driver' | 'place' = raw === 'place' ? 'place' : 'driver';
  const zones = await getZones();

  return (
    <div className="app">
      <header className="hero">
        <div className="hero-top">
          <div className="hero-brand">
            <span className="hero-logo">
              <UserPlus size={20} />
            </span>
            سجّل نفسك
          </div>
        </div>
        <h1>{kind === 'driver' ? 'عايز تشتغل سائق؟' : 'عندك محل أو مطعم؟'}</h1>
        <p>املا البيانات وهنراجعها ونكلمك.</p>
      </header>

      <main className="wrap page" style={{ paddingTop: 22 }}>
        <div className="zones" style={{ marginBottom: 18 }}>
          <Link className="zchip" href="/join/apply?kind=driver" aria-pressed={kind === 'driver'}>
            سائق
          </Link>
          <Link className="zchip" href="/join/apply?kind=place" aria-pressed={kind === 'place'}>
            محل أو مطعم
          </Link>
        </div>

        <div className="note">
          <Info className="ic" />
          <span>
            طلبك <strong>مش بيظهر في الدليل على طول</strong>. بنراجعه الأول ونشوف ورقك،
            وبعدها بنفعّله. ده اللي بيخلي الناس تثق في القايمة.
          </span>
        </div>

        <ApplyForm kind={kind} zones={zones} />
      </main>

      <BottomNav />
    </div>
  );
}
