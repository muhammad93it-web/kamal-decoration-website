<?php
/** Label sheet as PDF (mPDF, Kurdish RTL). Falls back with a friendly message if mPDF is unavailable. */
require __DIR__ . '/includes/admin-bootstrap.php';

$type = (string)($_GET['type'] ?? 'shades');
if (!in_array($type, ['shades', 'products'], true)) $type = 'shades';
$pid = (int)($_GET['palette'] ?? 0);

if (!class_exists('\Mpdf\Mpdf')) {
    flash('error', t('a_pdf_missing', 'دروستکردنی PDF بەردەست نییە لەم سێرڤەرە — لەبری ئەوە دوگمەی «چاپکردن» بەکاربهێنە.'));
    redirect(admin_url('labels.php?type=' . $type . '&palette=' . $pid));
}

$items = [];
$sheetTitle = setting('site_name', 'دیکۆراتی کەمال');
if ($type === 'shades' && $pid) {
    $st = db()->prepare('SELECT s.*, p.name AS palette_name FROM palette_shades s JOIN palettes p ON p.id = s.palette_id WHERE s.palette_id = ? ORDER BY s.position, s.id');
    $st->execute([$pid]);
    $items = $st->fetchAll();
} elseif ($type === 'products') {
    $items = db()->query("SELECT * FROM products WHERE code IS NOT NULL AND code <> '' AND is_active = 1 ORDER BY sort_order, id")->fetchAll();
}
if (!$items) {
    flash('error', t('no_items', 'هیچ نییە'));
    redirect(admin_url('labels.php?type=' . $type . '&palette=' . $pid));
}

$fontDir = APP_ROOT . '/assets/fonts/pdf';
$hasKurdFont = is_file($fontDir . '/KurdishSans-Regular.ttf');

$config = [
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 10, 'margin_bottom' => 10, 'margin_left' => 8, 'margin_right' => 8,
    'directionality' => 'rtl',
    'autoScriptToLang' => true,
    'autoLangToFont' => true,
    'tempDir' => sys_get_temp_dir(),
];
if ($hasKurdFont) {
    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $config['fontDir'] = array_merge($defaultConfig['fontDir'], [$fontDir]);
    $config['fontdata'] = $defaultFontConfig['fontdata'] + [
        'kurdishsans' => [
            'R' => 'KurdishSans-Regular.ttf',
            'B' => is_file($fontDir . '/KurdishSans-Bold.ttf') ? 'KurdishSans-Bold.ttf' : 'KurdishSans-Regular.ttf',
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
    ];
    $config['default_font'] = 'kurdishsans';
}

$mpdf = new \Mpdf\Mpdf($config);
$mpdf->SetTitle('Labels — ' . $sheetTitle);

$css = '
  body { font-family: kurdishsans, sans-serif; }
  table.sheet { width: 100%; border-collapse: separate; border-spacing: 4mm; }
  td.lb { width: 33%; border: 0.4mm solid #D8CDBB; border-radius: 3mm; padding: 4mm; text-align: center; vertical-align: top; }
  .sw { height: 16mm; border-radius: 2mm; border: 0.2mm solid #C9BFAE; }
  .brand { font-size: 8pt; color: #8A7C68; margin-top: 2mm; }
  .nm { font-size: 11pt; font-weight: bold; color: #232120; margin: 1mm 0; }
  .cd { font-size: 12pt; font-weight: bold; color: #8A6B3F; letter-spacing: 1pt; }
  .ph { font-size: 8pt; color: #8A7C68; margin-top: 1.5mm; }
';

$cells = [];
foreach ($items as $it) {
    $isShade = $type === 'shades';
    $qrFile = !empty($it['qr_path']) ? UPLOAD_DIR . '/' . $it['qr_path'] : null;
    $bcFile = !empty($it['barcode_path']) ? UPLOAD_DIR . '/' . $it['barcode_path'] : null;
    $swatch = $isShade
        ? '<div class="sw" style="background:' . e($it['hex_color']) . '"></div>'
        : '';
    $qrImg = ($qrFile && is_file($qrFile)) ? '<img src="' . $qrFile . '" style="width:22mm;height:22mm">' : '';
    $bcImg = ($bcFile && is_file($bcFile)) ? '<br><img src="' . $bcFile . '" style="width:34mm;height:9mm">' : '';
    $name = e($isShade ? ($it['name'] . ' — ' . $it['palette_name']) : $it['name']);
    $cells[] = '<td class="lb">' . $swatch
        . '<div class="brand">' . e($sheetTitle) . '</div>'
        . '<div class="nm">' . $name . '</div>'
        . '<div class="cd">' . e($it['code']) . '</div>'
        . '<div style="margin-top:2mm">' . $qrImg . $bcImg . '</div>'
        . '<div class="ph">' . e(setting('phone', '')) . '</div>'
        . '</td>';
}

$rows = '';
foreach (array_chunk($cells, 3) as $chunk) {
    while (count($chunk) < 3) $chunk[] = '<td style="border:none"></td>';
    $rows .= '<tr>' . implode('', $chunk) . '</tr>';
}

$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML('<table class="sheet">' . $rows . '</table>', \Mpdf\HTMLParserMode::HTML_BODY);

log_activity('label_pdf', $type, $pid ?: null, count($items) . ' labels');
$mpdf->Output('kamal-labels-' . $type . '.pdf', \Mpdf\Output\Destination::INLINE);
exit;
