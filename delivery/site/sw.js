/* في السكة — service worker
   الهدف: الصفحات تفتح بسرعة، ولو النت قطع تبان صفحة محترمة
   بدل شاشة الديناصور. مفيش كاش لأي صفحة فيها بيانات حساب. */

const CACHE = 'sekka-v3';
const SHELL = ['offline.html', 'assets/app.css', 'assets/app.js', 'assets/icons/favicon.svg'];

self.addEventListener('install', (ev) => {
  ev.waitUntil(
    caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (ev) => {
  ev.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (ev) => {
  const req = ev.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== location.origin) return;

  // 🔴 صور المستندات والصفحات الشخصية مش بتتخزن أبدًا
  if (url.pathname.endsWith('/img.php') || url.pathname.includes('/dashboard') || url.pathname.includes('/admin')) {
    return;
  }

  // الملفات الثابتة: من الكاش الأول
  if (/\.(css|js|svg|png|webp|woff2?)$/.test(url.pathname) || url.pathname.endsWith('/icon.php')) {
    ev.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        const copy = res.clone();
        caches.open(CACHE).then((c) => c.put(req, copy));
        return res;
      }).catch(() => hit))
    );
    return;
  }

  // الصفحات: من الشبكة، ولو وقعت نبان صفحة «مفيش نت»
  if (req.mode === 'navigate') {
    ev.respondWith(
      fetch(req).catch(() => caches.match('offline.html'))
    );
  }
});
