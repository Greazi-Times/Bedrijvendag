<?php

namespace App\Support;

use App\Exceptions\PrivilegeDenied;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The one way the application writes audit entries. Model changes reach the
 * log through spatie's LogsActivity (see Models\Concerns\Audited); everything
 * else calls Audit::log(). Both pass through enrich(), which adds the
 * severity, actor and request metadata and strips sensitive values.
 *
 * Event names are "category.action[.detail]", e.g. auth.login.failed; the
 * category is stored as the activity's log_name.
 */
final class Audit
{
    public const SEVERITIES = ['info', 'warning', 'high', 'critical'];

    public const RESULTS = ['success', 'failure', 'denied'];

    /** @param  array<string, mixed>  $properties */
    public static function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?string $severity = null,
        string $result = 'success',
        ?Model $causer = null,
        ?string $label = null,
    ): ?AuditLog {
        $logger = activity(Str::before($event, '.'))->event($event)->withProperties($properties);

        if ($subject) {
            $logger->performedOn($subject);
        }

        if ($causer) {
            $logger->causedBy($causer);
        }

        return $logger
            ->tap(function (AuditLog $log) use ($severity, $result, $label): void {
                $log->severity = $severity;
                $log->result = $result;
                $log->subject_label = $label;
            })
            ->log($description);
    }

    /**
     * Called for every HTTP error response. Only 403s and, for signed-in users,
     * CSRF failures (419) are logged: anonymous 419s are mostly expired forms and
     * would let anyone fill the log. A failing audit write never replaces the
     * error response.
     */
    public static function httpDenied(HttpExceptionInterface $e): void
    {
        $status = $e->getStatusCode();

        if ($status !== 403 && ! ($status === 419 && auth()->check())) {
            return;
        }

        try {
            $previous = $e->getPrevious();

            match (true) {
                $status === 419 => self::log('security.csrf.failed', 'CSRF token missing or expired (usually an expired session)', severity: 'info', result: 'denied'),
                $previous instanceof PrivilegeDenied => self::log('security.privilege.denied', $previous->getMessage(), severity: $previous->severity, result: 'denied'),
                default => self::log('security.access.denied', $e->getMessage() ?: 'Access denied', result: 'denied'),
            };
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** Fill in everything an entry needs before it is stored. */
    public static function enrich(AuditLog $log): void
    {
        // Model events arrive as "updated" in log "events"; store "events.updated".
        if ($log->event && ! str_contains($log->event, '.')) {
            $log->event = "{$log->log_name}.{$log->event}";
        }

        $log->severity = in_array($log->severity, self::SEVERITIES, true)
            ? $log->severity
            : (config('audit.severity')[$log->event] ?? 'info');
        $log->result ??= 'success';
        $log->properties = collect(self::redact($log->properties?->all() ?? []));
        $log->subject_label = Str::limit((string) ($log->subject_label ?? self::labelFor($log->subject)), 250, '') ?: null;

        $causer = $log->causer;
        $log->actor_type = match (true) {
            $causer !== null => 'user',
            self::handlingRequest() => 'guest',
            default => 'system',
        };
        $log->actor_label = $causer?->getAttribute('name');
        $log->request_id = Context::get('request_id');

        if (self::handlingRequest()) {
            $request = request();
            $route = self::route($request);

            $log->ip_address = $request->ip();
            $log->user_agent = Str::limit((string) $request->userAgent(), 500, '') ?: null;
            $log->http_method = $request->method();
            $log->route_name = $route?->getName();
            $log->path = Str::limit(self::path($request, $route), 250, '');
        }
    }

    /**
     * Remove sensitive keys at any depth. Keys are matched without case.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function redact(array $data): array
    {
        $sensitive = array_map('strtolower', config('audit.sensitive_attributes'));

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $sensitive, true)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = self::redact($value);
            }
        }

        return $data;
    }

    /** "jane.doe@example.com" becomes "ja***@example.com"; enough to recognise, not to reuse. */
    public static function maskIdentifier(?string $identifier): ?string
    {
        if (blank($identifier)) {
            return null;
        }

        $identifier = Str::lower(trim($identifier));
        [$local, $domain] = str_contains($identifier, '@') ? explode('@', $identifier, 2) : [$identifier, null];
        $masked = Str::limit($local, 2, '').'***';

        return Str::limit($domain ? "{$masked}@{$domain}" : $masked, 120, '');
    }

    /** Keyed hash, so attempts against one account can be grouped without storing the address. */
    public static function hashIdentifier(?string $identifier): ?string
    {
        return blank($identifier) ? null : hash_hmac('sha256', Str::lower(trim($identifier)), (string) config('app.key'));
    }

    public static function labelFor(?Model $model): ?string
    {
        if (! $model) {
            return null;
        }

        foreach (['name', 'label', 'title', 'company_name', 'proposed_name', 'stand_number'] as $attribute) {
            if (filled($value = $model->getAttribute($attribute))) {
                return (string) $value;
            }
        }

        return null;
    }

    /** Fortify's reset form, or Filament's reset page. */
    public static function isPasswordResetRequest(): bool
    {
        $name = self::handlingRequest() ? (string) self::route(request())?->getName() : '';

        return $name === 'password.update' || str_contains($name, 'password-reset');
    }

    /** Queue workers, the scheduler and artisan have no request worth recording. */
    private static function handlingRequest(): bool
    {
        return ! app()->runningInConsole() || app()->runningUnitTests();
    }

    /** For Livewire updates, the page the component lives on rather than /livewire/update. */
    private static function route(Request $request): ?Route
    {
        try {
            if (Livewire::isLivewireRequest()) {
                return app('router')->getRoutes()->match(Request::create(Livewire::originalPath()));
            }
        } catch (Throwable) {
            return $request->route();
        }

        return $request->route();
    }

    /** The path without query string, with access-granting route parameters replaced by their name. */
    private static function path(Request $request, ?Route $route): string
    {
        if (! $route) {
            return '/'.ltrim($request->path(), '/');
        }

        $redacted = config('audit.redacted_route_parameters');
        $path = $route->uri();

        foreach ($route->hasParameters() ? $route->originalParameters() : [] as $name => $value) {
            if (! in_array($name, $redacted, true)) {
                $path = str_replace(["{{$name}}", "{{$name}?}"], (string) $value, $path);
            }
        }

        return '/'.ltrim($path, '/');
    }
}
