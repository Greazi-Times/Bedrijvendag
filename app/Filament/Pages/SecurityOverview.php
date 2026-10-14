<?php

namespace App\Filament\Pages;

use App\Filament\Security\Widgets\FailedLoginsChart;
use App\Filament\Security\Widgets\OpenSecurityAlerts;
use App\Filament\Security\Widgets\RecentSecurityEvents;
use App\Filament\Security\Widgets\SecurityStats;
use App\Filament\Security\Widgets\TopSourceIps;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** A second dashboard built from audit entries. Its widgets live outside app/Filament/Widgets so the main dashboard doesn't pick them up. */
class SecurityOverview extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'security';

    protected static ?string $title = 'Security overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('security.view') ?? false;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    DatePicker::make('from')->placeholder('7 days ago')->maxDate(now()),
                    DatePicker::make('until')->placeholder('Today')->maxDate(now()),
                ]),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            OpenSecurityAlerts::class,
            SecurityStats::class,
            FailedLoginsChart::class,
            TopSourceIps::class,
            RecentSecurityEvents::make(['scope' => 'severe']),
            RecentSecurityEvents::make(['scope' => 'denied']),
            RecentSecurityEvents::make(['scope' => 'privileges']),
            RecentSecurityEvents::make(['scope' => 'lockouts']),
        ];
    }

    /** @return list<class-string> */
    public static function widgetComponents(): array
    {
        return [OpenSecurityAlerts::class, SecurityStats::class, FailedLoginsChart::class, TopSourceIps::class, RecentSecurityEvents::class];
    }
}
