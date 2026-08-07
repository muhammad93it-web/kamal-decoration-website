<!doctype html>
<html lang="ckb" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t('maintenance_title')) ?> — <?= e(setting('site_name', 'دیکۆراتی ڕەوەند')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/fonts.css')) ?>">
<style>
  body{margin:0;font-family:'Vazirmatn',Tahoma,sans-serif;background:#232120;color:#FAF7F2;
       display:grid;place-items:center;min-height:100vh;text-align:center;padding:24px}
  .box{max-width:520px}
  h1{font-family:'Noto Kufi Arabic','Vazirmatn',sans-serif;color:#BFA05A;font-size:1.9rem;margin:0 0 12px}
  p{line-height:2;opacity:.85;margin:0}
  .dot{width:64px;height:64px;margin:0 auto 24px;border-radius:50%;border:2px solid #BFA05A;
       display:grid;place-items:center;font-size:28px}
</style>
</head>
<body>
  <div class="box">
    <div class="dot">🛠</div>
    <h1><?= e(t('maintenance_title')) ?></h1>
    <p><?= e(t('maintenance_text')) ?></p>
  </div>
</body>
</html>
