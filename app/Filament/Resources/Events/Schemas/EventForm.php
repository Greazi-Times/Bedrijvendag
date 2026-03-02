<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
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
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),
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
                Select::make('partners')
                    ->relationship('partners', 'name')
                    ->multiple()
                    ->preload(),
                TextInput::make('google_photos_album_url')
                    ->url()
                    ->default(null),
            ]);
    }
}
