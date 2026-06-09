<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Education\EducationResource;
use Filament\Resources\RelationManagers\RelationManager;

class EducationsRelationManager extends RelationManager
{
    protected static string $relationship = 'educations';

    protected static ?string $relatedResource = EducationResource::class;
}
