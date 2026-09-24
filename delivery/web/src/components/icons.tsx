/* أيقونات مرسومة بالإيد — من غير مكتبة خارجية.
   التوك توك مش موجود في أي مكتبة أيقونات، فاترسم هنا. */

type P = { size?: number; className?: string };

const base = (size: number) => ({
  width: size, height: size, viewBox: '0 0 24 24', fill: 'none',
  stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round' as const,
  strokeLinejoin: 'round' as const, 'aria-hidden': true,
});

export const Motorcycle = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <circle cx="5" cy="17" r="3.2" /><circle cx="19" cy="17" r="3.2" />
    <path d="M8.2 17h7.2l-4-6H7" /><path d="M14 8h3l2.4 6" /><path d="M6.4 11H4" />
  </svg>
);

export const TukTuk = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <circle cx="6" cy="18" r="2.4" /><circle cx="18" cy="18" r="2.4" />
    <path d="M8.4 18h7.2" />
    <path d="M6 15.6V11a5 5 0 0 1 5-5h3.2a5 5 0 0 1 5 5v4.6" />
    <path d="M6.4 9.6 4 8.4" /><path d="M10.8 6V4" />
  </svg>
);

export const Bicycle = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <circle cx="5.5" cy="17.5" r="3.5" /><circle cx="18.5" cy="17.5" r="3.5" />
    <path d="M5.5 17.5 9 8h4l3.2 9.5" /><path d="M9 8h6" /><path d="M13 8l-2.4 9.5" />
  </svg>
);

export const Box = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M20.5 7.8v8.4a1.6 1.6 0 0 1-.8 1.4l-6.9 3.9a1.6 1.6 0 0 1-1.6 0l-6.9-3.9a1.6 1.6 0 0 1-.8-1.4V7.8a1.6 1.6 0 0 1 .8-1.4l6.9-3.9a1.6 1.6 0 0 1 1.6 0l6.9 3.9a1.6 1.6 0 0 1 .8 1.4Z" />
    <path d="m3.7 7 8.3 4.7L20.3 7" /><path d="M12 21v-9.3" />
  </svg>
);

export const City = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M3 21h18" /><path d="M5 21V8l5-3v16" /><path d="M14 21V11l5 2.5V21" />
    <path d="M7.5 10.5v0M7.5 14v0M16.5 16v0" />
  </svg>
);

export const Grid = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <rect x="3" y="3" width="7.5" height="7.5" rx="2" />
    <rect x="13.5" y="3" width="7.5" height="7.5" rx="2" />
    <rect x="3" y="13.5" width="7.5" height="7.5" rx="2" />
    <rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2" />
  </svg>
);

export const Phone = ({ size = 20, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M21 16.9v2.6a1.8 1.8 0 0 1-2 1.8 17.6 17.6 0 0 1-7.7-2.7 17.4 17.4 0 0 1-5.3-5.3A17.6 17.6 0 0 1 3.3 5.5 1.8 1.8 0 0 1 5.1 3.5h2.6a1.8 1.8 0 0 1 1.8 1.6 11.4 11.4 0 0 0 .6 2.5 1.8 1.8 0 0 1-.4 1.9l-1.1 1.1a14.4 14.4 0 0 0 5.4 5.4l1.1-1.1a1.8 1.8 0 0 1 1.9-.4 11.4 11.4 0 0 0 2.5.6 1.8 1.8 0 0 1 1.6 1.8Z" />
  </svg>
);

export const WhatsApp = ({ size = 22, className }: P) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className={className}>
    <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5.1-1.3A10 10 0 1 0 12 2Zm5.4 14.1c-.2.6-1.3 1.2-1.8 1.2s-1.2.3-3.9-.9a13.4 13.4 0 0 1-5.3-5.3c-.5-.9-.9-1.9-.9-2.8s.5-1.5.8-1.8a.9.9 0 0 1 .6-.2h.5c.2 0 .4 0 .6.5l.8 2c.1.2 0 .4 0 .5l-.4.5-.3.3c-.1.2-.2.3 0 .6a9.4 9.4 0 0 0 1.7 2.1 8.5 8.5 0 0 0 2.4 1.5c.3.2.5.1.6 0l1-1.1c.2-.2.4-.2.6-.1l2 1c.2.1.4.2.4.3a2 2 0 0 1-.1.8Z" />
  </svg>
);

export const Star = ({ size = 13, className }: P) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className={className}>
    <path d="m12 2.6 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L2.6 9.4l6.5-.9Z" />
  </svg>
);

