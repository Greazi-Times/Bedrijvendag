<?php

namespace App\Filament\Resources\Partners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('url')
                ->label('Website URL')
                ->url()
                ->required(),

            FileUpload::make('image')
                ->image()
                ->disk('public')
                ->directory('partners')
                ->disk('public')
                ->required(),
        ]);
    }
}
