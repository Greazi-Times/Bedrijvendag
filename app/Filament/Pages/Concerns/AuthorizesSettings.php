<?php

namespace App\Filament\Pages\Concerns;

use App\Models\AuditLog;

trait AuthorizesSettings
{
    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function canEdit(): bool
    {
        return static::canAccess();
    }

    // Runs inside the save transaction, so a failed save leaves no entry.
    protected function beforeSave(): void
    {
        AuditLog::record('settings.updated', label: static::getNavigationLabel());
    }
}