export const Check = ({ size = 13, className }: P) => (
  <svg {...base(size)} strokeWidth={3.4} className={className}><path d="m4.5 12.5 5 5 10-11" /></svg>
);

export const Search = ({ size = 21, className }: P) => (
  <svg {...base(size)} className={className}><circle cx="11" cy="11" r="7.5" /><path d="m20.5 20.5-4-4" /></svg>
);

export const Close = ({ size = 16, className }: P) => (
  <svg {...base(size)} strokeWidth={2.6} className={className}><path d="M18 6 6 18M6 6l12 12" /></svg>
);

export const Chevron = ({ size = 18, className }: P) => (
  <svg {...base(size)} className={className}><path d="m6 9 6 6 6-6" /></svg>
);

export const Pin = ({ size = 15, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M20 10.5c0 5.5-8 11.5-8 11.5s-8-6-8-11.5a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10.5" r="2.8" />
  </svg>
);

export const Home = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M3 10.5 12 3l9 7.5" /><path d="M5.5 9.3V20a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1V9.3" />
  </svg>
);

export const Board = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <rect x="3.5" y="4" width="17" height="17" rx="3" /><path d="M8 2.5v3M16 2.5v3M8 11h8M8 15.5h5" />
  </svg>
);

export const Money = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <rect x="2.5" y="6" width="19" height="12.5" rx="3" /><circle cx="12" cy="12.2" r="2.6" />
    <path d="M6 12.2v0M18 12.2v0" />
  </svg>
);

export const Shield = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M12 2.5 4.5 5.6v6c0 4.5 3.1 8.6 7.5 9.9 4.4-1.3 7.5-5.4 7.5-9.9v-6Z" /><path d="m9 12 2.2 2.2L15.2 10" />
  </svg>
);

export const Clock = ({ size = 15, className }: P) => (
  <svg {...base(size)} className={className}><circle cx="12" cy="12" r="9" /><path d="M12 7v5.3l3.2 2" /></svg>
);

export const Info = ({ size = 19, className }: P) => (
  <svg {...base(size)} className={className}><circle cx="12" cy="12" r="9.2" /><path d="M12 11v5.5M12 7.7v0" /></svg>
);

export const Alert = ({ size = 19, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M10.3 3.9 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /><path d="M12 9.5v4M12 17v0" />
  </svg>
);

export const Sleep = ({ size = 30, className }: P) => (
  <svg {...base(size)} strokeWidth={1.7} className={className}>
    <path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5a8.5 8.5 0 1 0 11 11Z" />
  </svg>
);

export const Logo = ({ size = 20, className }: P) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill="none" aria-hidden="true" className={className}>
    <rect x="3" y="4" width="4" height="16" rx="2" fill="currentColor" />
    <rect x="10" y="4" width="11" height="3.6" rx="1.8" fill="currentColor" opacity=".95" />
    <rect x="10" y="10.2" width="8" height="3.6" rx="1.8" fill="currentColor" opacity=".7" />
    <rect x="10" y="16.4" width="5" height="3.6" rx="1.8" fill="currentColor" opacity=".45" />
  </svg>
);

export const Camera = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M20.5 18.5a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2V9.2a2 2 0 0 1 2-2h2.6l1.5-2.4h5.8l1.5 2.4h2.6a2 2 0 0 1 2 2Z" />
    <circle cx="12" cy="13.4" r="3.4" />
  </svg>
);

export const Send = ({ size = 20, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M21 3 10.5 13.5" /><path d="M21 3l-6.8 18-3.7-7.5L3 9.8Z" />
  </svg>
);

export const Chat = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M20.5 12.6c0 4-3.8 7.2-8.5 7.2a9.8 9.8 0 0 1-2.6-.35L4 21.2l1.5-3.6A6.9 6.9 0 0 1 3.5 12.6c0-4 3.8-7.2 8.5-7.2s8.5 3.2 8.5 7.2Z" />
  </svg>
);

export const UserPlus = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <circle cx="9.5" cy="8" r="4" />
    <path d="M2.5 20.5c0-3.6 3.1-6 7-6 1.3 0 2.5.3 3.5.8" />
    <path d="M18 14v6M15 17h6" />
  </svg>
);

/* ---- تصنيفات المحلات ---- */

export const Restaurant = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M6 3v7a2.5 2.5 0 0 0 5 0V3" /><path d="M8.5 10.5V21" />
    <path d="M17.5 3c-1.4 1.4-2 3.3-2 5.2 0 1.6.7 2.8 2 3.3V21" />
  </svg>
);

