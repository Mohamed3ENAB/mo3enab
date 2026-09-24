/* Service worker بسيط: بيكاش الأصول الثابتة بس.
   الصفحات نفسها بتتجاب من الشبكة دايمًا — التوفر لازم يبقى لحظي. */
const CACHE = 'sekka-v1';
const ASSETS = [
  'assets/app.css',
  'assets/app.js',
  'assets/icons/icon-192.png',
  'assets/icons/icon-512.png',
  'offline.html',
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting()).catch(() => {}));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // الأصول: من الكاش الأول
  if (/\.(css|js|png|ico|webp|svg)$/.test(url.pathname)) {
    e.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        const copy = res.clone();
        caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
        return res;
      }).catch(() => hit))
    );
    return;
  }

  // الصفحات: الشبكة، ولو مفيش نت نعرض صفحة بديلة
  e.respondWith(fetch(req).catch(() => caches.match('offline.html')));
});
