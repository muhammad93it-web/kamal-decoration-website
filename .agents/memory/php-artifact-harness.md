---
name: Pure-PHP artifact on Replit
description: Pattern for previewing a shared-hosting PHP+MySQL deliverable (e.g. Namecheap) inside the pnpm monorepo.
---

Pattern used for the Kamal Decoration artifact; reuse when a client needs a portable PHP/MySQL site but wants live Replit preview.

- Split the artifact dir into `site/` (the pure deliverable — what gets zipped/uploaded) and `dev/` (Replit-only harness). Nothing in `site/` may reference `dev/` or Replit env vars.
- Harness: `dev/start.sh` boots MariaDB with `--datadir` under the artifact (`mariadb-install-db` first run), socket in `/tmp`, non-default port; imports `database.sql` when the DB is empty; then `exec php -S 0.0.0.0:$PORT -t site dev/router.php`.
- `dev/router.php` must mirror `.htaccess` rewrites EXACTLY, including the generic extensionless-page rule (`/products` → `products.php`) — forgetting that rule 404s every static page while parameterized routes still work.
- CLI seeding (`site/tools/dev-seed.php`) covers what a web installer would do: admin user, site_url from `REPLIT_DEV_DOMAIN`, thumbnails, QR files. Keep it idempotent — it runs on every workflow start.
- **Why:** shared-hosting deliverables can't use Node tooling, and the preview must not leak dev-only files into the upload folder.
- **How to apply:** any "build me a PHP site for cPanel/Namecheap" request; also check that installer lock-file paths agree between bootstrap gate, web installer, and dev tools (we lost a cycle to `install.lock` vs `config/install.lock`).

URL building in proxied previews: building every internal URL absolute from a stored `site_url` breaks behind Replit's preview proxy — the page origin (shared proxy / 127.0.0.1) differs from the stored dev domain, so fonts die on CORS and sessions/fetches go cross-origin. Keep internal links, assets, redirects, and JS bases **origin-relative** (path-prefix aware); reserve absolute URLs for QR payloads, sitemap, canonical/OG, and share links. Also give `php -S` workers (`PHP_CLI_SERVER_WORKERS`) or parallel asset requests time out behind the proxy.

Curl smoke-test gotchas against `php -S` behind the Replit proxy:
- Percent-encode non-ASCII query strings (`curl -G --data-urlencode`); raw UTF-8 in the request line makes php -S reply "Malformed HTTP request", which surfaces as a proxy 502 that looks like an app crash.
- Analytics-style loggers often bot-filter the default curl UA — assert DB log rows with a browser User-Agent.
- If the app fingerprints sessions by User-Agent, keep the UA byte-identical across login and later curl calls, or everything 302s to login mid-suite.
