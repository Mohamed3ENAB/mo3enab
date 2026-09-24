import Link from 'next/link';

export function TopBar({ current }: { current?: 'home' | 'requests' | 'prices' }) {
  return (
    <header className="topbar">
      <Link href="/" className="brand">
        في السكة
      </Link>
      <nav>
        <Link href="/requests" aria-current={current === 'requests' ? 'page' : undefined}>
          الطلبات
        </Link>
        <Link href="/prices" aria-current={current === 'prices' ? 'page' : undefined}>
          الأسعار
        </Link>
      </nav>
    </header>
  );
}
