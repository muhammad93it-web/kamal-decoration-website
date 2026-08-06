---
name: chillerlan php-qrcode v5 output selection
description: Why QR "PNG" files come out as SVG bytes with chillerlan/php-qrcode ^5 and the correct option.
---

In chillerlan/php-qrcode 5.0.x, `QROptions::$outputType` (default `QROutputInterface::MARKUP_SVG`) selects the renderer. `outputInterface` is only consulted when `outputType === QROutputInterface::CUSTOM` — setting `outputInterface` alone is **silently ignored** and `render()` returns SVG markup, which then gets written into `.png` files that browsers show as broken images.

Correct PNG config:
```php
new QROptions([
  'outputType'   => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
  'outputBase64' => false,
  ...
]);
```

**How to apply:** whenever generating QR files with this lib, verify the first bytes of the output (`\x89PNG` vs `<?xml`) before trusting the extension; a `file` check on one generated artifact catches it instantly.
