(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---- Reveal on scroll ---- */
  var reveals = $$('.reveal');
  if ('IntersectionObserver' in window && !reduce) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
    reveals.forEach(function (el, i) {
      el.style.transitionDelay = (i % 4) * 70 + 'ms';
      io.observe(el);
    });
  } else {
    reveals.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* ---- Counters ---- */
  var counters = $$('.count');
  function runCounter(el) {
    var to = parseInt(el.getAttribute('data-to'), 10) || 0;
    if (reduce) { el.textContent = to; return; }
    var t0 = null, dur = 1400;
    function step(t) {
      if (!t0) t0 = t;
      var p = Math.min((t - t0) / dur, 1);
      el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  if ('IntersectionObserver' in window) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { runCounter(en.target); cio.unobserve(en.target); }
      });
    }, { threshold: 0.6 });
    counters.forEach(function (el) { cio.observe(el); });
  } else {
    counters.forEach(runCounter);
  }

  /* ---- Header: hide on scroll down, show on scroll up; mobile menu ---- */
  var top = $('#top'), burger = $('#burger'), nav = $('#nav');
  var lastY = window.scrollY, ticking = false;
  function onScroll() {
    var y = window.scrollY;
    top.classList.toggle('is-scrolled', y > 10);
    var menuOpen = nav.classList.contains('is-open');
    top.classList.toggle('is-hidden', y > lastY && y > 240 && !menuOpen);
    lastY = y;
    parallax(y);
    timeline();
    ticking = false;
  }
  window.addEventListener('scroll', function () {
    if (!ticking) { ticking = true; requestAnimationFrame(onScroll); }
  }, { passive: true });

  function closeMenu() { nav.classList.remove('is-open'); burger.setAttribute('aria-expanded', 'false'); }
  burger.addEventListener('click', function () {
    var open = nav.classList.toggle('is-open');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  $$('a', nav).forEach(function (a) { a.addEventListener('click', closeMenu); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });

  /* ---- Hero parallax ---- */
  var arch = $('.arch');
  function parallax(y) {
    if (reduce || !arch || y > window.innerHeight) return;
    arch.style.setProperty('--py', (y * -0.06).toFixed(1) + 'px');
  }

  /* ---- Magnetic button ---- */
  if (canHover && !reduce) {
    $$('.magnetic').forEach(function (b) {
      b.addEventListener('mousemove', function (e) {
        var r = b.getBoundingClientRect();
        var x = (e.clientX - r.left - r.width / 2) * 0.25;
        var y = (e.clientY - r.top - r.height / 2) * 0.35;
        b.style.transform = 'translate(' + x + 'px,' + y + 'px)';
      });
      b.addEventListener('mouseleave', function () { b.style.transform = ''; });
    });
  }

  /* ---- Format cards (accordion) ---- */
  $$('.card__head').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('.card');
      var open = !card.classList.contains('is-open');
      card.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  /* ---- Timeline: clock follows the scroll ---- */
  var cues = $$('.cue');
  var clockTime = $('#clockTime'), clockBar = $('#clockBar');
  var activeIdx = -1;
  function timeline() {
    if (!cues.length) return;
    var mid = window.innerHeight * 0.5, best = 0, bestD = Infinity;
    cues.forEach(function (c, i) {
      var r = c.getBoundingClientRect();
      var d = Math.abs(r.top + r.height / 2 - mid);
      if (d < bestD) { bestD = d; best = i; }
    });
    var first = cues[0].getBoundingClientRect(), last = cues[cues.length - 1].getBoundingClientRect();
    var inside = first.top < window.innerHeight && last.bottom > 0;
    if (!inside) return;
    if (best !== activeIdx) {
      activeIdx = best;
      cues.forEach(function (c, i) { c.classList.toggle('is-active', i === best); });
      clockTime.textContent = cues[best].getAttribute('data-time');
      clockBar.style.width = ((best + 1) / cues.length * 100) + '%';
    }
  }

  /* ---- Lead form ---- */
  var form = $('#leadForm');
  if (form) {
    var status = $('#formStatus');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      status.className = 'form__status'; status.textContent = '';
      var name = form.elements.name, contact = form.elements.contact, ok = true;
      [name, contact].forEach(function (f) {
        var bad = !f.value.trim();
        f.setAttribute('aria-invalid', bad ? 'true' : 'false');
        if (bad) ok = false;
      });
      if (!ok) { status.classList.add('is-err'); status.textContent = 'Заполните имя и контакт, чтобы я могла ответить.'; return; }
      var btn = form.querySelector('button[type="submit"]');
      btn.disabled = true;
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.ok) { status.textContent = status.getAttribute('data-ok'); form.reset(); }
          else { throw new Error(); }
        })
        .catch(function () { status.classList.add('is-err'); status.textContent = 'Не получилось отправить. Напишите, пожалуйста, в мессенджер выше.'; })
        .then(function () { btn.disabled = false; });
    });
  }

  onScroll();
})();
