/* دیکۆراتی ڕەوەند — public site JS (vanilla, no dependencies) */
(function () {
  'use strict';

  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  /* sticky header state */
  const header = $('#siteHeader');
  if (header) {
    const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* mobile drawer */
  const drawer = $('#drawer');
  const backdrop = $('#drawerBackdrop');
  const toggle = $('#navToggle');
  function setDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle('open', open);
    drawer.setAttribute('aria-hidden', String(!open));
    if (toggle) toggle.setAttribute('aria-expanded', String(open));
    if (backdrop) {
      backdrop.hidden = !open;
      requestAnimationFrame(() => backdrop.classList.toggle('show', open));
    }
    document.body.style.overflow = open ? 'hidden' : '';
  }
  toggle && toggle.addEventListener('click', () => setDrawer(true));
  $('#drawerClose') && $('#drawerClose').addEventListener('click', () => setDrawer(false));
  backdrop && backdrop.addEventListener('click', () => setDrawer(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setDrawer(false); });

  /* hero slider — thumbnail-expand style */
  const hero = $('#hero') || $('.hero');
  const slides = hero ? $$('.hero-slide', hero) : [];
  if (hero && slides.length > 1) {
    const thumbsBox = $('#heroThumbs');
    const thumbs = thumbsBox ? $$('.hero-thumb', thumbsBox) : [];
    const dotsWrap = $('#heroDots');
    const nextBtn = $('#heroNext'), prevBtn = $('#heroPrev'), countEl = $('#heroCount');
    const content = $('.hero-content', hero);
    const tEl = $('#heroTitle'), sEl = $('#heroSub');
    const defaults = { t: tEl ? tEl.textContent : '', s: sEl ? sEl.textContent : '' };
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const knum = (n) => String(n); // site convention: Latin digits (matches knum() in PHP)
    let cur = 0, busy = false, timer = null, inView = true;

    const dots = slides.map((_, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', 'سلاید ' + knum(i + 1));
      if (i === 0) b.classList.add('active');
      b.addEventListener('click', () => go(i));
      dotsWrap && dotsWrap.appendChild(b);
      return b;
    });

    /* thumbs always preview the upcoming slides, like a deck */
    function paintMeta() {
      dots.forEach((d, i) => d.classList.toggle('active', i === cur));
      if (countEl) countEl.textContent = knum(cur + 1) + ' / ' + knum(slides.length);
      thumbs.forEach((th, k) => {
        const idx = (cur + k + 1) % slides.length;
        th.dataset.go = idx;
        const img = th.querySelector('img'), lbl = th.querySelector('.ht-label');
        const src = slides[idx].querySelector('img');
        if (img && src) img.src = src.currentSrc || src.src;
        if (lbl) lbl.textContent = slides[idx].dataset.title || '';
      });
    }

    function swapText() {
      if (!content) return;
      content.classList.add('hero-text-out');
      setTimeout(() => {
        if (tEl) tEl.textContent = slides[cur].dataset.title || defaults.t;
        if (sEl) sEl.textContent = slides[cur].dataset.sub || defaults.s;
        content.classList.remove('hero-text-out');
      }, reduced ? 0 : 230);
    }

    function activate(idx) {
      slides[cur].classList.remove('active');
      cur = idx;
      slides[cur].classList.add('active');
      swapText();
      paintMeta();
    }

    function go(idx, viaThumb) {
      idx = ((idx % slides.length) + slides.length) % slides.length;
      if (busy || idx === cur) return;
      restart();
      const th = viaThumb || thumbs.find((t) => +t.dataset.go === idx) || null;
      const canFlip = th && !reduced && th.offsetParent !== null;
      if (!canFlip) { activate(idx); return; }
      busy = true;
      /* FLIP: clone the thumb and let it grow into the full hero */
      const r = th.getBoundingClientRect(), hr = hero.getBoundingClientRect();
      const clone = document.createElement('div');
      clone.className = 'hero-flip-clone';
      const img = document.createElement('img');
      const target = slides[idx].querySelector('img');
      const thImg = th.querySelector('img');
      img.src = (target && (target.currentSrc || target.src)) || (thImg ? thImg.src : '');
      clone.appendChild(img);
      clone.style.cssText = 'top:' + r.top + 'px;left:' + r.left + 'px;width:' + r.width + 'px;height:' + r.height + 'px;';
      document.body.appendChild(clone);
      th.classList.add('is-flying');
      void clone.offsetWidth;
      clone.style.top = hr.top + 'px';
      clone.style.left = hr.left + 'px';
      clone.style.width = hr.width + 'px';
      clone.style.height = hr.height + 'px';
      clone.style.borderRadius = '0px';
      setTimeout(() => {
        activate(idx);
        th.classList.remove('is-flying');
        clone.style.opacity = '0';
        setTimeout(() => { clone.remove(); busy = false; }, 260);
      }, 730);
    }

    function restart() {
      clearInterval(timer);
      timer = setInterval(() => {
        if (!inView || document.hidden || busy) return;
        go(cur + 1);
      }, 6500);
    }

    nextBtn && nextBtn.addEventListener('click', () => go(cur + 1));
    prevBtn && prevBtn.addEventListener('click', () => go(cur - 1));
    thumbs.forEach((th) => th.addEventListener('click', function () { go(+this.dataset.go, this); }));
    hero.addEventListener('mouseenter', () => clearInterval(timer));
    hero.addEventListener('mouseleave', restart);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver((en) => { inView = !!(en[0] && en[0].isIntersecting); }, { threshold: 0.15 }).observe(hero);
    }
    paintMeta();
    restart();
  } else if (slides.length === 1) {
    slides[0].classList.add('active');
  }

  /* reveal on scroll */
  const revealEls = $$('.reveal');
  if ('IntersectionObserver' in window && revealEls.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
    revealEls.forEach((el) => io.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('in'));
  }

  /* live search suggestions */
  const input = $('#searchInput');
  const box = $('#suggestBox');
  if (input && box) {
    let t = null, lastQ = '';
    const base = (window.KD_BASE || '').replace(/\/$/, '');
    input.addEventListener('input', () => {
      const q = input.value.trim();
      clearTimeout(t);
      if (q.length < 2) { box.hidden = true; box.innerHTML = ''; return; }
      t = setTimeout(async () => {
        if (q === lastQ) return;
        lastQ = q;
        try {
          const r = await fetch((base || '') + '/ajax/suggest.php?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
          const data = await r.json();
          if (!data.items || !data.items.length) {
            box.innerHTML = '<div class="suggest-empty">' + (data.empty_label || '—') + '</div>';
            box.hidden = false;
            return;
          }
          box.innerHTML = data.items.map((it) => {
            const media = it.color
              ? '<span class="suggest-swatch" style="background:' + it.color + '"></span>'
              : (it.thumb ? '<img class="suggest-thumb" src="' + it.thumb + '" alt="">' : '<span class="suggest-swatch" style="background:#E9DFD2"></span>');
            return '<a class="suggest-item" href="' + it.url + '">' + media +
              '<span><span class="suggest-label">' + it.label + '</span><br><span class="suggest-sub">' + (it.sub || '') + '</span></span></a>';
          }).join('');
          box.hidden = false;
        } catch (err) { box.hidden = true; }
      }, 220);
    });
    document.addEventListener('click', (e) => {
      if (!box.contains(e.target) && e.target !== input) box.hidden = true;
    });
    input.addEventListener('focus', () => { if (box.innerHTML) box.hidden = false; });
  }

  /* before/after slider */
  $$('.ba-wrap').forEach((wrap) => {
    const set = (clientX) => {
      const r = wrap.getBoundingClientRect();
      let p = ((clientX - r.left) / r.width) * 100;
      p = Math.max(2, Math.min(98, p));
      wrap.style.setProperty('--pos', p + '%');
    };
    let dragging = false;
    wrap.addEventListener('pointerdown', (e) => { dragging = true; wrap.setPointerCapture(e.pointerId); set(e.clientX); });
    wrap.addEventListener('pointermove', (e) => { if (dragging) set(e.clientX); });
    wrap.addEventListener('pointerup', () => { dragging = false; });
    wrap.addEventListener('pointercancel', () => { dragging = false; });
  });

  /* lightbox */
  const lb = $('#lightbox');
  const lbImg = $('#lightboxImg');
  if (lb && lbImg) {
    let group = [], idx = 0;
    function openLb(items, i) {
      group = items; idx = i;
      lbImg.src = group[idx].href;
      lbImg.alt = group[idx].dataset.alt || '';
      lb.hidden = false;
      document.body.style.overflow = 'hidden';
    }
    function closeLb() { lb.hidden = true; lbImg.src = ''; document.body.style.overflow = ''; }
    function nav(d) {
      if (!group.length) return;
      idx = (idx + d + group.length) % group.length;
      lbImg.src = group[idx].href;
    }
    document.addEventListener('click', (e) => {
      const a = e.target.closest('[data-lightbox]');
      if (!a) return;
      e.preventDefault();
      const name = a.getAttribute('data-lightbox');
      const items = $$('[data-lightbox="' + name + '"]');
      openLb(items, items.indexOf(a));
    });
    $('#lightboxClose').addEventListener('click', closeLb);
    $('#lightboxPrev').addEventListener('click', () => nav(-1));
    $('#lightboxNext').addEventListener('click', () => nav(1));
    lb.addEventListener('click', (e) => { if (e.target === lb) closeLb(); });
    document.addEventListener('keydown', (e) => {
      if (lb.hidden) return;
      if (e.key === 'Escape') closeLb();
      if (e.key === 'ArrowLeft') nav(1);
      if (e.key === 'ArrowRight') nav(-1);
    });
  }

  /* product gallery thumbs */
  const mainImg = $('#galleryMain img');
  $$('.gallery-thumbs button').forEach((b) => {
    b.addEventListener('click', () => {
      if (!mainImg) return;
      $$('.gallery-thumbs button').forEach((x) => x.classList.remove('active'));
      b.classList.add('active');
      mainImg.src = b.dataset.full;
    });
  });

  /* copy buttons */
  $$('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(btn.dataset.copy);
        const old = btn.textContent;
        btn.textContent = btn.dataset.copied || '✓';
        setTimeout(() => { btn.textContent = old; }, 1600);
      } catch (e) { /* older browsers */ }
    });
  });

  /* share button */
  $$('[data-share]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const url = btn.dataset.share;
      const title = btn.dataset.shareTitle || document.title;
      if (navigator.share) {
        try { await navigator.share({ title, url }); } catch (e) {}
      } else {
        try {
          await navigator.clipboard.writeText(url);
          const old = btn.textContent;
          btn.textContent = btn.dataset.copied || '✓';
          setTimeout(() => { btn.textContent = old; }, 1600);
        } catch (e) {}
      }
    });
  });
})();
