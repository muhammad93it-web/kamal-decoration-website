/* admin panel JS */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* sidebar (mobile) */
  var side = $('#aSide'), burger = $('#aBurger'), bd = $('#aSideBackdrop');
  function sideOpen(o) {
    if (!side) return;
    side.classList.toggle('open', o);
    if (bd) bd.hidden = !o;
  }
  burger && burger.addEventListener('click', function () { sideOpen(!side.classList.contains('open')); });
  bd && bd.addEventListener('click', function () { sideOpen(false); });

  /* confirm destructive forms */
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* auto slug (Kurdish → Latin-ish kept as typed; server re-slugifies) */
  $$('[data-slug-source]').forEach(function (slugInput) {
    var src = document.querySelector(slugInput.getAttribute('data-slug-source'));
    if (!src) return;
    var dirty = slugInput.value !== '';
    slugInput.addEventListener('input', function () { dirty = slugInput.value !== ''; });
    src.addEventListener('input', function () {
      if (dirty) return;
      slugInput.placeholder = src.value.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^\p{L}\p{N}\-]+/gu, '').slice(0, 60);
    });
  });

  /* image input preview */
  $$('input[type="file"][data-preview]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var t = document.querySelector(inp.getAttribute('data-preview'));
      if (!t || !inp.files || !inp.files[0]) return;
      t.src = URL.createObjectURL(inp.files[0]);
      t.style.display = '';
    });
  });

  /* hex text ⇄ color input pairs */
  $$('[data-hex-pair]').forEach(function (color) {
    var txt = document.querySelector(color.getAttribute('data-hex-pair'));
    if (!txt) return;
    color.addEventListener('input', function () { txt.value = color.value.toUpperCase(); updateSw(txt); });
    txt.addEventListener('input', function () {
      if (/^#[0-9a-fA-F]{6}$/.test(txt.value.trim())) color.value = txt.value.trim();
      updateSw(txt);
    });
  });
  function updateSw(txt) {
    var sw = document.querySelector(txt.getAttribute('data-swatch') || '');
    if (sw && /^#[0-9a-fA-F]{6}$/.test(txt.value.trim())) sw.style.background = txt.value.trim();
  }

  /* copy buttons */
  $$('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      navigator.clipboard.writeText(b.getAttribute('data-copy')).then(function () {
        var t = b.textContent;
        b.textContent = '✓';
        setTimeout(function () { b.textContent = t; }, 1300);
      });
    });
  });

  /* check-all */
  $$('[data-check-all]').forEach(function (master) {
    master.addEventListener('change', function () {
      $$(master.getAttribute('data-check-all')).forEach(function (c) { c.checked = master.checked; });
    });
  });

  /* Quill rich editor (textarea fallback when the lib is missing) */
  if (!window.Quill) {
    $$('.rich-editor').forEach(function (host) { host.style.display = 'none'; });
  } else {
    $$('.rich-editor').forEach(function (host) {
      var hidden = document.querySelector(host.getAttribute('data-input'));
      if (hidden) hidden.style.display = 'none';
      var q = new Quill(host, {
        theme: 'snow',
        modules: {
          toolbar: [
            [{ header: [2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ align: [] }, { direction: 'rtl' }],
            ['link', 'blockquote', 'clean']
          ]
        }
      });
      if (hidden && hidden.value) q.clipboard.dangerouslyPasteHTML(hidden.value);
      var form = host.closest('form');
      form && form.addEventListener('submit', function () {
        if (hidden) hidden.value = q.root.innerHTML;
      });
    });
  }
})();