export const Cart = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <circle cx="9.5" cy="19.5" r="1.6" /><circle cx="17.5" cy="19.5" r="1.6" />
    <path d="M2.5 3h2.6l2.4 12.2a1.6 1.6 0 0 0 1.6 1.3h8.4a1.6 1.6 0 0 0 1.6-1.3l1.4-7.4H6" />
  </svg>
);

export const Store = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M3.5 9.5V20a1 1 0 0 0 1 1h15a1 1 0 0 0 1-1V9.5" />
    <path d="M2.5 9.5 4.4 4.2A1.6 1.6 0 0 1 5.9 3h12.2a1.6 1.6 0 0 1 1.5 1.2l1.9 5.3a3 3 0 0 1-5.7 1.3 3 3 0 0 1-5.6 0 3 3 0 0 1-5.7-1.3Z" />
    <path d="M9.5 21v-5.5h5V21" />
  </svg>
);

export const Herb = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M12 21V11" />
    <path d="M12 11c0-4 2.6-7.4 6.4-8.2C19 6.8 16.6 11 12 11Z" />
    <path d="M12 15c-3.4 0-5.8-2.4-6.2-5.6C8.9 10 12 12 12 15Z" />
  </svg>
);

export const Bread = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M4 10.5C4 7.5 7.6 5.5 12 5.5s8 2 8 5c0 1.4-1.1 2.2-2.2 2.2V19a1 1 0 0 1-1 1H7.2a1 1 0 0 1-1-1v-6.3C5.1 12.7 4 11.9 4 10.5Z" />
    <path d="M9 9.2v3.2M15 9.2v3.2" />
  </svg>
);

export const Meat = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M7.6 20.4a5.5 5.5 0 0 1-3.6-8.6l7-8.5a4.6 4.6 0 0 1 7.6 5l-1.4 2.3" />
    <path d="M13.5 20.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" /><path d="M13.5 17v0" />
  </svg>
);

export const Produce = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M12 8.5c-3.6-2.7-8 0-8 4.6 0 3.6 3 7.4 5.6 7.4 1 0 1.6-.5 2.4-.5s1.4.5 2.4.5c2.6 0 5.6-3.8 5.6-7.4 0-4.6-4.4-7.3-8-4.6Z" />
    <path d="M12 8.5V5.2M12 5.2c2.2 0 3-1.4 3-2.7-1.8 0-3 1-3 2.7Z" />
  </svg>
);

export const Pharmacy = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <rect x="3" y="3.5" width="18" height="17" rx="4" />
    <path d="M12 8v8M8 12h8" />
  </svg>
);

export const Book = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M4 4.5A2 2 0 0 1 6 2.5h13v16H6a2 2 0 0 0-2 2Z" /><path d="M4 18.5V21h15" />
    <path d="M8.5 7h6" />
  </svg>
);

export const Sweets = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M4 20.5h16v-5.8a8 8 0 0 0-16 0Z" /><path d="M2.5 20.5h19" />
    <path d="M12 6.7V4.4M12 4.4c1.4 0 2-.9 2-1.9-1.2 0-2 .7-2 1.9Z" />
  </svg>
);

export const Tools = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <path d="M14.2 6.2a4 4 0 0 1 5.4-5.4l-3 3 2.6 2.6 3-3a4 4 0 0 1-5.4 5.4L6.4 19.2a2.3 2.3 0 0 1-3.3-3.3Z" />
  </svg>
);

export const Mobile = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <rect x="6" y="2.5" width="12" height="19" rx="3" /><path d="M10.5 18.3h3" />
  </svg>
);

export const Dots = ({ size = 22, className }: P) => (
  <svg {...base(size)} className={className}>
    <circle cx="12" cy="12" r="9" /><path d="M8.2 12v0M12 12v0M15.8 12v0" />
  </svg>
);

export const PLACE_ICON = {
  restaurant: Restaurant,
  supermarket: Cart,
  grocery: Store,
  herbalist: Herb,
  bakery: Bread,
  butcher: Meat,
  produce: Produce,
  pharmacy: Pharmacy,
  stationery: Book,
  sweets: Sweets,
  hardware: Tools,
  phones: Mobile,
  other: Dots,
} as const;

/* الأيقونة المناسبة لكل خدمة */
export const SERVICE_ICON = {
  delivery: Motorcycle,
  tuktuk: TukTuk,
  goods: Box,
  bicycle: Bicycle,
  mahalla_run: City,
} as const;
