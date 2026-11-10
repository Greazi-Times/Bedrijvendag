# Production deployment plan (not executed)

Do everything on a staging copy first. No step below has been run against production.

1. **Before anything**: verify a fresh database backup restores. Back up `.env` and `storage/app/private`.
2. **Environment**: `php artisan privacy:generate-keys` on a trusted machine; put `EMAIL_ENCRYPTION_KEY` and `EMAIL_LOOKUP_KEY` in the server environment and a separate secret store. Set `PRIVACY_ERASURE_LEDGER_PATH` to a location that is backed up separately from the database. Set `LOG_STACK=daily`, `LOG_DAILY_DAYS=90`, `AUDIT_RETENTION_SCHEDULED=true`. Optional: `AUDIT_ALERT_MAIL_TO`.
3. **Scheduler**: cron `* * * * * php artisan schedule:run`. Queue workers must run with the same environment; restart them after deploy (`php artisan queue:restart`). Jobs queued before deploy remain readable.
4. **Deploy code, then `php artisan migrate`** (schema only; it changes column types and swaps the borrel/newsletter unique indexes). Check on staging that existing duplicate emails on borrel (per event) or newsletter do not block the new unique hash indexes: hashes are created by the backfill, nulls do not collide.
5. **Backfill**: `php artisan privacy:encrypt-emails --dry-run`, review counts, then run it; rerun until `remaining_plaintext` is 0. Take a new backup afterwards.
6. **Cutover**: legacy plaintext lookups remain as a fallback for rows whose hash is null. After the backfill reports 0 remaining, the fallback is harmless; removing it is a later code change.
7. **Retention**: `php artisan privacy:cleanup --dry-run` and review the counts. Nothing is deleted until the first real run (or the 03:15 schedule). Do an explicit operational review of what will be purged before the first run, and decide whether to temporarily disable the schedule.
8. **Policies**: the three policy pages (admin: Settings) can load drafts from `policies/`, but saving is blocked while open items remain. Publish only after legal sign-off.
9. **Post-deployment checks**: login and password reset, a test enrollment in staging, `schedule:list`, `privacy:account-review`.

**Rollback**: do not roll back the code after the backfill without restoring the keys; older code reads encrypted columns as garbage. Rollback options: (a) stay on the new code and fix forward; (b) restore the pre-backfill database backup, which still holds plaintext, and the old code, then replay erasures made since (`privacy:replay-erasures`). Never delete the encryption keys while any backup or live data depends on them.

**Infrastructure to configure by hand**: backup retention (28 days), Nginx/PHP-FPM/OS log rotation (<= 90 days), server clock and timezone, mail provider settings, separate storage for the erasure ledger.
