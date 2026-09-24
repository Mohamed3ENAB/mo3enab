import type { Metadata, Viewport } from 'next';
import { Alexandria } from 'next/font/google';
import './globals.css';

/* الخط بيتحمّل من دومينك مش من جوجل — أسرع على النت الضعيف. */
const app = Alexandria({
  subsets: ['arabic', 'latin'],
  weight: ['400', '600', '700', '800'],
  display: 'swap',
  variable: '--font-app',
});

export const metadata: Metadata = {
  title: 'في السكة — سواقين القيصرية والمناطق المجاورة',
  description:
    'دليل مجاني لسواقين وتوك توك القيصرية وبطينة ومحلة أبو علي ومحلة زياد والمحلة الكبرى. شوف مين متاح دلوقتي وكلّمه على طول.',
  manifest: '/manifest.webmanifest',
  other: { 'format-detection': 'telephone=no' },
};

export const viewport: Viewport = {
  width: 'device-width',
  initialScale: 1,
  themeColor: [
    { media: '(prefers-color-scheme: light)', color: '#00A86B' },
    { media: '(prefers-color-scheme: dark)', color: '#070B12' },
  ],
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ar" dir="rtl" className={app.variable}>
      <body>{children}</body>
    </html>
  );
}
