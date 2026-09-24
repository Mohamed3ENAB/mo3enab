import { getPlaces, getProviders, getZones } from '@/lib/queries';
import { Places } from '@/components/Places';
import { BottomNav } from '@/components/BottomNav';
import { Foot } from '@/components/Foot';
import { Store, Pin } from '@/components/icons';

export const dynamic = 'force-dynamic';

export default async function PlacesPage() {
  const [places, drivers, zones] = await Promise.all([
    getPlaces(),
    getProviders({}),
    getZones(),
  ]);

  return (
    <div className="app">
      <header className="hero">
        <div className="hero-top">
          <div className="hero-brand">
            <span className="hero-logo">
              <Store size={20} />
            </span>
            المحلات والمطاعم
          </div>
          <span className="zonepick">
            <Pin /> القيصرية
          </span>
        </div>
        <h1>اطلب من أي محل في البلد</h1>
        <p>كلّم المحل واطلب، وبعدين كلّم سائق يستلم منهم.</p>
      </header>

      <main className="wrap">
        <Places places={places} drivers={drivers} zones={zones} />
        <Foot />
      </main>

      <BottomNav current="places" />
    </div>
  );
}
