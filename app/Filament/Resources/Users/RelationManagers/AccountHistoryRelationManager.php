<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * What happened to this account: entries where it is the affected resource,
 * such as profile and role changes made by others, and failed sign-ins that
 * named this account's email address (only then is an attempt linked to it).
 */
class AccountHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Changes to this account';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('audit.view') ?? false;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }
}
