<?php

namespace App\Filament\Resources\EventMapPoints;

use App\Filament\Resources\EventMapPoints\Pages\ManageMapPoints;
use App\Models\EventMapPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class EventMapPointResource extends Resource
{
    protected static ?string $model = EventMapPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Map Points';

    protected static string|null|\UnitEnum $navigationGroup = 'Edition';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMapPoints::route('/'),
        ];
    }
}
