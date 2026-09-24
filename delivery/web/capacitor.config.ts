import type { CapacitorConfig } from '@capacitor/cli';

/**
 * التطبيق صفحاته بتتبني على السيرفر (Next.js)، فمينفعش يتحول لملفات
 * ثابتة جوه التطبيق. فالغلاف الأصلي بيفتح الموقع المنشور.
 *
 * 🔴 غيّر SEKKA_APP_URL للرابط بتاعك قبل ما تعمل build:
 *    SEKKA_APP_URL=https://your-app.vercel.app npx cap sync
 */
const url = process.env.SEKKA_APP_URL ?? 'https://REPLACE-ME.vercel.app';

const config: CapacitorConfig = {
  appId: 'eg.fielsekka.app',
  appName: 'في السكة',
  webDir: 'mobile-shell',
  server: {
    url,
    cleartext: false,
    // الدومين بتاعك بس — أي لينك تاني (اتصال، واتساب، خرايط)
    // بيتفتح برة التطبيق في المتصفح أو التطبيق المناسب
    allowNavigation: [new URL(url).host],
  },
  android: {
    backgroundColor: '#F2F6F9',
  },
  ios: {
    backgroundColor: '#F2F6F9',
    contentInset: 'always',
  },
};

export default config;
