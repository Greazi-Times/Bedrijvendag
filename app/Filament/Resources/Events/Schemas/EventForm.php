<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                TextInput::make('max_stands')
                    ->label('Company stands')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),
                TextInput::make('partner_stand_count')
                    ->label('Partner stands')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->nullable(),
                Select::make('eventPartners')
                    ->label('Organising partners')
                    ->relationship('eventPartners', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->nullable(),
                TextInput::make('google_photos_album_url')
                    ->url()
                    ->default(null),
                RichEditor::make('description')
                    ->label('Description')
                    ->default(null)
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'bulletList',
                        'orderedList',
                        'h2',
                        'h3',
                        'blockquote',
                        'link',
                    ])
                    ->required(),
                FileUpload::make('header_image_path')
                    ->label('Header image')
                    ->image()
                    ->directory('event-headers')
                    ->disk('public')
                    ->imageEditor()
                    ->nullable(),
                FileUpload::make('map_path')
                    ->label('Map image')
                    ->image()
                    ->directory('event-maps')
                    ->disk('public')
                    ->imageEditor()
                    ->nullable(),
            ]);
    }
}
