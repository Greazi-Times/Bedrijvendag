<?php

namespace App\Filament\Security\Widgets;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Security\Concerns\InteractsWithSecurityRange;
use App\Models\AuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Addresses behind the most failed or refused requests. Frequent is not the same as hostile: shared networks show up here too. */
class TopSourceIps extends TableWidget
{
    use InteractsWithSecurityRange;

    private const EVENTS = ['auth.login.failed', 'security.login.lockout', 'security.access.denied', 'security.privilege.denied', 'security.csrf.failed'];

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        [$from, $until] = $this->range();

        return $table
            ->heading('Most frequent source IPs of failed or denied requests')
            ->query(fn (): Builder => AuditLog::query()
                ->selectRaw('min(id) as id, ip_address, count(*) as total, max(created_at) as last_seen')
                ->whereIn('event', self::EVENTS)
                ->whereNotNull('ip_address')
                ->whereBetween('created_at', [$from, $until])
                ->groupBy('ip_address')
                ->orderByDesc('total')
                ->limit(10))
            ->paginated(false)
            ->recordUrl(fn (AuditLog $record): string => AuditLogResource::filteredUrl([
                ...$this->rangeFilter(),
                'ip_address' => ['ip_address' => $record->ip_address],
            ]))
            ->columns([
                TextColumn::make('ip_address')->label('IP address')->copyable(),
                TextColumn::make('total')->label('Events')->numeric(),
                TextColumn::make('last_seen')->label('Last seen')->dateTime('m-d H:i', config('audit.display_timezone')),
            ])
            ->emptyStateHeading('No failed or denied requests in this period');
    }
}
