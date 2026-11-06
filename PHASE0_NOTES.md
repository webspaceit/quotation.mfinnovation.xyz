# Phase 0 — Applied 6 Nov 2026

Security hardening pass. Every change is verified against the running Apache
instance at `http://localhost/qms`, not assumed.

---

## 1. Baseline first

`git init` + baseline commit `91ce69d` captured the project **before** any edit.
Every file removed below is recoverable with:

```
git checkout 91ce69d -- <filename>
```

`.gitignore` excludes `.env` (secrets) and `assets/uploads/*` (user media).

## 2. Removed from the web root

| File | Why |
|---|---|
| `fix_password.php` | Reset the admin password to `admin123` on an anonymous GET |
| `install.php` | Hardcoded `root`/empty DB password, recreated the schema |
| `sample.php` | Inserted demo rows on demand |
| `add_cols2.php` | Ran `ALTER TABLE` on demand |
| `fix_top_save.php` | `file_put_contents()` into `admin/settings/index.php` |
| `fix_revert_top.php` | Same file-write primitive, reverse direction |
| `check_top2.php` | Echoed the source of `admin/settings/index.php` |
| `check_bottombtn.php` | Same, different fragment |

`install.php` and `sample.php` went to the Windows Recycle Bin; the other six
were moved to `_quarantine/root-scripts-2026-11-06/`. Both locations are
recoverable, and `_quarantine/` is blocked over HTTP.

## 3. `.htaccess` hardening

```
.env, .git, dotfiles          -> 403   (.well-known excluded for ACME)
*.sql *.md *.log *.bak *.zip  -> 403
config/ classes/ includes/ vendor/ _quarantine/ -> 403
assets/uploads/*.php|phtml|phar|cgi|pl -> 403
```

Plus `assets/uploads/.htaccess` turning the PHP engine off inside the upload
directory, as defence in depth behind `uploadFile()`'s extension check.

## 4. Code changes

| File | Change |
|---|---|
| `ajax/send_email.php` | **Added authentication** (`isLoggedIn()` → 401). CSRF failure now returns JSON 403 instead of a plain-text die. Validates `to`/`cc`/`bcc` with `filter_var`, strips CR/LF from the subject, normalises message line endings. Quotation ID must be a positive integer. |
| `admin/settings/index.php` | Admin-only guard added — `staff` users were able to edit SMTP credentials |
| `config/database.php` | Session cookie now `HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS; skipped if a session is already active |
| `includes/functions.php` | New `csrf_valid()` using `hash_equals()` (timing-safe); `verify_csrf()` delegates to it and returns 419 on failure |
| `classes/Database.php` | Connection failure no longer echoes the PDO message (leaked host/user); logs it and shows a generic 500 |

## 5. Verification

```
.env                              -> 403
database.sql                      -> 403
README.md / AUDIT*.md             -> 403
config/database.php               -> 403
assets/uploads/sectest.php        -> 403
assets/css/style.css              -> 200
fix_password.php                  -> 404

login (admin)                     -> 302, dashboard 200
login (test staff)                -> 302, dashboard 200
staff -> admin/settings/index.php -> 302 (blocked)
staff -> admin/users/index.php    -> 302 (blocked)

POST ajax/send_email.php, anonymous          -> 401 JSON
POST ajax/send_email.php, bad CSRF, signed in-> 403 JSON
GET  ajax/send_email.php, signed in          -> 405 JSON
POST ajax/send_email.php, bad recipient      -> 400 JSON

php -l on all 24 application files           -> 0 errors
```

The temporary staff account used for the privilege test was deleted;
`users` is back to a single admin row.

## 6. Known side effect

`fix_password.php` was executed during the read-only audit (by requesting the
URL to confirm it was reachable), which reset the admin password to
`admin123`.

**Action for you:** change the admin password. There is still no self-service
password UI — that arrives with the "Access" items in §6 of the audit
(Phase 4). Until then you can set one with:

```
php -r "$p=new PDO('mysql:host=localhost;dbname=qms_db','root','');$p->prepare('UPDATE users SET password=? WHERE email=?')->execute([password_hash('YOUR_NEW_PASSWORD',PASSWORD_DEFAULT),'admin@qms.com']);"
```

## 7. Deliberately not done in Phase 0

- `database.sql` still sits in the project (needed for phpMyAdmin import) but is HTTP-blocked.
- The email attachment is still an `.html` dump labelled "PDF" — Phase 2.
- Print pagination (repeating totals) — Phase 2.
- DB transactions, item-deletion ordering, quotation numbering — Phase 1.
