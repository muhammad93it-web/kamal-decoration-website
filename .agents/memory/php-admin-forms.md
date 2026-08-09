---
name: PHP admin panel form/caching lessons
description: Recurring pitfalls in the pure-PHP admin panels (decoration sites) — stale badges, Enter-key deletes, bulk-id tampering.
---

## Admin pages must send no-store headers
**Rule:** After `require_login()`, send `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` on every admin page.
**Why:** Browser back-button served a cached page with a stale unread-messages badge; users thought read/unread was broken.
**How to apply:** Emit headers centrally in the admin bootstrap, never per page.

## Bulk forms with per-row delete buttons need a hidden default submit
**Rule:** When one `<form>` contains row-level `<button name="del" value="ID">` submits, place a hidden/sr-only default submit button (the save action) as the FIRST submit in the form.
**Why:** Pressing Enter in any text input triggers the first submit button in DOM order — without the hidden one, Enter deleted a row.
**How to apply:** `<button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">` right after the form opens.

## Bulk endpoints must intersect posted ids with existing rows
**Rule:** For `name[id]`-style bulk handlers, load the current id set (`SELECT id FROM …`) and skip posted ids not in it; also gate the delete id.
**Why:** Otherwise a tampered POST silently operates on arbitrary ids (flagged High in code review even when prepared statements make it injection-safe).

## box-shadow is not logical-direction aware
In these always-RTL sites, the row accent bar uses `box-shadow: inset -3px 0 0 accent` — physical right = logical start only because dir=rtl is permanent. For any bilingual UI use a `::before` with `inset-inline-start` instead.
