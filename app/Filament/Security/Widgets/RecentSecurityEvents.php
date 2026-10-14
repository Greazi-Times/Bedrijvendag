<?php

namespace App\Filament\Security\Widgets;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Security\Concerns\InteractsWithSecurityRange;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** The ten latest entries of one kind; used several times on the overview with a different $scope. */
class RecentSecurityEvents extends TableWidget
{
    use InteractsWithSecurityRange;

    public const SCOPES = [
        'severe' => ['heading' => 'Recent high and critical events', 'filter' => ['severity' => ['values' => ['high', 'critical']]]],
        'denied' => ['heading' => 'Recent authorization failures', 'filter' => ['event' => ['values' => ['security.access.denied', 'security.privilege.denied']]]],
        'lockouts' => ['heading' => 'Recent sign-in lockouts', 'filter' => ['event' => ['values' => ['security.login.lockout']]]],
        'privileges' => ['heading' => 'Recent privilege changes', 'filter' => ['event' => ['values' => ['roles.assigned', 'roles.revoked', 'roles.created', 'roles.updated', 'roles.deleted', 'permissions.updated']]]],
    ];

    public string $scope = 'severe';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        [$from, $until] = $this->range();
        $definition = self::SCOPES[$this->scope];
        [$column, $values] = [array_key_first($definition['filter']), reset($definition['filter'])['values']];

        return $table
            ->heading($definition['heading'])
            ->query(fn (): Builder => AuditLog::query()
                ->whereIn($column, $values)
                ->whereBetween('created_at', [$from, $until])
                ->latest('id')
                ->limit(10))
            ->paginated(false)
            ->recordUrl(fn (AuditLog $record): string => AuditLogResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('created_at')->label('Time')->dateTime('m-d H:i', config('audit.display_timezone')),
                TextColumn::make('event')->description(fn (AuditLog $record): string => str($record->description)->limit(60)->toString()),
                TextColumn::make('actor_label')->label('Actor')->placeholder(fn (AuditLog $record): string => ucfirst($record->actor_type)),
                TextColumn::make('severity')->badge()->color(fn (string $state): string => AuditLogResource::severityColor($state)),
            ])
            ->headerActions([
                Action::make('all')
                    ->label('View all')
                    ->link()
                    ->url(AuditLogResource::filteredUrl([...$this->rangeFilter(), ...$definition['filter']])),
            ])
            ->emptyStateHeading('Nothing in this period');
    }
}
