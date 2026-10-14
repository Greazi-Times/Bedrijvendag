<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\SecurityAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Applies the rules in config('audit.alerts.rules') to each new audit entry.
 * A rule only queries the log when the entry matches it, so ordinary entries
 * cost nothing. One open alert exists per rule and group: repeats raise its
 * count instead of creating new alerts, which keeps alerting idempotent and
 * stops a burst of failed logins from flooding the overview or a mailbox.
 */
final class SecurityAlerts
{
    public static function evaluate(AuditLog $log): void
    {
        if (! config('audit.alerts.enabled')) {
            return;
        }

        foreach (config('audit.alerts.rules') as $rule => $definition) {
            if (! self::matches($log, $definition) || ($key = self::groupKey($log, $definition['group_by'])) === null) {
                continue;
            }

            $open = SecurityAlert::query()->where('rule', $rule)->where('group_key', $key)->whereNull('acknowledged_at')->first();

            if ($open) {
                $open->forceFill(['count' => $open->count + 1, 'last_seen_at' => now()])->save();

                continue;
            }

            $count = self::recentMatches($log, $definition, $key)->count();

            if ($count >= $definition['threshold']) {
                self::open($rule, $definition, $key, $count);
            }
        }
    }

    /** @param  array<string, mixed>  $definition */
    private static function matches(AuditLog $log, array $definition): bool
    {
        return in_array($log->event, $definition['events'] ?? [], true)
            || in_array($log->severity, $definition['severities'] ?? [], true);
    }

    private static function groupKey(AuditLog $log, string $groupBy): ?string
    {
        return match ($groupBy) {
            'ip_address' => $log->ip_address,
            'identifier' => $log->properties->get('identifier_hash'),
            'actor' => $log->causer_id ? "user:{$log->causer_id}" : ($log->ip_address ? "ip:{$log->ip_address}" : null),
            'event' => $log->event,
            default => null,
        };
    }

    /** @param  array<string, mixed>  $definition */
    private static function recentMatches(AuditLog $log, array $definition, string $key): Builder
    {
        $query = AuditLog::query()
            ->where('created_at', '>=', now()->subMinutes($definition['minutes']))
            ->where(fn (Builder $query) => $query
                ->whereIn('event', $definition['events'] ?? [])
                ->orWhereIn('severity', $definition['severities'] ?? []));

        return match ($definition['group_by']) {
            'ip_address' => $query->where('ip_address', $key),
            'identifier' => $query->where('properties->identifier_hash', $key),
            'actor' => $log->causer_id ? $query->where('causer_id', $log->causer_id) : $query->whereNull('causer_id')->where('ip_address', $log->ip_address),
            'event' => $query->where('event', $key),
        };
    }

    /** @param  array<string, mixed>  $definition */
    private static function open(string $rule, array $definition, string $key, int $count): void
    {
        $alert = new SecurityAlert;
        $alert->forceFill([
            'rule' => $rule,
            'group_key' => $key,
            'severity' => $definition['severity'],
            'count' => $count,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ])->save();

        if ($to = config('audit.alerts.mail_to')) {
            try {
                Mail::raw(
                    "Security alert: {$alert->ruleLabel()}\nSeverity: {$alert->severity}\nMatching events: {$count}\nGroup: ".self::displayKey($rule, $key)."\n\nReview it in the dashboard under Administration > Security overview.",
                    fn ($message) => $message->to($to)->subject("[{$alert->severity}] {$alert->ruleLabel()}"),
                );
            } catch (Throwable $e) {
                // An unreachable mail server must not break the request that triggered the alert.
                report($e);
            }
        }
    }

    /** The account hash means nothing to a reader; everything else is shown as is. */
    public static function displayKey(string $rule, string $key): string
    {
        return config("audit.alerts.rules.{$rule}.group_by") === 'identifier' ? 'one account (identifier hashed)' : $key;
    }
}
