# Audit Log and Security Monitoring

The dashboard keeps an audit trail of sign-ins, security events and changes to content and access. It is built on [spatie/laravel-activitylog](https://spatie.be/docs/laravel-activitylog/v4) (v4) and stored in the `activity_log` table.

## Where to find it

| Page | URL | Permission |
| --- | --- | --- |
| Administration > Audit log | `/dashboard/audit-logs` | `audit.view` |
| Audit entry details | `/dashboard/audit-logs/{id}` | `audit.view` |
| Administration > Security overview | `/dashboard/security` | `security.view` |
| Users > edit > "Activity by this user" / "Changes to this account" | `/dashboard/users/{id}/edit` | `audit.view` |

| Permission | Allows |
| --- | --- |
| `audit.view` | Read the audit log and user activity history. |
| `audit.export` | Download the filtered log as CSV. Each export is logged (`audit.exported`). |
| `audit.manage-retention` | Run the retention cleanup from the audit log page. |
| `security.view` | Open the Security overview. |
| `security.manage-alerts` | Acknowledge security alerts. |

Roles that held the old `audit-logs.view` permission received `audit.view` automatically when permissions were synced. The Administrator role does **not** get `audit.export`, `audit.manage-retention`, `security.view` or `security.manage-alerts` by default; a Super Admin grants them per role.

Showing the actor's email address on an entry also requires `users.view`.

## What is logged

Every entry has: ID, time (stored in UTC, shown in `APP_TIMEZONE`), category, event name, severity, result (`success`, `failure`, `denied`), actor (user ID, name at the time, type `user` / `guest` / `system`), IP address, user agent, HTTP method, route name, path, request ID, affected resource (type, ID, name) and the changed values.

Event names are `category.action[.detail]`:

| Category | Events |
| --- | --- |
| `auth` | `auth.login.success`, `auth.login.failed`, `auth.logout`, `auth.password.reset_requested`, `auth.password.reset`, `auth.password.changed`, `auth.email.verified`, `auth.session.revoked` (signed out other browser sessions) |
| `security` | `security.access.denied` (403), `security.privilege.denied` (role or account change refused), `security.login.lockout`, `security.csrf.failed` (signed-in users only), `security.alert.acknowledged` |
| `users` | `users.created`, `users.updated`, `users.deleted` |
| `roles` | `roles.created`, `roles.updated`, `roles.deleted`, `roles.assigned`, `roles.revoked` |
| `permissions` | `permissions.updated` (permissions of a role; with added/removed/old/new) |
| `settings` | `settings.updated` |
| `audit` | `audit.exported`, `audit.retention.pruned` |
| `system` | `system.job.failed` |
| content | `<module>.created/updated/deleted` for events, companies, partners, education, sectors, event stands and map points, access requests and profile submissions (review fields only), borrel enrollments (changes and deletions, without email), interest requests and newsletter subscribers (deletions only, without contact details) |

Model changes record only attributes that actually changed, with old and new values. Saving without changes writes nothing.

**Never logged:** passwords and hashes, remember tokens, 2FA secrets and recovery codes, company profile tokens, reset tokens, CSRF tokens, session IDs, request bodies and query strings. The list is in `config/audit.php` (`sensitive_attributes`) and is applied to every entry. Route parameters that grant access (`{token}`, `{signature}`, ...) are replaced by their name in the logged path.

**Failed sign-ins** store the attempted address masked (`ja***@example.com`) plus a keyed hash, so repeated attempts against one account can be grouped without storing the address. An attempt is linked to an account only when the address matched an existing account.

**Not tracked:** account activation, deactivation and suspension (the application has no such states), API clients (there is no API), page views (no page-access log; it would mostly be browsing history), and device fingerprints.

## Severity

`info`, `warning`, `high`, `critical`. Defaults per event are in `config/audit.php` (`severity`). Some are raised by context:

- Assigning or revoking the Super Admin role: `high`.
- Removing users/roles/permissions/audit/security permissions from a role: `high`.
- A refused role or account change: `high`; when it targets the Super Admin role or the last Super Admin: `critical`.

A `403` alone is `warning`, not an incident.

## Security alerts

Rules in `config/audit.php` (`alerts.rules`) count matching entries in a time window:

| Rule | Default |
| --- | --- |
| Failed sign-ins from one IP | 10 in 15 minutes |
| Failed sign-ins for one account | 5 in 15 minutes |
| Denied access by one user (or anonymous IP) | 10 in 10 minutes |
| Any `high` or `critical` event | 1 |

One open alert exists per rule and source; repeats only raise its count, so bursts don't flood the overview. Acknowledging an alert closes it; new activity then opens a new one. Set `AUDIT_ALERT_MAIL_TO` to also receive an email when an alert opens. An alert is a pattern worth reviewing, not proof of an attack: shared networks and forgotten passwords trigger them too.

## Retention

Defaults (environment variables in brackets) are starting points, not legal requirements:

| Category | Days |
| --- | --- |
| `auth`, `security` | 90 (`AUDIT_RETENTION_SECURITY_DAYS`) |
| `users`, `roles`, `permissions`, `settings`, `audit` | 365 (`AUDIT_RETENTION_ADMIN_DAYS`) |
| everything else | 180 (`AUDIT_RETENTION_GENERAL_DAYS`) |

Entries with severity `high` or `critical` are kept until the longest period has passed. Acknowledged alerts are removed after the security period.

```bash
php artisan audit:prune --dry-run   # show what would be removed
php artisan audit:prune             # remove it (logged as audit.retention.pruned)
```

The cleanup does **not** run on its own. Review your requirements, then set `AUDIT_RETENTION_SCHEDULED=true` so the scheduler runs it daily (the server needs `php artisan schedule:run` every minute). Don't use spatie's `activitylog:clean`: it ignores categories and protected entries.

## Privacy

IP addresses, user agents, names and email addresses in the log are personal data.

- **Why:** to know who changed what, to investigate sign-in and access problems, and to detect abuse of accounts.
- **Who can see it:** only roles granted `audit.view` / `security.view`. Exports need `audit.export` and are themselves logged.
- **How long:** see Retention. Nothing is kept indefinitely once the cleanup is scheduled.
- **Deleted accounts:** entries keep the user ID and the name at the time, so history stays readable. Retention removes them in time; to erase a person sooner, delete their entries by `causer_id` / `subject_id` in the database.
- **No geolocation** and no device fingerprinting.

## Client IP behind a proxy

By default the connecting address is logged and `X-Forwarded-For` is ignored, so it can't be spoofed. If the site runs behind a load balancer or proxy, set `TRUSTED_PROXIES` to its address(es), comma separated, or `*` only if the app cannot be reached except through the proxy.

## Request IDs

Every response carries an `X-Request-Id` header. All entries written during that request share it; click the request ID on an entry to see them together.

## Tamper resistance

The dashboard has no way to edit or delete entries, the model refuses updates and deletes, and only `audit:prune` removes rows. This does **not** make the log tamper-proof: anyone with write access to the database can change it. For stronger guarantees, restrict the application's database user to `INSERT`/`SELECT` on `activity_log` (cleanup then needs a separate user), or ship entries to external append-only storage.

Entries are written synchronously, in the same request, so a stopped queue worker can't lose them. Security events (403s, refused privilege changes) are written after the failed request's transaction has rolled back, so they are kept.

## Upgrading from the old audit log

The migration copies existing `audit_logs` rows into `activity_log` (old details under "Legacy"). The `audit_logs` table is left in place; drop it once you've checked the copy.
