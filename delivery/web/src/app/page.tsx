import Link from 'next/link';
import { getProviders, getZones } from '@/lib/queries';
import { Directory } from '@/components/Directory';
import { BottomNav } from '@/components/BottomNav';
import { Foot } from '@/components/Foot';
import { Logo, Pin, Store, Bicycle, Chevron } from '@/components/icons';

export const dynamic = 'force-dynamic'; // التوفر لازم يبقى لحظي

export default async function Home() {
  const [providers, zones] = await Promise.all([getProviders({}), getZones()]);
  const liveCount = providers.filter((p) => p.live).length;

  return (
    <div className="app">
      <header className="hero">
        <div className="hero-top">
          <div className="hero-brand">
            <span className="hero-logo">
              <Logo />
            </span>
            في السكة
          </div>
          <span className="zonepick">
            <Pin /> القيصرية
          </span>
        </div>

        <h1>
          {liveCount === 0
            ? 'مفيش حد متاح دلوقتي'
            : liveCount === 1
              ? 'سائق واحد متاح دلوقتي'
              : liveCount === 2
                ? 'سائقين متاحين دلوقتي'
                : `${liveCount} سواقين متاحين دلوقتي`}
        </h1>
        <p>اضغط اتصال وكلّمه على طول — من غير عمولة ولا وسيط.</p>
      </header>

      <main className="wrap">
        <Directory providers={providers} zones={zones} />

        <section className="sec">
          <div className="sec-head">
            <h2>كمان</h2>
          </div>
          <div className="cards">
            <Link className="tile" href="/places">
              <span className="bubble b-restaurant">
                <Store size={22} />
              </span>
              <span className="t">
                <b>المحلات والمطاعم</b>
                <span>اطلب من أي محل، وسائق يستلم منهم</span>
              </span>
              <Chevron className="go" />
            </Link>
            <Link className="tile" href="/join">
              <span className="bubble b-bicycle">
                <Bicycle size={22} />
              </span>
              <span className="t">
                <b>اشتغل معانا</b>
                <span>عندك عجلة؟ ابدأ من 5 جنيه للطلب</span>
              </span>
              <Chevron className="go" />
            </Link>
          </div>
        </section>

        <Foot />
      </main>

      <BottomNav current="home" />
    </div>
  );
}
