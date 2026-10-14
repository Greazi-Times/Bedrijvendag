<?php

namespace App\Filament\Security\Widgets;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Security\Concerns\InteractsWithSecurityRange;
use App\Models\SecurityAlert;
use App\Support\Audit;
use App\Support\SecurityAlerts;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Alerts raised by the rules in config/audit.php. An alert points at activity worth reviewing, not at a confirmed attack. */
class OpenSecurityAlerts extends TableWidget
{
    use InteractsWithSecurityRange;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Open alerts')
            ->description('Patterns that may need a look. Acknowledge an alert once it has been reviewed; new activity then opens a new one.')
            ->query(fn (): Builder => SecurityAlert::query()->whereNull('acknowledged_at')->latest('last_seen_at'))
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 25])
            ->columns([
                TextColumn::make('rule')->label('Alert')->formatStateUsing(fn (SecurityAlert $record): string => $record->ruleLabel()),
                TextColumn::make('group_key')->label('Source')->formatStateUsing(fn (SecurityAlert $record): string => SecurityAlerts::displayKey($record->rule, $record->group_key)),
                TextColumn::make('severity')->badge()->color(fn (string $state): string => AuditLogResource::severityColor($state)),
                TextColumn::make('count')->label('Events')->numeric(),
                TextColumn::make('first_seen_at')->label('First seen')->dateTime('Y-m-d H:i', config('audit.display_timezone')),
                TextColumn::make('last_seen_at')->label('Last seen')->dateTime('Y-m-d H:i', config('audit.display_timezone')),
            ])
            ->recordActions([
                Action::make('acknowledge')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()?->can('security.manage-alerts') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('security.manage-alerts') ?? false)
                    ->action(function (SecurityAlert $record): void {
                        $record->forceFill(['acknowledged_at' => now(), 'acknowledged_by' => auth()->id()])->save();

                        Audit::log('security.alert.acknowledged', "Alert \"{$record->ruleLabel()}\" acknowledged", properties: [
                            'alert_id' => $record->id,
                            'rule' => $record->rule,
                            'count' => $record->count,
                        ]);
                    }),
            ])
            ->emptyStateHeading('No open alerts')
            ->emptyStateIcon('heroicon-o-shield-check');
    }
}
