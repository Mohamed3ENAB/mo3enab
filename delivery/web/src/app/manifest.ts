import type { MetadataRoute } from 'next';

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: 'في السكة',
    short_name: 'في السكة',
    description: 'شوف مين متاح دلوقتي من سواقين البلد وكلّمه على طول.',
    start_url: '/',
    display: 'standalone',
    background_color: '#F2F6F9',
    theme_color: '#00A86B',
    lang: 'ar',
    dir: 'rtl',
    icons: [
      { src: '/icon.svg', sizes: 'any', type: 'image/svg+xml' },
      { src: '/icons/icon-192.webp', sizes: '192x192', type: 'image/webp' },
      { src: '/icons/icon-512.webp', sizes: '512x512', type: 'image/webp' },
      // maskable عشان أندرويد يقص الأيقونة صح على الشاشة
      { src: '/icons/icon-512.webp', sizes: '512x512', type: 'image/webp', purpose: 'maskable' },
    ],
  };
}
