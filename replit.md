# KAMAL DECORATION — دیکۆراتی کەمال (+ RAWAND DECORATION — دیکۆراتی ڕەوەند)

## Overview
Production website + admin dashboard for **Kamal Decoration**, a home-decoration business in Ranya, Kurdistan (phone/WhatsApp 0750 024 4706). Built per the detailed prompt in `attached_assets/Kamal_Decoration_Replit_AI_Prompt_1786038074264.txt`.

**Second site (Aug 2026): `artifacts/rawand-decoration/`** — a fully independent clone named **دیکۆراتی ڕەوەند / Rawand Decoration** (phone/WhatsApp/Telegram/Viber 0750 103 8181, wa.me/tel use 9647501038181), destined for the user's OTHER cPanel hosting + a new domain he is buying. Same stack, same conventions, zero shared files/DB with Kamal. Preview at `/rawand` (artifact previewPath); dev DB `rawand_decoration` on 127.0.0.1:3308, socket `/tmp/rawand-mysql.sock`, dev admin **admin / Rawand@2026**. Its `dev/router.php` additionally strips the `BASE_PATH` env prefix and streams static files itself (php -S would 404 prefixed asset URLs); `site/tools/dev-seed.php` appends BASE_PATH to `site_url`. Never edit Kamal and Rawand as if they were one site — changes are per-business unless the user explicitly asks to apply to both.

**Deliverable stack (non-negotiable):** PHP 8.1+ / MySQL / HTML / CSS / vanilla JS / PDO. No Node, no frameworks, no CDNs — everything vendored. Deploys to Namecheap shared hosting by uploading `site/` and following the Kurdish README inside it.

## Structure (`artifacts/kamal-decoration/`)
- `site/` — **the portable deliverable.** Public pages at root, `includes/` core (bootstrap, auth, csrf, codes, uploads, track, seo, social), `admin/` dashboard, `install/` Kurdish web installer, `database.sql` (schema + Kurdish sample content), `libraries/vendor/` (mPDF, chillerlan/php-qrcode v5, picqer barcode, HTMLPurifier), `assets/` (self-hosted fonts/JS/CSS), Kurdish `README.md` deploy guide.
- `dev/` — Replit-only harness, **never shipped**: `start.sh` (MariaDB on 127.0.0.1:3307, socket /tmp/kamal-mysql.sock, db `kamal_decoration`, auto-import + seed), `router.php` (php -S router mirroring `.htaccess`), `fetch-vendor.sh`.
- Workflow: `artifacts/kamal-decoration: web` → `bash dev/start.sh`.

## Key conventions
- Whole UI Kurdish Sorani, `lang="ckb" dir="rtl"`, utf8mb4. Kurdish strings via `t('key', 'inline default')` — always pass inline defaults.
- Design "Warm Atelier": charcoal #232120, bone #FAF7F2, gold #BFA05A (dynamic via `color_accent` setting), Noto Kufi Arabic + Vazirmatn (self-hosted `assets/css/fonts.css`).
- Codes: palettes KD-P###, shades KD-S###, products KD-PR##. QR encodes `{site_url}/p/{CODE}`; regenerate from admin → ئامرازەکانی QR after domain change.
- Uploads: JPG/PNG/WebP/GIF only (finfo-validated, **no SVG**); logo/favicon stored as-is (`no_recompress`), other images get thumbnails.
- Clean URLs live in BOTH `site/.htaccess` (production) and `dev/router.php` (preview) — keep in sync. Extensionless pages (`/products`) map to `products.php`.
- `install.lock` lives at `config/install.lock` (bootstrap, installer, and dev tools all agree).
- Roles: `super_admin`, `editor` (tables `roles`/`user_roles`). Super-only pages: users.php, backups.php.

## Dev environment
- Dev admin login: **admin / Kamal@2026** (created by `site/tools/dev-seed.php`, which also sets `site_url` from the Replit domain, builds thumbnails, generates QR/barcodes).
- DB shell: `mariadb --socket=/tmp/kamal-mysql.sock -u root kamal_decoration`.
- `site/config/config.php` + `config/install.lock` are dev-generated (gitignored-style artifacts); production installs via `/install`.

## User preferences
- User is **non-technical** and chats in **Kurdish Sorani** — reply in simple Sorani, no jargon, no code talk.
- User will upload the real logo later themselves: admin → ڕێکخستنەکان → «لۆگۆ و ناسنامە».
- Target hosting is Namecheap cPanel — do NOT suggest Replit deployment for this project.

## Production (LIVE) — user's own hosting
- **Live site: https://kamal-decoration.com** — installed August 2026 by the user on his own Namecheap shared hosting (cPanel) + his own domain. Production is EXTERNAL — never suggest Replit deployments for this project.
- The agent has NO access to the live server or its database. All live content/settings changes are done by the user in his admin panel (`/admin` on his domain) — guide him step-by-step in simple Kurdish.
- Shipping a code fix: edit `site/`, rebuild the zip, present it for download; user uploads + extracts it over `public_html` in cPanel. `config/config.php` and `config/install.lock` are EXCLUDED from the zip, so his live config and admin login survive — no reinstall needed.
- Zip rebuild command: `cd artifacts/kamal-decoration/site && rm -f ../kamal-decoration-site.zip && zip -rq ../kamal-decoration-site.zip . -x "config/config.php" "config/install.lock" "tools/*" "logs/*"`
- Internal URLs are origin-relative via `url()`/`site_path()`; absolute URLs only via `abs_url()` (canonical, og:, sitemap, WhatsApp share). This fixed the Aug 2026 mixed-content outage (installer stored `http://www…`, visitors browse `https://` → all CSS blocked). Do not reintroduce absolute internal URLs.
- `.devdb/` (local MariaDB data dir) is gitignored and regenerated by `dev/start.sh`. Dev DB content is disposable; real content lives only on the user's live server.

## GitHub / restore
- This repo on GitHub is the code backup + source of truth. After re-importing into Replit: just run workflow `artifacts/kamal-decoration: web` (boots MariaDB, imports schema, seeds Kurdish demo data, serves via php -S). Dev admin: admin / Kamal@2026.
- The deliverable zip in the repo root of the artifact is rebuildable at any time — nothing outside `site/` is needed on shared hosting.
