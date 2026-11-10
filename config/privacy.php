<?php

/*
| Privacy settings: retention periods, the timezone in which an event "ends", and the keys that protect stored
| email addresses. Every retention period lives here so none is scattered through the code.
| See docs/privacy/ for what each value means and for the operational steps.
*/

return [

    // An event ends at the end of its date in this zone (events have a date, not a time).
    'timezone' => env('PRIVACY_TIMEZONE', 'Europe/Amsterdam'),

    'retention' => [
        // Dietary and allergy answers are removed this many days after the event ended.
        'dietary_days_after_event' => (int) env('PRIVACY_DIETARY_DAYS', 7),

        // Private enrollment data (contact details, full answers, uploads, invitations) of an event is removed this many
        // days after the event ended. The enrollment record, company link, status and stand allocation stay.
        // CONFIRM with the data controller: how long is invoicing/follow-up contact needed after the event?
        'enrollment_contact_days_after_event' => (int) env('PRIVACY_ENROLLMENT_CONTACT_DAYS', 0),

        // Personal data of a rejected application is removed this many calendar months after it was finally handled.
        'rejected_months_after_handling' => (int) env('PRIVACY_REJECTED_MONTHS', 3),

        // Guests of the networking drinks: name and email are removed this many days after the event ended.
        'borrel_days_after_event' => (int) env('PRIVACY_BORREL_DAYS', 0),

        // Documented here so the policy text and the infrastructure checklist read one value. The security and audit
        // categories are enforced by config/audit.php (AUDIT_RETENTION_SECURITY_DAYS), the application log file by
        // config/logging.php (LOG_DAILY_DAYS), failed queue jobs by the scheduler.
        'security_log_days' => (int) env('AUDIT_RETENTION_SECURITY_DAYS', 90),
        'application_log_days' => (int) env('LOG_DAILY_DAYS', 90),
        'failed_jobs_hours' => 72,

        // Not enforced by the application: the database backups are made by the infrastructure.
        'backup_days' => (int) env('PRIVACY_BACKUP_DAYS', 28),

        // A review of who still has access is due at least this often, and after each edition.
        'access_request_months_after_handling' => (int) env('PRIVACY_ACCESS_REQUEST_MONTHS', 3), // proposed, awaiting approval
        'account_review_months' => (int) env('PRIVACY_ACCOUNT_REVIEW_MONTHS', 12),
    ],

    // Stable form field keys that always hold sensitive (dietary/allergy) answers, for forms published before fields
    // could carry a "sensitive" flag. A field flagged `sensitive: true` in a form schema is treated the same way.
    'sensitive_field_keys' => ['lunch_diet', 'lunch_diet_other'],

    // Rows processed per batch by the cleanup.
    // Append-only ledger of manual erasures, kept OUTSIDE the database so restoring an older database backup cannot erase it.
    // Point this at a location that is not restored together with the database (see docs/privacy/README.md).
    'erasure_ledger' => [
        'path' => env('PRIVACY_ERASURE_LEDGER_PATH', storage_path('app/private/privacy/erasure-ledger.jsonl')),
        'keep_days' => (int) env('PRIVACY_ERASURE_LEDGER_DAYS', 35), // backup retention (28) plus a safety margin
    ],

    // Consent for promotional email. Bump the version whenever the wording (lang messages.marketing_consent_text) changes.
    'marketing_consent' => ['version' => env('PRIVACY_MARKETING_CONSENT_VERSION', '2026-11-v1')],

    'batch_size' => 200,

    /*
    | Email protection. Values are base64 encoded 32-byte keys ("base64:..."), generated with
    | `php artisan privacy:generate-keys`. They live in the environment, never in the database or source control.
    | In production both keys are required; locally and in tests they are derived from APP_KEY.
    */
    'email' => [
        'cipher' => 'aes-256-gcm',
        'encryption_key' => env('EMAIL_ENCRYPTION_KEY'),
        'lookup_key' => env('EMAIL_LOOKUP_KEY'),
        // Comma separated keys that stay readable while rotating (old key first is fine).
        'previous_encryption_keys' => env('EMAIL_ENCRYPTION_PREVIOUS_KEYS'),
        'previous_lookup_keys' => env('EMAIL_LOOKUP_PREVIOUS_KEYS'),
        // Stable key for the erasure ledger only. It is NOT rotated with the lookup key, so old ledger entries keep matching.
        'erasure_key' => env('PRIVACY_ERASURE_KEY'),
    ],
];
