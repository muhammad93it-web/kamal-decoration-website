<?php
require __DIR__ . '/includes/bootstrap.php';
require APP_ROOT . '/templates/partials/cards.php';

track_page_view('scanner');

$PAGE = ['title' => t('scanner_title'), 'desc' => t('scanner_sub'), 'nav' => 'scanner'];
require APP_ROOT . '/templates/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1 class="page-title"><?= e(t('scanner_title')) ?></h1>
    <p class="page-sub"><?= e(t('scanner_sub')) ?></p>
  </div>
</section>
<?php render_breadcrumbs([['label' => t('nav_scanner')]]); ?>

<section class="section">
  <div class="container">
    <div class="scanner-card">
      <div id="qrReader"></div>
      <div class="scanner-actions">
        <button class="btn btn-gold" type="button" id="scanStart"><?= e(t('scanner_start')) ?></button>
        <button class="btn btn-ghost" type="button" id="scanStop" style="display:none"><?= e(t('scanner_stop')) ?></button>
      </div>
      <div class="scan-result" id="scanMsg"></div>

      <div class="manual-search">
        <label for="manualCode"><?= e(t('scanner_manual')) ?></label>
        <form method="get" action="<?= e(url('resolve.php')) ?>">
          <input type="hidden" name="src" value="manual">
          <input type="text" id="manualCode" name="code" placeholder="KD-S101" dir="ltr" autocomplete="off" required>
          <button class="btn btn-dark" type="submit"><?= e(t('btn_search')) ?></button>
        </form>
        <p class="muted" style="font-size:.8rem;margin-top:10px"><?= e(t('scanner_manual_hint')) ?></p>
      </div>
    </div>
  </div>
</section>

<script src="<?= e(asset('js/vendor/html5-qrcode.min.js')) ?>"></script>
<script>
(function () {
  var reader = null, running = false;
  var startBtn = document.getElementById('scanStart');
  var stopBtn = document.getElementById('scanStop');
  var msg = document.getElementById('scanMsg');
  var base = (window.KD_BASE || '').replace(/\/$/, '');

  function show(kind, text) { msg.className = 'scan-result ' + kind; msg.textContent = text; }

  function extractCode(text) {
    text = String(text || '').trim();
    var m = text.match(/\/p\/([A-Za-z0-9\-]+)/);
    if (m) return m[1];
    if (/^[A-Za-z0-9\-]{3,32}$/.test(text)) return text;
    return null;
  }

  function onScan(decoded) {
    var code = extractCode(decoded);
    stop();
    if (code) {
      show('ok', '<?= e(t('scanner_found')) ?> ' + code + ' …');
      window.location.href = base + '/p/' + encodeURIComponent(code.toUpperCase()) + '?src=qr';
    } else {
      show('err', '<?= e(t('scanner_not_code')) ?>');
    }
  }

  function start() {
    if (typeof Html5Qrcode === 'undefined') { show('err', '<?= e(t('scanner_lib_missing')) ?>'); return; }
    reader = reader || new Html5Qrcode('qrReader');
    show('ok', '<?= e(t('scanner_starting')) ?>');
    reader.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: function (w, h) { var s = Math.min(w, h) * 0.7; return { width: s, height: s }; } },
      onScan,
      function () { /* per-frame decode misses are normal */ }
    ).then(function () {
      running = true;
      msg.className = 'scan-result';
      startBtn.style.display = 'none';
      stopBtn.style.display = '';
    }).catch(function (err) {
      show('err', '<?= e(t('scanner_camera_error')) ?>');
    });
  }

  function stop() {
    if (reader && running) { reader.stop().catch(function () {}); running = false; }
    startBtn.style.display = '';
    stopBtn.style.display = 'none';
  }

  startBtn.addEventListener('click', start);
  stopBtn.addEventListener('click', stop);
  window.addEventListener('beforeunload', stop);
})();
</script>

<?php require APP_ROOT . '/templates/footer.php'; ?>
