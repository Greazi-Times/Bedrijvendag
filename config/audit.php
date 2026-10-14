<?php

/*
| Audit logging and security monitoring. Entries are stored by
| spatie/laravel-activitylog in the activity_log table, through
| App\Models\AuditLog. See docs/audit-logging.md for what is collected and why.
*/

return [

    // Timestamps are stored in UTC (config app.timezone) and shown in this zone.
    'display_timezone' => env('APP_TIMEZONE', 'Europe/Amsterdam'),

    /*
    | Severity per event name (info, warning, high, critical). Events not
    | listed are "info". A caller may still pass a severity for one entry,
    | e.g. a denial that touches the Super Admin role is raised to critical.
    */
    'severity' => [
        'auth.login.failed' => 'warning',
        'auth.password.reset_failed' => 'warning',
        'security.login.lockout' => 'warning',
        'security.access.denied' => 'warning',
        'security.privilege.denied' => 'high',
        'system.job.failed' => 'warning',
        'roles.created' => 'warning',
        'roles.updated' => 'warning',
        'roles.deleted' => 'warning',
        'roles.assigned' => 'warning',
        'roles.revoked' => 'warning',
        'permissions.updated' => 'warning',
        'users.deleted' => 'warning',
        'audit.exported' => 'warning',
        'audit.retention.pruned' => 'warning',
    ],

    /*
    | Never written to an entry, whatever a model or caller passes in.
    | Matched case-insensitively against attribute and property keys.
    */
    'sensitive_attributes' => [
        'password', 'password_confirmation', 'current_password', 'new_password',
        'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        'token', 'profile_token', 'api_token', 'access_token', 'refresh_token',
        'secret', 'private_key', 'session', 'session_id', '_token',
    ],

    /*
    | Route parameters replaced by "{name}" in the logged path, so links that
    | grant access (company profile links, reset links) never end up in the log.
    */
    'redacted_route_parameters' => ['token', 'hash', 'signature', 'expires'],

    /*
    | Days to keep entries, by category (the entry's log_name). Categories not
    | listed use "default". Only applied by `php artisan audit:prune`, which is
    | scheduled only when AUDIT_RETENTION_SCHEDULED=true: review your legal and
    | operational requirements before enabling it. These are starting values,
    | not legal requirements.
    */
    'retention' => [
        'scheduled' => env('AUDIT_RETENTION_SCHEDULED', false),
        'default' => (int) env('AUDIT_RETENTION_GENERAL_DAYS', 180),
        'categories' => [
            'auth' => (int) env('AUDIT_RETENTION_SECURITY_DAYS', 90),
            'security' => (int) env('AUDIT_RETENTION_SECURITY_DAYS', 90),
            'users' => (int) env('AUDIT_RETENTION_ADMIN_DAYS', 365),
            'roles' => (int) env('AUDIT_RETENTION_ADMIN_DAYS', 365),
            'permissions' => (int) env('AUDIT_RETENTION_ADMIN_DAYS', 365),
            'settings' => (int) env('AUDIT_RETENTION_ADMIN_DAYS', 365),
            'audit' => (int) env('AUDIT_RETENTION_ADMIN_DAYS', 365),
        ],
        // Entries with these severities are kept for the longest configured period.
        'protected_severities' => ['high', 'critical'],
    ],

    /*
    | Security alerts. Each rule counts matching entries in a sliding window,
    | grouped by one column; once a group reaches the threshold, one open alert
    | is created for it and later matches only raise its count. Acknowledging
    | the alert closes it, so a new burst opens a new alert.
    */
    'alerts' => [
        'enabled' => env('AUDIT_ALERTS_ENABLED', true),

        // Optional: an address that receives an email when an alert opens.
        'mail_to' => env('AUDIT_ALERT_MAIL_TO'),

        'rules' => [
            'failed_logins_per_ip' => [
                'label' => 'Repeated failed logins from one IP address',
                'events' => ['auth.login.failed'],
                'group_by' => 'ip_address',
                'threshold' => 10,
                'minutes' => 15,
                'severity' => 'warning',
            ],
            'failed_logins_per_account' => [
                'label' => 'Repeated failed logins for one account',
                'events' => ['auth.login.failed'],
                'group_by' => 'identifier',
                'threshold' => 5,
                'minutes' => 15,
                'severity' => 'high',
            ],
            'repeated_access_denied' => [
                'label' => 'Repeated denied access to protected pages',
                'events' => ['security.access.denied'],
                'group_by' => 'actor',
                'threshold' => 10,
                'minutes' => 10,
                'severity' => 'warning',
            ],
            'privilege_events' => [
                'label' => 'High-severity privilege or security event',
                'severities' => ['high', 'critical'],
                'group_by' => 'event',
                'threshold' => 1,
                'minutes' => 60,
                'severity' => 'high',
            ],
        ],
    ],

];
