# Privacy operations

Everything here is enforced by code in `app/Support/Privacy` and configured in `config/privacy.php`. Items marked **[infrastructure]** are not controlled by the application.

## Retention (what the code does)

| Data | Rule | Mechanism |
|---|---|---|
| Participation (event–company link, status, stand, fee/terms) | Kept indefinitely | Never deleted; FKs preserved |
| Enrollment contact name/email, full answers, uploads, invitations, second-form submissions | Removed the day after the event (`privacy.retention.enrollment_contact_days_after_event`, default 0; a grace period needs a documented purpose) | `privacy:cleanup`, marker `personal_data_purged_at` |
| Rejected applications | Personal data removed 3 calendar months after `reviewed_at` | same |
| Pending applications | Never touched | |
| Dietary answers (`lunch_diet`, `lunch_diet_other`, or fields flagged `sensitive` in a form schema; whole repeater row removed) | 7 days after the event day | `sensitive_purged_at` |
| Borrel guests | name/email removed the day after the event (default 0); row kept for counts | `personal_data_purged_at` |
| Staff accounts (`users`) | Never auto-removed; review yearly and after each edition | admin Users resource |
| `companies.profile_contact_email` | Not touched (contact for the company's own profile link) | |
| Application/security logs | 90 days (`LOG_DAILY_DAYS`, default `daily` channel) | Laravel daily rotation |
| Audit log (`activity_log`) | `audit:prune`, scheduled by default, 90 days for auth/security | `config/audit.php` |
| Failed jobs | 72 hours | `queue:prune-failed` |
| Database backups | 28 days | **[infrastructure]** |

Event end is the end of the event `date` in `PRIVACY_TIMEZONE` (Europe/Amsterdam). Preview: `php artisan privacy:cleanup --dry-run` (counts only). Runs are recorded as counts in `privacy_runs`. Cleanup is idempotent and state based.

## Scheduler

Production needs the standard Laravel cron entry: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`. `privacy:cleanup` then runs daily at 03:15 Europe/Amsterdam.

## Email encryption

Emails are stored with Laravel's AES-256-GCM encrypter under `EMAIL_ENCRYPTION_KEY`; exact lookup uses HMAC-SHA256 of the normalised address (trim + lowercase) under a separate `EMAIL_LOOKUP_KEY`, stored in `*_hash` columns. Unique rules (borrel per event, newsletter) use the hash. Search in admin tables is exact-match on those columns. Not encrypted: `users.email` and `password_reset_tokens` (needed for login and reset; staff only) — a deliberate limit.

Deploy:
1. `php artisan privacy:generate-keys`; put both in the production `.env` (production refuses to run without them). Back them up outside the database.
2. `php artisan migrate` (schema only, no data change).
3. `php artisan privacy:encrypt-emails --dry-run`, then without the flag. Each value is decrypted and compared before it is written; reruns are safe. Until then plaintext rows stay readable.
4. Take a fresh backup afterwards; older backups still hold plaintext until they expire (28 days).

Rotation: add the old keys to `*_PREVIOUS_KEYS`, set new current keys, run `php artisan privacy:encrypt-emails --rotate`, remove old keys once finished. Losing a key means the affected emails are unrecoverable.

## Erasures and backup restores

Manual erasures are recorded by `ErasureLedger`: deleting an enrollment in the admin panel, and `php artisan privacy:erase-email <address>` (erases newsletter, borrel guest, contact-change and enrollment personal data for that address). Each entry holds a type, an enrollment id or a **keyed email hash** and a time, never an address. Entries are written to the database (`privacy_erasures`) **and** appended to a file outside it (`PRIVACY_ERASURE_LEDGER_PATH`, default `storage/app/private/privacy/erasure-ledger.jsonl`, mode 0600). Entries older than `PRIVACY_ERASURE_LEDGER_DAYS` (35, i.e. backup retention + margin) are dropped by the daily cleanup so the ledger does not grow into a permanent list.

Restoring an older database backup removes the database copy of recent erasures, which is why the file exists. **Requirement outside the app:** the ledger path must NOT be part of what is restored with the database. Put it on a separate path/volume and back it up separately (e.g. a different backup job). If the whole server is restored from one snapshot, the ledger goes back too and this protection is lost; keep a copy elsewhere.

Restore procedure:
1. Restore the database; keep the application in maintenance mode (`php artisan down`).
2. Restore the same `EMAIL_*` keys that were active at backup time (see Key management).
3. `php artisan privacy:replay-erasures --dry-run`, review the counts, then without `--dry-run`. It replays ledger entries (an enrollment id is only erased if the restored row was created before the erasure, so reused ids are safe) and runs the time-based retention. Idempotent.
4. `php artisan up`.

Limitations: erasures older than the ledger window, and any manual removal not made through these paths (direct SQL, other tables such as access/interest requests), are not replayed. Hashes recorded before a lookup-key rotation do not match rows hashed under the new key; re-run `privacy:erase-email` for open requests after rotating.

## Key management

- Keys: `php artisan privacy:generate-keys` prints two values; they are never written by the app. Store them in the server environment and in a password manager / secret store that is separate from database backups.
- Without the keys the encrypted emails are unrecoverable. A restored database backup needs the keys that were current when it was taken, so keep every retired key until all backups encrypted under it have expired (28 days) and then remove it.
- Rotation: set new `EMAIL_*` keys, put the old ones in `*_PREVIOUS_KEYS`, run `privacy:encrypt-emails --rotate` (resumable: reads use previous keys, so an interrupted run leaves everything readable; rerun to finish), then remove the previous keys.
- Missing key in production: the application throws instead of falling back to APP_KEY. Invalid or short keys throw as well.

## Threat model (what encryption does and does not do)

- Database-only breach (SQL dump, injection that only reads the database): emails of enrollments, borrel, newsletter and requests are ciphertext; the keys are not in the database. Meaningful reduction.
- Database backup alone: same, provided the keys are not stored with the backup. Backups made before the backfill still contain plaintext until they expire.
- Application or server compromise: the keys are available to the app, so an attacker with code execution can read them. No protection.
- Key compromise: treat as breach of confidentiality for everything encrypted under that key; rotate.
- Not covered: `users.email` and `password_reset_tokens`, the audit log's hashed identifiers, web server logs, outgoing mail provider.

## `users.email` and `password_reset_tokens` (not encrypted, deliberate)

Technically possible but not done, because it touches Laravel's authentication core: the user provider looks users up by `email` in the Fortify/Filament login, password broker, Breezy two-factor flows and the `unique:users` validation, the Spatie permission and Filament user resources query it, `password_reset_tokens.email` is the primary key used by the broker, and notifications resolve the address via `routeNotificationFor('mail')`. Doing it safely needs a custom user provider and password broker, a hash column with a unique constraint and a tested staged migration, and none of that could be validated without a MySQL server here. Staff addresses are therefore plaintext (staff only, small, needed for sign-in). Revisit if the threat model requires it.

## Retention versus the original requirement

The requirement was to delete enrollment emails after the event. The default is now 0 days (the day after the event), matching it. A grace period is only acceptable with a documented operational purpose (then set `PRIVACY_ENROLLMENT_CONTACT_DAYS` / `PRIVACY_BORREL_DAYS`). Handled company access requests lose their personal data 3 months after handling (`PRIVACY_ACCESS_REQUEST_MONTHS`, proposed). Contact-form messages are only emailed, not stored by the site, so their retention is a mailbox matter. Rejected applications keep `company_name`, which can identify a sole trader; the organisation must decide.

## Erasure ledger keys

Email erasure entries use `PRIVACY_ERASURE_KEY`, a stable key that is NOT rotated with the lookup key, so entries stay matchable after a rotation (replay decrypts candidate rows in batches and compares). Back it up with the other keys; losing it makes email-based entries unusable. Obsolete entries are removed after at least backup retention + 7 days.

## Infrastructure checklist [infrastructure]

- Backups: 28-day retention on the backup system (e.g. Proxmox/PBS prune policy); encrypted, access restricted. The app does not manage or delete backups.
- Nginx/PHP-FPM/OS logs (access logs contain IP addresses, URLs): rotate with logrotate for at most 90 days; ensure invitation URLs (`/informatie/{token}`) are not retained longer than that, or shorten access-log retention.
- Mail provider/hosting: confirm processing agreements and data location.
- Alerting: set `AUDIT_ALERT_MAIL_TO` (existing audit alert mail) if wanted.

## Account review

Review the Users list in the admin panel yearly and after each edition; remove roles/accounts of people no longer authorised. Nothing is removed automatically for inactivity.
