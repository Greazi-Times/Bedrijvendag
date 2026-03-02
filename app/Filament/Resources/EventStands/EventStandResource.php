<?php

namespace App\Filament\Resources\EventStands;

use App\Filament\Resources\EventStands\Pages\ManageStands;
use App\Models\CompanyEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class EventStandResource extends Resource
{
    protected static ?string $model = CompanyEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Stands';

    protected static string|null|\UnitEnum $navigationGroup = 'Edition';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStands::route('/'),
        ];
    }
}
