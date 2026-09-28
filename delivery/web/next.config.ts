import type { NextConfig } from 'next';

const config: NextConfig = {
  // الصفحة لازم تفضل خفيفة — النت في البلد ضعيف.
  poweredByHeader: false,
  compress: true,
  // بلاش توليد ملفات إرشاد للوكلاء في كل build
  agentRules: false,
};

export default config;
