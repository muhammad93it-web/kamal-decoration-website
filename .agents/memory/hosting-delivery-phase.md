---
name: Hosting delivery phase (Namecheap)
description: Whether zip deliveries for the two decoration sites may wipe the hosted database or must preserve it.
---

**Status (as of 2026-08-09): TESTING phase.** The user said data loss is fine for now — they re-upload zips and re-import `database.sql` freely while trying things out on Namecheap cPanel.

**Rule:** The user will explicitly say when testing is over and the site content becomes real. From that moment:
- Never instruct a full `database.sql` re-import (it drops/reseeds everything).
- Deliver updates as file-only zips, plus a separate small migration `.sql` for any schema/seed changes (e.g. `update-vX.sql` with only ALTER/INSERT statements).
- Remind them to back up the DB via phpMyAdmin export before any SQL change.

**Why:** Full seed import erases products, settings, messages, and uploads references they entered on the live site.
