<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                ImageEntry::make('logo_path')
                    ->label('Logo')
                    ->height(120)
                    ->placeholder('No logo has been uploaded'),
                TextEntry::make('website_url')
                    ->placeholder('No website URL has been provided'),
                TextEntry::make('description')
                    ->placeholder('No description has been provided')
                    ->columnSpanFull(),
                TextEntry::make('sectors.name')
                    ->label('Sectors')
                    ->bulleted()
                    ->placeholder('No sectors have been associated'),
                TextEntry::make('educations.name')
                    ->label('Educations')
                    ->bulleted()
                    ->placeholder('No educations have been associated'),
            ]);
    }
}
