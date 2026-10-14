<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

/**
 * Used by the audit log and by the activity tabs on a user. Every column is
 * stored on the entry itself, so listing never queries users or subjects.
 */
class AuditLogsTable
{
    public static function configure(Table $table, bool $withActorFilter = true): Table
    {
        $timezone = config('audit.display_timezone');

        return $table
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([25, 50, 100])
            ->recordUrl(fn (AuditLog $record): string => AuditLogResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('Y-m-d H:i:s', $timezone)
                    ->sortable(),
                TextColumn::make('actor_label')
                    ->label('Actor')
                    ->placeholder(fn (AuditLog $record): string => ucfirst($record->actor_type))
                    ->description(fn (AuditLog $record): ?string => $record->actor_label ? ucfirst($record->actor_type) : null)
                    ->searchable(),
                TextColumn::make('event')
                    ->description(fn (AuditLog $record): string => str($record->description)->limit(80)->toString())
                    ->searchable(['event', 'description', 'request_id'])
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Category')
                    ->formatStateUsing(fn (?string $state): string => AuditLogResource::categoryLabel($state))
                    ->toggleable(),
                TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state): string => AuditLogResource::severityColor($state))
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP address')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subject_label')
                    ->label('Resource')
                    ->state(fn (AuditLog $record): ?string => $record->subject_type
                        ? AuditLogResource::resourceTypeLabel($record->subject_type).' #'.$record->subject_id
                        : null)
                    ->description(fn (AuditLog $record): ?string => $record->subject_label)
                    ->placeholder('-')
                    ->searchable(['subject_label']),
                TextColumn::make('result')
                    ->badge()
                    ->color(fn (string $state): string => AuditLogResource::resultColor($state)),
            ])
            ->filters([
                ...($withActorFilter ? [self::actorFilter()] : []),
                SelectFilter::make('log_name')
                    ->label('Category')
                    ->multiple()
                    ->options(fn (): array => self::distinct('log_name')->mapWithKeys(fn (string $category): array => [$category => AuditLogResource::categoryLabel($category)])->all()),
                SelectFilter::make('event')
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => self::distinct('event')->mapWithKeys(fn (string $event): array => [$event => $event])->all()),
                SelectFilter::make('severity')
                    ->multiple()
                    ->options(array_combine(Audit::SEVERITIES, array_map('ucfirst', Audit::SEVERITIES))),
                SelectFilter::make('result')
                    ->options(array_combine(Audit::RESULTS, array_map('ucfirst', Audit::RESULTS))),
                SelectFilter::make('subject_type')
                    ->label('Resource type')
                    ->options(fn (): array => self::distinct('subject_type')->mapWithKeys(fn (string $type): array => [$type => AuditLogResource::resourceTypeLabel($type)])->all()),
                Filter::make('ip_address')
                    ->schema([TextInput::make('ip_address')->label('IP address')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['ip_address'] ?? null, fn (Builder $query, string $ip) => $query->where('ip_address', trim($ip))))
                    ->indicateUsing(fn (array $data): ?string => ($data['ip_address'] ?? null) ? "IP {$data['ip_address']}" : null),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '>=', Date::parse($date, $timezone)->startOfDay()->utc()))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '<=', Date::parse($date, $timezone)->endOfDay()->utc())))
                    ->indicateUsing(fn (array $data): ?string => ($data['from'] ?? null) || ($data['until'] ?? null)
                        ? 'Date '.($data['from'] ?? '…').' – '.($data['until'] ?? '…')
                        : null),
            ])
            ->filtersFormColumns(2)
            ->emptyStateHeading('No audit entries match')
            ->emptyStateDescription('Try a wider date range or fewer filters.');
    }

    private static function actorFilter(): SelectFilter
    {
        return SelectFilter::make('actor')
            ->label('User')
            ->searchable()
            ->getSearchResultsUsing(fn (string $search): array => User::query()
                ->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->limit(50)
                ->pluck('name', 'id')
                ->all())
            ->getOptionLabelUsing(fn ($value): ?string => User::query()->whereKey($value)->value('name'))
            ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, $id) => $query
                ->where('causer_type', (new User)->getMorphClass())
                ->where('causer_id', $id)));
    }

    /** @return Collection<int, string> */
    private static function distinct(string $column): Collection
    {
        return AuditLog::query()->whereNotNull($column)->distinct()->orderBy($column)->pluck($column);
    }
}
