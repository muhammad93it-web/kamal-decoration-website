<?php
/**
 * QR + Code128 barcode generation (fully offline, vendored libraries).
 * QR codes encode the short link  {site}/p/{CODE}  which resolve.php redirects.
 */

function codes_autoload(): void
{
    static $done = false;
    if (!$done) {
        require_once APP_ROOT . '/libraries/vendor/autoload.php';
        $done = true;
    }
}

/** Next sequential code for an entity. $prefix e.g. 'KD-S', 'KD-P', 'KD-PR' */
function next_code(string $table, string $column, string $prefix): string
{
    $st = db()->prepare(
        "SELECT {$column} FROM {$table} WHERE {$column} LIKE ? ORDER BY LENGTH({$column}) DESC, {$column} DESC LIMIT 1"
    );
    $st->execute([$prefix . '%']);
    $last = (string)($st->fetchColumn() ?: '');
    $n = 100;
    if ($last !== '' && preg_match('/(\d+)$/', $last, $m)) $n = (int)$m[1];
    return $prefix . ($n + 1);
}

/** Absolute short URL a QR points to. */
function qr_target_url(string $code): string
{
    return site_base() . '/p/' . rawurlencode($code);
}

/** Generate (overwrite) the QR PNG for a code. Returns uploads-relative path. */
function generate_qr(string $code): string
{
    codes_autoload();
    $rel = 'qr/' . $code . '.png';
    $abs = UPLOAD_DIR . '/' . $rel;
    if (!is_dir(dirname($abs))) @mkdir(dirname($abs), 0755, true);

    $options = new \chillerlan\QRCode\QROptions([
        // v5: outputType selects the renderer (defaults to SVG markup otherwise)
        'outputType'    => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
        'eccLevel'      => \chillerlan\QRCode\Common\EccLevel::M,
        'scale'         => 8,
        'outputBase64'  => false,
        'addQuietzone'  => true,
        'quietzoneSize' => 2,
    ]);
    $png = (new \chillerlan\QRCode\QRCode($options))->render(qr_target_url($code));
    file_put_contents($abs, $png);
    return $rel;
}

/** Generate (overwrite) the Code128 barcode PNG for a code. Returns uploads-relative path. */
function generate_barcode(string $code): string
{
    codes_autoload();
    $rel = 'barcodes/' . $code . '.png';
    $abs = UPLOAD_DIR . '/' . $rel;
    if (!is_dir(dirname($abs))) @mkdir(dirname($abs), 0755, true);

    $gen = new \Picqer\Barcode\BarcodeGeneratorPNG();
    $png = $gen->getBarcode($code, $gen::TYPE_CODE_128, 3, 70, [0, 0, 0]);
    file_put_contents($abs, $png);
    return $rel;
}

/** Ensure a shade has code + QR + barcode files; updates the row. */
function ensure_shade_codes(int $shadeId): void
{
    $st = db()->prepare('SELECT * FROM palette_shades WHERE id = ?');
    $st->execute([$shadeId]);
    $s = $st->fetch();
    if (!$s) return;

    $code = $s['code'];
    if ($code === null || $code === '') {
        $code = next_code('palette_shades', 'code', 'KD-S');
    }
    $qr = generate_qr($code);
    $bc = generate_barcode($code);
    db()->prepare('UPDATE palette_shades SET code = ?, qr_path = ?, barcode_path = ? WHERE id = ?')
        ->execute([$code, $qr, $bc, $shadeId]);
}

/** Ensure a product has code + QR + barcode files; updates the row. */
function ensure_product_codes(int $productId): void
{
    $st = db()->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$productId]);
    $p = $st->fetch();
    if (!$p) return;

    $code = $p['code'];
    if ($code === null || $code === '') {
        $code = next_code('products', 'code', 'KD-PR');
    }
    $qr = generate_qr($code);
    $bc = generate_barcode($code);
    db()->prepare('UPDATE products SET code = ?, qr_path = ?, barcode_path = ? WHERE id = ?')
        ->execute([$code, $qr, $bc, $productId]);
}

/** Regenerate every QR + barcode (use after the site URL/domain changes). Returns count. */
function regenerate_all_codes(): int
{
    $n = 0;
    foreach (db()->query('SELECT id FROM palette_shades') as $r) {
        ensure_shade_codes((int)$r['id']);
        $n++;
    }
    foreach (db()->query('SELECT id FROM products') as $r) {
        ensure_product_codes((int)$r['id']);
        $n++;
    }
    return $n;
}

/** Find what a scanned/typed code belongs to. Returns [type, row] or null. */
function find_by_code(string $code): ?array
{
    $code = trim($code);
    if ($code === '') return null;

    $st = db()->prepare('SELECT * FROM palette_shades WHERE code = ? LIMIT 1');
    $st->execute([$code]);
    if ($row = $st->fetch()) return ['shade', $row];

    $st = db()->prepare('SELECT * FROM products WHERE code = ? LIMIT 1');
    $st->execute([$code]);
    if ($row = $st->fetch()) return ['product', $row];

    $st = db()->prepare('SELECT * FROM palettes WHERE code = ? LIMIT 1');
    $st->execute([$code]);
    if ($row = $st->fetch()) return ['palette', $row];

    return null;
}
