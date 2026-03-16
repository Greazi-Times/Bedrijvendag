<?php

namespace App\Filament\Resources\Education\Schemas;

use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Schema;

class EducationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('website_url')
                    ->placeholder('-'),
                ColorEntry::make('color')
                    ->label('Kleur')
                    ->placeholder('-'),
            ]);
    }
}
