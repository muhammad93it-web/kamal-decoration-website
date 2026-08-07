#!/usr/bin/env bash
# Downloads self-hosted assets (fonts, vanilla JS libs) + installs PHP libraries via Composer.
# Everything lands inside site/ so the deliverable stays fully portable (no CDN dependency).
set -uo pipefail
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SITE="$DIR/site"; VJS="$SITE/assets/js/vendor"; VCSS="$SITE/assets/css/vendor"; F="$SITE/assets/fonts"
UA="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125 Safari/537.36"
mkdir -p "$VJS" "$VCSS" "$F/pdf" "$SITE/assets/css"

echo "== web fonts (Noto Kufi Arabic + Vazirmatn) =="
CSSURL="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;500;600;700;800&family=Vazirmatn:wght@300;400;500;600;700&display=swap"
css=$(curl -fsSL -A "$UA" "$CSSURL" || true)
if [ -n "$css" ]; then
  i=0
  for u in $(printf '%s' "$css" | grep -oE 'https://fonts\.gstatic\.com/[^)]+\.woff2' | sort -u); do
    i=$((i+1)); n=$(printf 'kd-%02d.woff2' "$i")
    if curl -fsSL -o "$F/$n" "$u"; then
      css=$(printf '%s' "$css" | sed "s|$u|../fonts/$n|g")
    fi
  done
  printf '%s\n' "$css" > "$SITE/assets/css/fonts.css"
  echo "fonts.css written ($i woff2 files)"
else
  echo "FONT CSS DOWNLOAD FAILED"
fi

echo "== html5-qrcode (camera QR scanner, vanilla JS) =="
curl -fsSL -o "$VJS/html5-qrcode.min.js" "https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" \
 || curl -fsSL -o "$VJS/html5-qrcode.min.js" "https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js" \
 || echo "HTML5-QRCODE FAILED"

echo "== quill (rich text editor, vanilla JS) =="
curl -fsSL -o "$VJS/quill.js" "https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js" || echo "QUILL JS FAILED"
curl -fsSL -o "$VCSS/quill.snow.css" "https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" || echo "QUILL CSS FAILED"

echo "== PDF fonts (TTF with full Kurdish glyph coverage) =="
ok=0
for base in \
  "https://raw.githubusercontent.com/googlefonts/noto-fonts/main/hinted/ttf/NotoSansArabic" \
  "https://raw.githubusercontent.com/googlefonts/noto-fonts/master/hinted/ttf/NotoSansArabic"; do
  if curl -fsSL -o "$F/pdf/KurdishSans-Regular.ttf" "$base/NotoSansArabic-Regular.ttf" \
     && curl -fsSL -o "$F/pdf/KurdishSans-Bold.ttf" "$base/NotoSansArabic-Bold.ttf"; then ok=1; echo "Noto Sans Arabic OK"; break; fi
done
if [ "$ok" != 1 ]; then
  echo "Noto unavailable; falling back to Vazirmatn TTF"
  curl -fsSL -o "$F/pdf/KurdishSans-Regular.ttf" "https://raw.githubusercontent.com/rastikerdar/vazirmatn/master/fonts/ttf/Vazirmatn-Regular.ttf" || echo "PDF FONT REGULAR FAILED"
  curl -fsSL -o "$F/pdf/KurdishSans-Bold.ttf" "https://raw.githubusercontent.com/rastikerdar/vazirmatn/master/fonts/ttf/Vazirmatn-Bold.ttf" || echo "PDF FONT BOLD FAILED"
fi

echo "== composer libraries (mPDF, QR, barcode, HTMLPurifier) =="
cd "$SITE"
export COMPOSER_HOME=/tmp/composer COMPOSER_CACHE_DIR=/tmp/composer-cache
composer install --no-interaction --no-dev --no-progress 2>&1 | tail -n 8
echo "== vendor fetch done =="
