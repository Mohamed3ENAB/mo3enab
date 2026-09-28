/* ============================================================
   في السكة — الجافاسكريبت
   كل حاجة هنا تحسينات فوق صفحة شغّالة أصلًا من غيرها:
   الفلاتر والقصّ والأصوات لو الجافاسكريبت وقع، الموقع يفضل يشتغل.
   ============================================================ */
(function () {
  'use strict';

  var $  = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  };

  /* ── التنبيهات ──────────────────────────────────────────── */
  function toast(text, kind) {
    var box = $('#toasts');
    if (!box) return;
    var t = document.createElement('div');
    t.className = 'toast ' + (kind || '');
    t.textContent = text;
    box.appendChild(t);
    setTimeout(function () {
      t.classList.add('out');
      setTimeout(function () { t.remove(); }, 320);
    }, 3200);
  }
  window.sekkaToast = toast;

  /* ── الأصوات ────────────────────────────────────────────
     كل الأصوات متولّدة بـ WebAudio — مفيش ملفات صوت تتحمّل،
     فالصفحة بتفضل خفيفة والصوت بيشتغل حتى من غير نت. */
  var Sound = (function () {
    var ctx = null;
    var on = store.get('sekka-sound') === '1';

    function ensure() {
      if (!ctx) {
        var AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return null;
        ctx = new AC();
      }
      if (ctx.state === 'suspended') ctx.resume();
      return ctx;
    }

    function blip(freq, dur, type, vol, slideTo) {
      if (!on) return;
      var c = ensure();
      if (!c) return;
      var o = c.createOscillator(), g = c.createGain();
      o.type = type || 'sine';
      o.frequency.setValueAtTime(freq, c.currentTime);
      if (slideTo) o.frequency.exponentialRampToValueAtTime(slideTo, c.currentTime + dur);
      g.gain.setValueAtTime(0.0001, c.currentTime);
      g.gain.exponentialRampToValueAtTime(vol || 0.16, c.currentTime + 0.012);
      g.gain.exponentialRampToValueAtTime(0.0001, c.currentTime + dur);
      o.connect(g); g.connect(c.destination);
      o.start(); o.stop(c.currentTime + dur + 0.02);
    }

    return {
      isOn: function () { return on; },
      set: function (v) {
        on = !!v;
        store.set('sekka-sound', on ? '1' : '0');
        if (on) { ensure(); blip(660, 0.09, 'sine', 0.12, 880); }
      },
      play: function (name) {
        switch (name) {
          case 'tap':  blip(520, 0.05, 'sine', 0.09); break;
          case 'ok':   blip(560, 0.09, 'sine', 0.14, 840);
                       setTimeout(function () { blip(840, 0.12, 'sine', 0.12, 1050); }, 90); break;
          case 'call': blip(760, 0.1, 'triangle', 0.12, 980); break;
          case 'err':  blip(240, 0.18, 'sawtooth', 0.1, 150); break;
          case 'pop':  blip(900, 0.06, 'sine', 0.1, 1200); break;
        }
      }
    };
  })();

  function syncSoundBtn() {
    $$('[data-sound-toggle]').forEach(function (b) {
      var onIc = $('[data-sound-on]', b), offIc = $('[data-sound-off]', b);
      if (onIc)  onIc.hidden  = !Sound.isOn();
      if (offIc) offIc.hidden = Sound.isOn();
      b.setAttribute('aria-pressed', Sound.isOn() ? 'true' : 'false');
    });
  }

  /* ── الثيم ──────────────────────────────────────────────── */
  function currentTheme() {
    var t = document.documentElement.dataset.theme;
    if (t) return t;
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  function syncThemeBtn() {
    var dark = currentTheme() === 'dark';
    $$('[data-theme-toggle]').forEach(function (b) {
      var d = $('[data-theme-dark]', b), l = $('[data-theme-light]', b);
      if (d) d.hidden = dark;
      if (l) l.hidden = !dark;
    });
  }

  document.addEventListener('click', function (ev) {
    var soundBtn = ev.target.closest && ev.target.closest('[data-sound-toggle]');
    if (soundBtn) { Sound.set(!Sound.isOn()); syncSoundBtn(); return; }

    var themeBtn = ev.target.closest && ev.target.closest('[data-theme-toggle]');
    if (themeBtn) {
      var next = currentTheme() === 'dark' ? 'light' : 'dark';
      document.documentElement.dataset.theme = next;
      store.set('sekka-theme', next);
      syncThemeBtn();
      Sound.play('tap');
      return;
    }

    var back = ev.target.closest && ev.target.closest('[data-back]');
    if (back) { history.length > 1 ? history.back() : (location.href = './'); return; }

    var copy = ev.target.closest && ev.target.closest('[data-copy]');
    if (copy) {
      var val = copy.getAttribute('data-copy');
      var done = function () {
        copy.classList.add('done');
        copy.textContent = 'اتنسخ ✓';
        toast('الرقم اتنسخ: ' + val, 'ok');
        Sound.play('pop');
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(val).then(done, function () { toast('مقدرناش ننسخ. اضغط مطوّل على الرقم.', 'bad'); });
      } else {
        var ta = document.createElement('textarea');
        ta.value = val; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); done(); } catch (e) { toast('مقدرناش ننسخ.', 'bad'); }
        ta.remove();
      }
      return;
    }

    var sfx = ev.target.closest && ev.target.closest('[data-sfx]');
    if (sfx) Sound.play(sfx.getAttribute('data-sfx'));
  });

  /* ── الموجة في الأزرار ──────────────────────────────────── */
  document.addEventListener('pointerdown', function (ev) {
    var btn = ev.target.closest && ev.target.closest('.btn');
    if (!btn) return;
    var r = btn.getBoundingClientRect();
    var size = Math.max(r.width, r.height);
    var s = document.createElement('span');
    s.className = 'rip';
    s.style.width = s.style.height = size + 'px';
    s.style.left = (ev.clientX - r.left - size / 2) + 'px';
    s.style.top  = (ev.clientY - r.top - size / 2) + 'px';
    btn.appendChild(s);
    setTimeout(function () { s.remove(); }, 600);
  });

  /* ── تأكيد قبل الأفعال الخطرة ───────────────────────────── */
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (f.hasAttribute && f.hasAttribute('data-confirm')) {
      if (!confirm(f.getAttribute('data-confirm'))) { ev.preventDefault(); return; }
    }
  });

  /* ── إظهار كلمة السر ────────────────────────────────────── */
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest && ev.target.closest('[data-pw-toggle]');
    if (!t) return;
    var inp = t.parentNode.querySelector('input');
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    t.setAttribute('aria-pressed', inp.type === 'text' ? 'true' : 'false');
  });

  /* ── رفع يبعت لوحده ─────────────────────────────────────── */
  document.addEventListener('change', function (ev) {
    var i = ev.target;
    if (i.matches && i.matches('[data-autosubmit]') && i.files && i.files.length) {
      if (!/^image\//.test(i.files[0].type)) { toast('لازم صورة — الملفات مش مقبولة.', 'bad'); Sound.play('err'); i.value = ''; return; }
      i.form && i.form.submit();
    }
  });

  /* ── الفلاتر ────────────────────────────────────────────── */
  function setupFilters(box) {
    var list  = $('[data-list]');
    if (!list) return;
    var items = $$(':scope > *', list);
    var state = { text: '', service: '', zone: '', cat: '', live: false };
    var none  = $('[data-noresult]');

    function apply() {
      var shown = 0;
      items.forEach(function (el) {
        var ok = true;
        if (state.text) {
          ok = (el.getAttribute('data-name') || '').indexOf(state.text) !== -1;
        }
        if (ok && state.service) {
          ok = (',' + (el.getAttribute('data-services') || '') + ',').indexOf(',' + state.service + ',') !== -1;
        }
        if (ok && state.zone) ok = el.getAttribute('data-zone') === state.zone;
        if (ok && state.cat)  ok = el.getAttribute('data-cat') === state.cat;
        if (ok && state.live) ok = el.getAttribute('data-live') === '1';
        el.hidden = !ok;
        if (ok) shown++;
      });
      if (none) none.hidden = shown !== 0;
    }

    var search = $('[data-search]', box);
    if (search) {
      search.addEventListener('input', function () {
        state.text = this.value.trim();
        apply();
      });
    }

    $$('.fchip', box).forEach(function (chip) {
      chip.addEventListener('click', function () {
        var f = chip.getAttribute('data-f');
        $$('.fchip[data-f="' + f + '"]', box).forEach(function (c) { c.classList.remove('on'); });
        chip.classList.add('on');
        state[f] = chip.getAttribute('data-v') || '';
        Sound.play('tap');
        apply();
      });
    });

    var liveBox = $('[data-f="live"]', box);
    if (liveBox) liveBox.addEventListener('change', function () { state.live = this.checked; apply(); });

    // اختصارات الصفحة الرئيسية بتظبّط فلتر الخدمة
    $$('[data-quick]').forEach(function (q) {
      q.addEventListener('click', function () {
        var v = q.getAttribute('data-quick');
        var chip = $('.fchip[data-f="service"][data-v="' + v + '"]', box);
        if (chip) chip.click();
      });
    });
  }
  $$('[data-filters]').forEach(setupFilters);

  /* فلتر بسيط لجداول الإدارة */
  $$('[data-filter]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var v = this.value.trim();
      $$(this.getAttribute('data-filter')).forEach(function (row) {
        row.hidden = v !== '' && (row.getAttribute('data-text') || '').indexOf(v) === -1;
      });
    });
  });

  /* ── العدّادات ──────────────────────────────────────────── */
  function countUp(el) {
    var target = el.getAttribute('data-count');
    var num = parseFloat(target);
    if (isNaN(num)) { el.textContent = target; return; }
    var dec = (target.indexOf('.') !== -1) ? 1 : 0;
    var t0 = null, dur = 900;
    function step(ts) {
      if (!t0) t0 = ts;
      var p = Math.min(1, (ts - t0) / dur);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = (num * eased).toFixed(dec);
      if (p < 1) requestAnimationFrame(step);
      else el.textContent = target;
    }
    requestAnimationFrame(step);
  }

  /* ── الظهور مع النزول ───────────────────────────────────── */
  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if ('IntersectionObserver' in window && !reduced) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target;
        if (el.hasAttribute('data-count')) countUp(el);
        io.unobserve(el);
      });
    }, { threshold: 0.4 });
    $$('[data-count]').forEach(function (el) { io.observe(el); });
  } else {
    $$('[data-count]').forEach(function (el) { el.textContent = el.getAttribute('data-count'); });
  }

  /* ── ميلان ثلاثي الأبعاد خفيف ───────────────────────────── */
  if (window.matchMedia && window.matchMedia('(hover: hover)').matches && !reduced) {
    $$('.tilt').forEach(function (card) {
      card.addEventListener('pointermove', function (ev) {
        var r = card.getBoundingClientRect();
        var px = (ev.clientX - r.left) / r.width - 0.5;
        var py = (ev.clientY - r.top) / r.height - 0.5;
        card.style.transform = 'perspective(800px) rotateX(' + (-py * 5).toFixed(2) + 'deg) rotateY(' +
                               (px * 6).toFixed(2) + 'deg) translateY(-3px)';
      });
      card.addEventListener('pointerleave', function () { card.style.transform = ''; });
    });
  }

  /* ── المعالج (خطوات التسجيل) ────────────────────────────── */
  $$('[data-wizard]').forEach(function (form) {
    var steps = $$('fieldset[data-step]', form);
    if (steps.length < 2) return;
    form.classList.add('js');
    var dots = $$('.steps li', form);
    var at = 0;

    function show(i) {
      at = Math.max(0, Math.min(steps.length - 1, i));
      steps.forEach(function (s, n) { s.classList.toggle('on', n === at); });
      dots.forEach(function (d, n) { d.classList.toggle('on', n <= at); });
      form.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
    }

    function validStep() {
      var bad = null;
      $$('input, select, textarea', steps[at]).forEach(function (f) {
        if (bad || f.hidden || f.type === 'hidden') return;
        if (f.willValidate && !f.checkValidity()) bad = f;
      });
      if (bad) { bad.reportValidity(); Sound.play('err'); return false; }
      return missingFiles(steps[at]) ? false : true;
    }

    $$('[data-wz-next]', form).forEach(function (b) {
      b.addEventListener('click', function () { if (validStep()) { Sound.play('tap'); show(at + 1); } });
    });
    $$('[data-wz-prev]', form).forEach(function (b) {
      b.addEventListener('click', function () { show(at - 1); });
    });
    show(0);

    // لو فيه حقل ناقص في خطوة مخفية، نوديه عليها بدل ما المتصفح يقف صامت
    form.addEventListener('invalid', function (ev) {
      var fs = ev.target.closest('fieldset[data-step]');
      if (fs && !fs.classList.contains('on')) show(steps.indexOf(fs));
    }, true);
  });

  /* ── الصور المطلوبة ──────────────────────────────────────
     الـ input بتاع الملف مخفي جوه label، وكروم بيرفض يعمل
     validation على حاجة مخفية وبيوقف الإرسال من غير ما يقول
     حاجة. فبنفحص إحنا ونقول للمستخدم بالعربي.
     السيرفر بيفحص تاني على أي حال. */
  function missingFiles(scope) {
    var miss = null;
    $$('[data-af-required], [data-doc-required]', scope).forEach(function (box) {
      if (miss) return;
      var inp = $('[data-af-input], [data-doc-input]', box);
      if (inp && (!inp.files || !inp.files.length)) miss = box;
    });
    if (miss) {
      var label = (miss.querySelector('.lbl') || {}).textContent || 'صورة مطلوبة';
      toast('لسه مرفعتش: ' + label.replace('*', '').trim(), 'bad');
      Sound.play('err');
      miss.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'center' });
      return true;
    }
    return false;
  }

  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!f.querySelector) return;
    if (!f.querySelector('[data-af-required], [data-doc-required]')) return;
    if (missingFiles(f)) ev.preventDefault();
  });

  /* ── رفع المستندات: معاينة وفحص إنها صورة ───────────────── */
  $$('[data-doc-field]').forEach(function (box) {
    var input = $('[data-doc-input]', box);
    var thumb = $('[data-doc-thumb]', box);
    var nameEl = $('[data-doc-name]', box);
    var drop = $('.doc-drop', box);
    if (!input) return;

    drop.addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); input.click(); }
    });

    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      if (!f) return;
      if (!/^image\/(png|jpeg|jpg|webp|gif)$/i.test(f.type)) {
        toast('صور بس — الملفات زي PDF مش مقبولة.', 'bad');
        Sound.play('err');
        input.value = '';
        return;
      }
      if (f.size > 8 * 1024 * 1024) {
        toast('الصورة أكبر من ٨ ميجا. صغّرها الأول.', 'bad');
        Sound.play('err');
        input.value = '';
        return;
      }
      var url = URL.createObjectURL(f);
      thumb.innerHTML = '';
      var im = new Image();
      im.onload = function () { URL.revokeObjectURL(url); };
      im.src = url;
      im.alt = '';
      thumb.appendChild(im);
      if (nameEl) nameEl.textContent = f.name;
      drop.classList.add('has');
      Sound.play('pop');
    });
  });

  /* ── صورة الحساب: قصّ دايري بالسحب والتكبير ─────────────── */
  $$('[data-avatar-field]').forEach(function (box) {
    var input   = $('[data-af-input]', box);
    var canvas  = $('[data-af-canvas]', box);
    var preview = $('[data-af-preview]', box);
    var stage   = $('[data-af-stage]', box);
    var zoom    = $('[data-af-zoom]', box);
    var zoomWrap= $('[data-af-zoomwrap]', box);
    var crop    = $('[data-af-crop]', box);
    var clear   = $('[data-af-clear]', box);
    if (!input || !canvas) return;

    var img = null, base = 1, k = 1, ox = 0, oy = 0;
    var SIDE = 132, OUT = 512;
    var dpr = Math.min(2, window.devicePixelRatio || 1);
    canvas.width = SIDE * dpr;
    canvas.height = SIDE * dpr;
    canvas.style.width = canvas.style.height = SIDE + 'px';
    var ctx = canvas.getContext('2d');

    function clamp() {
      var w = img.width * base * k, h = img.height * base * k;
      var mx = Math.max(0, (w - SIDE) / 2), my = Math.max(0, (h - SIDE) / 2);
      ox = Math.max(-mx, Math.min(mx, ox));
      oy = Math.max(-my, Math.min(my, oy));
    }

    function draw() {
      if (!img) return;
      clamp();
      var w = img.width * base * k, h = img.height * base * k;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, SIDE, SIDE);
      ctx.drawImage(img, SIDE / 2 + ox - w / 2, SIDE / 2 + oy - h / 2, w, h);
    }

    /** نفس الحساب بس بمقاس التخزين — الناتج مربع مظبوط. */
    function exportCrop() {
      if (!img) return '';
      var f = OUT / SIDE;
      var c = document.createElement('canvas');
      c.width = c.height = OUT;
      var x = c.getContext('2d');
      x.fillStyle = '#ffffff';
      x.fillRect(0, 0, OUT, OUT);
      var w = img.width * base * k * f, h = img.height * base * k * f;
      x.drawImage(img, OUT / 2 + ox * f - w / 2, OUT / 2 + oy * f - h / 2, w, h);
      try { return c.toDataURL('image/jpeg', 0.9); } catch (e) { return ''; }
    }

    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      if (!f) return;
      if (!/^image\/(png|jpeg|jpg|webp|gif)$/i.test(f.type)) {
        toast('لازم تختار صورة.', 'bad'); Sound.play('err'); input.value = ''; return;
      }
      if (f.size > 8 * 1024 * 1024) {
        toast('الصورة أكبر من ٨ ميجا.', 'bad'); Sound.play('err'); input.value = ''; return;
      }
      var url = URL.createObjectURL(f);
      var im = new Image();
      im.onload = function () {
        img = im;
        base = Math.max(SIDE / im.width, SIDE / im.height);   // تغطية الدايرة
        k = 1; ox = 0; oy = 0;
        if (zoom) zoom.value = 100;
        canvas.hidden = false;
        if (preview) preview.hidden = true;
        if (zoomWrap) zoomWrap.hidden = false;
        if (clear) clear.hidden = false;
        draw();
        URL.revokeObjectURL(url);
        Sound.play('pop');
        toast('اسحب الصورة عشان تظبّط المنتصف.', 'ok');
      };
      im.onerror = function () { toast('مقدرناش نفتح الصورة دي.', 'bad'); URL.revokeObjectURL(url); };
      im.src = url;
    });

    if (zoom) {
      zoom.addEventListener('input', function () {
        k = parseInt(this.value, 10) / 100;
        draw();
      });
    }

    var dragging = false, lx = 0, ly = 0;
    stage.addEventListener('pointerdown', function (ev) {
      if (!img) return;
      dragging = true; lx = ev.clientX; ly = ev.clientY;
      stage.setPointerCapture(ev.pointerId);
    });
    stage.addEventListener('pointermove', function (ev) {
      if (!dragging || !img) return;
      ox += ev.clientX - lx; oy += ev.clientY - ly;
      lx = ev.clientX; ly = ev.clientY;
      draw();
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (e) {
      stage.addEventListener(e, function () { dragging = false; });
    });
    stage.addEventListener('wheel', function (ev) {
      if (!img) return;
      ev.preventDefault();
      k = Math.max(1, Math.min(3, k + (ev.deltaY < 0 ? 0.08 : -0.08)));
      if (zoom) zoom.value = Math.round(k * 100);
      draw();
    }, { passive: false });

    if (clear) {
      clear.addEventListener('click', function () {
        img = null; input.value = ''; if (crop) crop.value = '';
        canvas.hidden = true;
        if (preview) preview.hidden = false;
        if (zoomWrap) zoomWrap.hidden = true;
        clear.hidden = true;
      });
    }

    var form = box.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        if (img && crop) crop.value = exportCrop();
      });
    }
  });

  /* ── عند التحميل ────────────────────────────────────────── */
  syncSoundBtn();
  syncThemeBtn();

  if (window.__flash && window.__flash.text) {
    toast(window.__flash.text, window.__flash.type === 'bad' ? 'bad' : 'ok');
    Sound.play(window.__flash.type === 'bad' ? 'err' : 'ok');
  }

  // أول لمسة بتفك قفل الصوت في أغلب المتصفحات
  document.addEventListener('pointerdown', function once() {
    if (Sound.isOn()) Sound.play('tap');
    document.removeEventListener('pointerdown', once);
  }, { once: true });

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      var base = document.querySelector('link[rel="manifest"]');
      var swUrl = base ? base.getAttribute('href').replace('manifest.webmanifest', 'sw.js') : 'sw.js';
      navigator.serviceWorker.register(swUrl).catch(function () {});
    });
  }
})();
