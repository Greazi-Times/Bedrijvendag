<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Lockout;

/** Filament's login, plus the Lockout event Fortify fires, so throttled sign-ins are audited. */
class Login extends BaseLogin
{
    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        // Livewire requests carry form state in the snapshot, so pass the address explicitly.
        event(new Lockout(request()->duplicate(request: ['email' => $this->data['email'] ?? null])));

        return parent::getRateLimitedNotification($exception);
    }
}
