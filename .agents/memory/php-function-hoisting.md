---
name: PHP function hoisting vs includes
description: Duplicate helper defined in a page file fatals inside the shared include, with a confusing error attribution.
---

PHP compiles unconditional top-level `function` declarations in a file when the file is parsed — BEFORE any `require` on an earlier line executes. So a page that does `require bootstrap.php;` and later declares `function set_setting()` makes the *bootstrap* file the one that fatals ("Cannot redeclare … previously declared in page.php:6 in bootstrap.php:92"), which misattributes the bug to the shared include.

**Why:** cost a debugging cycle on the Kamal site — the fatal surfaced on a page that merely shared the session, and HTTP status stayed 200 with display_errors on, so status-code smoke tests missed it.
**How to apply:** when adding helpers to a PHP page that includes a shared bootstrap, grep the includes for the name first; and smoke-test PHP sites by grepping response BODIES for `Fatal error|Uncaught`, not just status codes.
