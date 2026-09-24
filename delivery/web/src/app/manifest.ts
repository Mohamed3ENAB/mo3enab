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
    icons: [{ src: '/icon.svg', sizes: 'any', type: 'image/svg+xml' }],
  };
}
