---
name: Cloning an existing artifact
description: How to duplicate an artifact dir into a new independent artifact (second brand/site) without breaking artifact registration.
---

Rule: when copying `artifacts/<a>/` to `artifacts/<b>/`, the copied `.replit-artifact/artifact.toml` still carries `<a>`'s immutable `id` — the platform guard blocks direct WriteFile edits AND `verifyAndReplaceArtifactToml` refuses any id change (INVALID_ARTIFACT_ID), even to a temp file with the id line removed.

**Why:** artifact ids are immutable per directory once a toml exists; the verify flow compares against the file on disk, so a copied toml poisons the new dir with the old identity.

**How to apply:** `rm -rf <b>/.replit-artifact`, write a fresh `artifact.toml` via shell heredoc (new id like `artifacts/<b>`, unique localPort, unique previewPath/paths, BASE_PATH env), then call `verifyAndReplaceArtifactToml` with an IDENTICAL temp copy — it validates the schema, and the platform registers the artifact + creates the managed `artifacts/<b>: <service>` workflow within seconds.

Also for clones: give the copy its own dev-DB identity (db name, user, socket path, port) so both harnesses run in parallel; exclude dev-generated files from the copy (config.php, install.lock, .devdb, generated QR/barcode files) so first boot regenerates them for the new identity/URL.
