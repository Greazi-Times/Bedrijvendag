<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Sectors\SectorResource;
use Filament\Resources\RelationManagers\RelationManager;

class SectorsRelationManager extends RelationManager
{
    // Sector management now happens in the Company form
    protected static string $relationship = 'sectors';

    protected static ?string $relatedResource = SectorResource::class;
}
