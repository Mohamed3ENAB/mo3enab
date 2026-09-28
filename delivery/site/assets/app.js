/* في السكة — جافاسكربت الواجهة. مفيش مكتبات. */
(() => {
  'use strict';

  /* ---- نسخ الأرقام ---- */
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-copy]');
    if (!b) return;
    const num = b.dataset.copy;
    const old = b.dataset.label || b.textContent;
    b.dataset.label = old;
    const done = () => { b.textContent = 'اتنسخ ✓'; setTimeout(() => { b.textContent = old; }, 1600); };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(num).then(done).catch(() => selectNum(b));
    } else selectNum(b);
  });
  function selectNum(btn) {
    const n = btn.closest('.telbox')?.querySelector('.num');
    if (!n) return;
    const r = document.createRange(); r.selectNodeContents(n);
    const s = window.getSelection(); s.removeAllRanges(); s.addRange(r);
    btn.textContent = 'محدد';
  }

  /* ---- الورقة السفلية ---- */
  document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-sheet]');
    if (open) {
      const el = document.getElementById(open.dataset.sheet);
      if (el) { el.hidden = false; document.body.style.overflow = 'hidden'; }
      return;
    }
    const close = e.target.closest('[data-close]');
    if (close || e.target.classList.contains('scrim')) {
      document.querySelectorAll('.scrim').forEach((s) => { s.hidden = true; });
      document.body.style.overflow = '';
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.scrim').forEach((s) => { s.hidden = true; });
    document.body.style.overflow = '';
  });

  /* ---- فلترة وبحث فورية من غير ما الصفحة تتحمّل تاني ---- */
  const list = document.querySelector('[data-filterable]');
  if (list) {
    const q = document.getElementById('q');
    const items = [...list.querySelectorAll('[data-hay]')];
    const apply = () => {
      const zone = list.dataset.zone || '';
      const kind = list.dataset.kind || '';
      const term = (q?.value || '').trim();
      let shown = 0;
      items.forEach((it) => {
        const ok = (!zone || it.dataset.zone === zone)
          && (!kind || (it.dataset.kinds || '').split(',').includes(kind))
          && (!term || (it.dataset.hay || '').includes(term));
        it.hidden = !ok;
        if (ok) shown++;
      });
      // كل قسم بيعدّ صفوفه هو — مش إجمالي الصفحة
      document.querySelectorAll('[data-group]').forEach((g) => {
        const n = [...g.querySelectorAll('[data-hay]')].filter((x) => !x.hidden).length;
        const c = g.querySelector('[data-count]');
        if (c) c.textContent = String(n);
        g.hidden = n === 0;
      });
      const none = document.getElementById('no-results');
      if (none) none.hidden = shown !== 0;
    };
    q?.addEventListener('input', apply);
    document.querySelectorAll('[data-set]').forEach((b) => {
      b.addEventListener('click', () => {
        const [key, v] = b.dataset.set.split(':');
        list.dataset[key] = list.dataset[key] === v ? '' : v;
        document.querySelectorAll(`[data-set^="${key}:"]`).forEach((o) => {
          const ov = o.dataset.set.split(':')[1];
          o.setAttribute('aria-pressed', String(list.dataset[key] === ov));
        });
        document.querySelector(`[data-set="${key}:"]`)
          ?.setAttribute('aria-pressed', String(!list.dataset[key]));
        apply();
      });
    });
    document.getElementById('clear-filters')?.addEventListener('click', () => {
      list.dataset.zone = ''; list.dataset.kind = '';
      if (q) q.value = '';
      document.querySelectorAll('[data-set]').forEach((o) => {
        o.setAttribute('aria-pressed', String(o.dataset.set.endsWith(':')));
      });
      apply();
    });
    apply();
  }

  /* ---- معاينة الصورة قبل الرفع ---- */
  document.querySelectorAll('.photofield').forEach((f) => {
    const input = f.querySelector('input[type=file]');
    const prev = f.querySelector('.prev');
    const btn = f.querySelector('.pick');
    btn?.addEventListener('click', () => input?.click());
    input?.addEventListener('change', () => {
      const file = input.files && input.files[0];
      if (!file) return;
      const img = new Image();
      img.alt = '';
      img.src = URL.createObjectURL(file);
      prev.replaceChildren(img);
      btn.textContent = 'غيّرها';
    });
  });

  /* ---- نص التلميح تحت خيار إخفاء الرقم ---- */
  const hide = document.getElementById('hide_phone');
  const hint = document.getElementById('hide-hint');
  hide?.addEventListener('change', () => {
    hint.textContent = hide.checked
      ? 'رقمك هيظهر ناقص كده 0101••••78، والسواقين هيكلموك من جوه التطبيق.'
      : 'رقمك هيظهر كامل لأي حد يفتح الصفحة، وأي سائق يقدر يتصل بيك على طول.';
  });

  /* ---- تثبيت التطبيق على الشاشة ---- */
  const bar = document.getElementById('installbar');
  const KEY = 'sekka-install-dismissed';
  let dismissed = false;
  try { dismissed = localStorage.getItem(KEY) === '1'; } catch {}
  let deferred = null;

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferred = e;
    if (bar && !dismissed) bar.hidden = false;
  });

  // آيفون مبيدعمش beforeinstallprompt — بنعرض الخطوات بدل الزرار
  const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
  const standalone = window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;
  if (bar && isIOS && !standalone && !dismissed) {
    bar.querySelector('.t').innerHTML =
      'ثبّت التطبيق على شاشتك<span>من سفاري: زرار المشاركة ← «إضافة إلى الشاشة الرئيسية»</span>';
    bar.querySelector('[data-install]')?.remove();
    bar.hidden = false;
  }

  bar?.querySelector('[data-install]')?.addEventListener('click', async () => {
    if (!deferred) return;
    deferred.prompt();
    await deferred.userChoice;
    deferred = null;
    bar.hidden = true;
  });
  bar?.querySelector('.x')?.addEventListener('click', () => {
    bar.hidden = true;
    try { localStorage.setItem(KEY, '1'); } catch {}
  });

  /* ---- Service worker: بيخلي التطبيق يتثبّت ويفتح لما النت يقطع ---- */
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      const base = document.querySelector('link[rel=manifest]')?.getAttribute('href') || '/manifest.webmanifest';
      navigator.serviceWorker.register(base.replace('manifest.webmanifest', 'sw.js')).catch(() => {});
    });
  }
})();
