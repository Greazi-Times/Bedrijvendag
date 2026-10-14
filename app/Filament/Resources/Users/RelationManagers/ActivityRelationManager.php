<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** What this user did: entries where they are the actor. Read-only. */
class ActivityRelationManager extends RelationManager
{
    protected static string $relationship = 'actions';

    protected static ?string $title = 'Activity by this user';

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
        return AuditLogsTable::configure($table, withActorFilter: false);
    }
}
