/* iCare Service — language switch, phone menu, banner carousel, pricelist picker and search. */
(function () {
  // Language: remember choice in a cookie and reload so the server renders that language.
  document.querySelectorAll('[data-lang]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.cookie = 'icare_lang=' + b.dataset.lang + ';path=/;max-age=31536000;SameSite=Lax';
      location.reload();
    });
  });

  // Phone menu
  var toggle = document.querySelector('.site-menu-toggle');
  var menu = document.getElementById('site-mobile-menu');
  if (toggle && menu) {
    var svg = toggle.querySelector('svg');
    var bars = svg.innerHTML;
    var openLabel = toggle.getAttribute('aria-label');
    var setOpen = function (open) {
      menu.style.display = open ? '' : 'none';
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? (document.documentElement.lang === 'en' ? 'Close menu' : 'Tutup menu') : openLabel);
      svg.innerHTML = open ? '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>' : bars;
    };
    toggle.addEventListener('click', function () { setOpen(menu.style.display === 'none'); });
    menu.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { setOpen(false); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
  }

  // Banner carousel
  var track = document.querySelector('.banner-track');
  if (track) {
    var slides = track.querySelectorAll('.banner-slide');
    var dots = document.querySelectorAll('.carousel-dots button');
    var current = 0, timer = null;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var show = function (n) {
      current = n;
      track.style.transform = 'translateX(-' + n * 100 + '%)';
      slides.forEach(function (s, i) {
        s.setAttribute('aria-hidden', i === n ? 'false' : 'true');
        if (i === n) s.removeAttribute('inert'); else s.setAttribute('inert', '');
        s.querySelectorAll('a').forEach(function (a) { a.tabIndex = i === n ? 0 : -1; });
      });
      dots.forEach(function (d, i) {
        d.classList.toggle('selected', i === n);
        d.setAttribute('aria-pressed', i === n ? 'true' : 'false');
      });
      clearTimeout(timer);
      if (!reduce) timer = setTimeout(function () { show((current + 1) % slides.length); }, 6000);
    };
    dots.forEach(function (d, i) { d.addEventListener('click', function () { show(i); }); });
    show(0);
  }

  // Pricelist: device / repair buttons keep the scroll position.
  var key = 'icare-scroll';
  var saved = sessionStorage.getItem(key);
  if (saved !== null) { sessionStorage.removeItem(key); window.scrollTo(0, +saved); }
  document.querySelectorAll('[data-href]').forEach(function (b) {
    b.addEventListener('click', function () {
      sessionStorage.setItem(key, String(window.scrollY));
      location.href = b.dataset.href;
    });
  });

  // Pricelist search
  var input = document.getElementById('price-search');
  if (input) {
    var rows = document.querySelectorAll('.public-price-table tbody tr');
    var count = document.getElementById('price-count');
    var filter = function () {
      var q = input.value.trim().toLowerCase(), n = 0;
      rows.forEach(function (r) {
        var hit = r.dataset.model.indexOf(q) !== -1;
        r.style.display = hit ? '' : 'none';
        if (hit) n++;
      });
      count.textContent = count.dataset.template.replace('{n}', n).replace('{total}', count.dataset.total);
    };
    input.addEventListener('input', filter);
    document.getElementById('price-reset').addEventListener('click', function () { input.value = ''; filter(); });
  }
})();
