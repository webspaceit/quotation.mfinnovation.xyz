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
any path segment starting with "."   -> 403   (.env, .git/, .htaccess; .well-known excluded)
*.sql *.md *.log *.bak *.zip         -> 403
config/ classes/ includes/ vendor/ _quarantine/ -> 403
assets/uploads/*.php|phtml|phar|cgi|pl -> 403
```

Plus `assets/uploads/.htaccess` turning the PHP engine off inside the upload
directory, as defence in depth behind `uploadFile()`'s extension check.

**Caught during verification:** the first version used `<FilesMatch "^\.…>` only,
and `/.git/config` returned **200** — `FilesMatch` matches the file *name*
(`config`), not the dot-directory it sits in. That would have exposed the whole
repository, including this baseline commit. Replaced with a per-directory
`RewriteRule (^|/)\.` that blocks any dot segment, with `.well-known` excluded
so ACME certificates still work. Also removed two dangling `RewriteCond` lines
that had no `RewriteRule` attached and were silently attaching themselves to the
next rule.

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
/login.php                       -> 200
/index.php                       -> 302 to login.php
/assets/css/style.css            -> 200
/.env                            -> 403
/.git/config                     -> 403
/.git/HEAD                       -> 403
/.gitignore                      -> 403
/.htaccess                       -> 403
/database.sql                    -> 403
/README.md                       -> 403
/AUDIT_2026-11-06.md             -> 403
/PHASE0_NOTES.md                 -> 403
/config/database.php             -> 403
/includes/functions.php          -> 403
/classes/User.php                -> 403
/_quarantine/…/fix_password.php  -> 403
/assets/uploads/.htaccess        -> 403
/fix_password.php                -> 404
/install.php                     -> 404
/sample.php                      -> 404

login (admin)                     -> 302, dashboard 200
login (test staff)                -> 302, dashboard 200
staff -> admin/settings/index.php -> 302 (blocked)
staff -> admin/users/index.php    -> 302 (blocked)

POST ajax/send_email.php, anonymous          -> 401 JSON
POST ajax/send_email.php, bad CSRF, signed in-> 403 JSON
GET  ajax/send_email.php, signed in          -> 405 JSON
POST ajax/send_email.php, bad recipient      -> 400 JSON

all 9 admin pages (dashboard, quotations list/create/view/edit/preview,
clients, users, settings)                    -> 200, no PHP warnings

php -l on all 24 application files           -> 0 errors
```

20/20 exposure checks pass.

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

---

## Addendum — 7 Nov 2026: user access management

`admin/users/index.php` could previously only **create** users. There was no
way to change a role, reset a password, rename, or remove access — `User` had
no `update`/`delete`/`getById` methods at all.

Added:

- **Edit** per row: name, email, role, and an optional new password
  (blank keeps the existing hash).
- **Delete** per row, behind a confirm dialog.
- Row marked **You** for the signed-in account; its delete button is hidden.
- `User::getById()`, `emailExists()`, `update()`, `delete()`, `countAdmins()`.

Guard rails (server-side, not just UI):

| Rule | Behaviour |
|---|---|
| Delete own account | Rejected — "cannot delete the account you are signed in with" |
| Demote/delete last admin | Rejected — "last administrator account" |
| Change own role | Silently forced back to the current role; name/email/password still save |
| Password < 8 chars on create/reset | Rejected |
| Duplicate email | Rejected (excludes the user being edited) |
| Non-admin opening the page | 302 to dashboard |

Verified end-to-end: create → update role+password → login with the new
password → delete → self-delete blocked → self-demotion blocked → blank
password leaves the hash untouched. 9/9 checks pass.

Also fixed: `verify_csrf()` used status **419**, which Apache rewrites to
**500**. An expired form token looked like a server crash. Now 403.
