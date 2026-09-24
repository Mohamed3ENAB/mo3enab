import Link from 'next/link';
import { Home, Store, Board, Money } from './icons';

// الإدارة مش هنا عن قصد — الناس العادية مش محتاجاها. اللينك في التذييل.
const ITEMS = [
  { href: '/', label: 'السواقين', Icon: Home, key: 'home' },
  { href: '/places', label: 'المحلات', Icon: Store, key: 'places' },
  { href: '/requests', label: 'الطلبات', Icon: Board, key: 'requests' },
  { href: '/prices', label: 'الأسعار', Icon: Money, key: 'prices' },
] as const;

export function BottomNav({ current }: { current?: string }) {
  return (
    <nav className="nav" aria-label="التنقل">
      <ul>
        {ITEMS.map(({ href, label, Icon, key }) => (
          <li key={key}>
            <Link href={href} aria-current={current === key ? 'page' : undefined}>
              <Icon className="ic" size={23} />
              {label}
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  );
}
