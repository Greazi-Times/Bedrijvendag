<?php

namespace App\Filament\Pages\Concerns;

use App\Support\Audit;

/** Pages using this declare `protected static string $permission`. */
trait AuthorizesSettings
{
    public static function canAccess(): bool
    {
        return auth()->user()?->can(static::$permission) ?? false;
    }

    public function canEdit(): bool
    {
        return static::canAccess();
    }

    // Runs inside the save transaction, so a failed save leaves no entry.
    protected function beforeSave(): void
    {
        Audit::log('settings.updated', static::getNavigationLabel().' updated', label: static::getNavigationLabel());
    }
}
