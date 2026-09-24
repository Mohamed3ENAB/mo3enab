import Link from 'next/link';
import { Home, Board, Money, Shield } from './icons';

const ITEMS = [
  { href: '/', label: 'الدليل', Icon: Home, key: 'home' },
  { href: '/requests', label: 'الطلبات', Icon: Board, key: 'requests' },
  { href: '/prices', label: 'الأسعار', Icon: Money, key: 'prices' },
  { href: '/admin', label: 'الإدارة', Icon: Shield, key: 'admin' },
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
