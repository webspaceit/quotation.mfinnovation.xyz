# Deploy incident — 7 Nov 2026

## Symptom

Code changed locally, `push-project.bat` reported success, but
`https://quotation.mfinnovation.xyz/admin/users/index.php` still served the
old page. Separately, the site root `/` was returning **HTTP 500**.

## Root cause

The archive was built like this:

```powershell
Get-ChildItem -Path $PROJECT_DIR -Recurse -File | Compress-Archive -DestinationPath $zip
```

`Compress-Archive` writes each `FileInfo` under its **base name only**. The
resulting zip had no directories — 30-odd files at the top level. Extracting
it into the site root therefore:

- **never touched** `admin/users/index.php`, `includes/*`, `classes/*`,
  `config/*` — the nested paths simply did not exist in the archive;
- **overwrote** each root-level file with whichever same-named file came last
  in the enumeration. `index.php` at the site root became a copy of
  `admin/users/index.php` (19,244 bytes).

That stray `index.php` starts with `require_once '../../includes/auth.php'`,
which resolves above the document root, so `/` returned 500 for every visitor.

There was a second, quieter failure: the script excluded `.htaccess` from the
zip and then uploaded a freshly generated one containing only the PHP handler.
Every security rule added in the Phase 0 pass was silently discarded on each
deploy.

## Fix

`push-project.bat` now:

1. Stages a clean copy with **`robocopy /E`**, which preserves paths, excluding
   `.git`, `_quarantine`, `deploy`, `.env`, `.htaccess`, docs, `database.sql`,
   `README.md` and the script itself.
2. Zips the staged tree with `Compress-Archive -Path "<stage>\*"` — passing a
   directory glob preserves the hierarchy (32 entries, `admin/users/index.php`
   included).
3. Extracts with **`unzip -oq` over SSH** instead of `curl` to a PHP endpoint.
   `curl` on this machine fails with `schannel: SEC_E_NO_CREDENTIALS`, so the
   old HTTP trigger could fail silently behind `2>nul`.
4. Uploads `deploy/server.htaccess`, which contains the host's PHP handler block
   **and** the security rules, so hardening survives deploys.
5. **Verifies** the result: compares local vs remote byte size of
   `admin/users/index.php`, asserts root `index.php` is 188 bytes, and checks
   `https://…/login.php`. A mismatch prints `DEPLOY COMPLETED WITH PROBLEMS`
   instead of a cheerful success message.

## Recovery performed

- Server-side backup: `~/qms_backup_.tar.gz` (162 KB).
- Correct archive uploaded and extracted with `unzip -o`; ownership stayed
  `mfinnovation.xyz`, so SFTP can overwrite it next time.
- Verified: root `index.php` = 188 bytes, `admin/users/index.php` = 19,244 bytes
  with `js-edit` present, `/` returns 302 to `login.php` instead of 500,
  `/login.php` returns 200.

## Still outstanding on the live server

| Item | Status |
|---|---|
| `fix_password.php` — resets admin password on any anonymous GET | **live** |
| `install.php`, `sample.php`, `add_cols2.php` | **live** |
| `fix_top_save.php`, `fix_revert_top.php` — write to settings file | **live** |
| `check_top2.php`, `check_bottombtn.php`, `check_db.php`, `check_users1-5.php`, `fix_perms.php`, `testdir/` | **live** |
| `database.sql` — schema readable over HTTP (200, 5,216 bytes) | **live** |
| Flattened duplicates at site root (`create.php`, `dashboard.php`, `edit.php`, `view.php`, `preview.php`, `duplicate.php`, `User.php`, `functions.php`, `header.php`, …) | present, each 500s |
| Hardened `.htaccess` | not yet applied |

`.env` is already blocked by the host (403), so database credentials are not
exposed.
