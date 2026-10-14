<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Resources\AuditLogs\Schemas\AuditLogInfolist;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use App\Support\Permissions;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/** Read-only: there are no create or edit pages, and the model refuses updates and deletes. */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Audit log';

    protected static ?string $modelLabel = 'audit entry';

    protected static string|null|\UnitEnum $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return AuditLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }

    /** The audit list with these table filters applied, e.g. ['severity' => ['values' => ['high']]]. */
    public static function filteredUrl(array $filters): string
    {
        return static::getUrl('index', ['filters' => $filters]);
    }

    public static function categoryLabel(?string $category): string
    {
        return match ($category) {
            'auth' => 'Authentication',
            'security' => 'Security',
            'system' => 'System',
            'audit' => 'Audit log',
            'settings' => 'Site settings',
            null, '' => 'General',
            default => Permissions::moduleLabel($category),
        };
    }

    public static function severityColor(?string $severity): string
    {
        return match ($severity) {
            'critical', 'high' => 'danger',
            'warning' => 'warning',
            default => 'gray',
        };
    }

    public static function resultColor(?string $result): string
    {
        return match ($result) {
            'denied' => 'danger',
            'failure' => 'warning',
            default => 'success',
        };
    }

    public static function resourceTypeLabel(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        return str(class_basename(Relation::getMorphedModel($type) ?? $type))->headline()->toString();
    }

    /** A link to the affected record when it still exists and the viewer may open it. */
    public static function subjectUrl(AuditLog $log): ?string
    {
        $subject = $log->subject;

        if (! $subject || ! $resource = Filament::getModelResource($subject)) {
            return null;
        }

        return match (true) {
            $resource::hasPage('edit') && $resource::canEdit($subject) => $resource::getUrl('edit', ['record' => $subject]),
            $resource::hasPage('view') && $resource::canView($subject) => $resource::getUrl('view', ['record' => $subject]),
            default => null,
        };
    }
}
