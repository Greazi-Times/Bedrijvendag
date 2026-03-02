<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Education\EducationResource;
use App\Filament\Resources\Sectors\SectorResource;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class EducationsRelationManager extends RelationManager
{
    protected static string $relationship = 'educations';

    protected static ?string $relatedResource = EducationResource::class;
}
