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

## Cleanup performed (with owner approval)

Deleted from the live server:

- Legacy scripts: `fix_password.php`, `install.php`, `sample.php`,
  `add_cols2.php`, `fix_top_save.php`, `fix_revert_top.php`, `check_top2.php`,
  `check_bottombtn.php`, `check_db.php`, `fix_perms.php`, `check_users1-5.php`
- `testdir/` (contained `check_db.php`, `create_db.php`)
- 20 flattened duplicates at the site root (`create.php`, `dashboard.php`,
  `edit.php`, `view.php`, `preview.php`, `duplicate.php`, `User.php`,
  `functions.php`, `header.php`, `footer.php`, `auth.php`, `database.php`,
  the five class files, `app.js`, `style.css`, `quotation.js`, `send_email.php`)
- `database.sql`, `.gitignore`, `push-project.bat`, `README.md`

Site root is now exactly: `.env`, `.htaccess`, `index.php`, `login.php`,
`logout.php` and the six application directories.

Hardened `.htaccess` deployed (`deploy/server.htaccess`): host PHP handler plus
`Require all denied` for `*.sql`, `*.md`, `*.zip`, dotfiles, and no code
execution under `assets/uploads/`.

### Verification after cleanup

```
27 files compared by MD5, local vs server   -> 0 mismatches
/                                           -> 302 login.php
/login.php                                  -> 200
/assets/css/style.css                       -> 200
/fix_password.php /install.php /sample.php  -> 404
/check_top2.php /fix_perms.php /check_users.php -> 404
/testdir/  /style.css                       -> 404
/database.sql  /.env                        -> 403
```

### Password reset — action required

While verifying, a plain GET to `/fix_password.php` was used to confirm the
file was still reachable. It **executed**, resetting the live admin password to
`admin123`. The file has since been deleted, but:

**Change the production admin password now** — sign in, open
`admin/users/index.php`, click Edit on your own row, set a new password.

`.env` is blocked by the host (403), so database credentials were not exposed.

---

## Follow-up — favicon upload 500 (7 Nov 2026)

The live database was a schema behind the code. `company_settings` had 20
columns; the deployed `Company::update()` writes 32. Saving settings threw
`SQLSTATE[42S22]: Unknown column 'favicon'`, uncaught, which Apache rendered as
a bare 500 — nothing to do with the upload itself.

Migration applied to production (additive only, backups first):

| Table | Added | Backup |
|---|---|---|
| `company_settings` | `favicon`, `block_typography`, `default_authorized_signature`, `table_font_family`, `table_font_size`, `table_font_style`, `table_text_color`, `table_line_height`, `table_font_weight`, `table_letter_spacing`, `table_text_transform`, `table_text_decoration` | `company_settings_bak_20261107` |
| `quotations` | `signature_image`, `authorized_signature` | `quotations_bak_20261107` |

All six tables now match local column for column. `Company::update()` runs
clean on the server.

Second bug fixed behind it: `uploadFile()` allowed only
`jpg/jpeg/png/gif/webp`, so a real `.ico` favicon would have been rejected with
"Favicon upload failed" even after the schema fix. `ico` is now allowed.
Verified locally end-to-end: upload → DB `favicon` = `<id>.ico` → file on disk.

`deploy/uploads.htaccess` is now uploaded by the deploy script, so the uploads
directory can never execute code on the server.
