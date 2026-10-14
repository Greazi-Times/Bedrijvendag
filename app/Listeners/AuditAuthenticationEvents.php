<?php

namespace App\Listeners;

use App\Support\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Laravel\Fortify\Fortify;

/**
 * Turns Laravel's authentication and queue events into audit entries. Both
 * login forms (Filament's and Fortify's) fire these events, so nothing is
 * logged twice. Passwords and reset tokens never reach these handlers' output.
 */
class AuditAuthenticationEvents
{
    public function login(Login $event): void
    {
        Audit::log('auth.login.success', "{$event->user->name} signed in", $event->user, [
            'guard' => $event->guard,
            'remember' => $event->remember,
        ], causer: $event->user);
    }

    /**
     * The attempted address is stored masked plus as a keyed hash, so repeated
     * attempts can be grouped without keeping the address. The attempt is
     * linked to an account only when the address matched one.
     */
    public function failed(Failed $event): void
    {
        $identifier = $event->credentials['email'] ?? $event->credentials[Fortify::username()] ?? null;

        Audit::log('auth.login.failed', 'Failed sign-in attempt', $event->user instanceof Model ? $event->user : null, [
            'guard' => $event->guard,
            // Known account: wrong password, or the account may not use the dashboard.
            'reason' => $event->user ? 'credentials_rejected' : 'unknown_account',
            'identifier' => Audit::maskIdentifier($identifier),
            'identifier_hash' => Audit::hashIdentifier($identifier),
        ], result: 'failure');
    }

    public function lockout(Lockout $event): void
    {
        $identifier = $event->request->input('email') ?? $event->request->input('data.email') ?? $event->request->input(Fortify::username());

        Audit::log('security.login.lockout', 'Sign-in temporarily blocked after too many attempts', properties: [
            'identifier' => Audit::maskIdentifier(is_string($identifier) ? $identifier : null),
            'identifier_hash' => Audit::hashIdentifier(is_string($identifier) ? $identifier : null),
        ], result: 'denied');
    }

    public function logout(Logout $event): void
    {
        if ($event->user instanceof Model) {
            Audit::log('auth.logout', "{$event->user->name} signed out", $event->user, ['guard' => $event->guard], causer: $event->user);
        }
    }

    public function otherDeviceLogout(OtherDeviceLogout $event): void
    {
        Audit::log('auth.session.revoked', "{$event->user->name} signed out other browser sessions", $event->user, ['guard' => $event->guard], causer: $event->user);
    }

    public function passwordResetLinkSent(PasswordResetLinkSent $event): void
    {
        Audit::log('auth.password.reset_requested', "Password reset link sent to {$event->user->name}", $event->user);
    }

    public function passwordReset(PasswordReset $event): void
    {
        Audit::log('auth.password.reset', "{$event->user->name} reset their password", $event->user, causer: $event->user);
    }

    public function verified(Verified $event): void
    {
        Audit::log('auth.email.verified', "{$event->user->name} verified their email address", $event->user, causer: $event->user);
    }

    /** Only the job class and exception class: messages and payloads can hold personal data. */
    public function jobFailed(JobFailed $event): void
    {
        Audit::log('system.job.failed', "Job {$event->job->resolveName()} failed", properties: [
            'job' => $event->job->resolveName(),
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'exception' => $event->exception::class,
        ], result: 'failure');
    }

    /** @return array<class-string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'login',
            Failed::class => 'failed',
            Lockout::class => 'lockout',
            Logout::class => 'logout',
            OtherDeviceLogout::class => 'otherDeviceLogout',
            PasswordResetLinkSent::class => 'passwordResetLinkSent',
            PasswordReset::class => 'passwordReset',
            Verified::class => 'verified',
            JobFailed::class => 'jobFailed',
        ];
    }
}
