<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->directory('company-logos')
                    ->disk('public')
                    ->imageEditor()
                    ->nullable(),
                TextInput::make('website_url')
                    ->url()
                    ->default(null),
                RichEditor::make('description')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'bulletList',
                        'orderedList',
                        'link',
                        'blockquote',
                        'h2',
                        'h3',
                    ])
                    ->required()
                    ->columnSpanFull(),
                CheckboxList::make('educations')
                    ->label('Educations')
                    ->relationship('educations', 'name')
                    ->columns(2)
                    ->searchable()
                    ->bulkToggleable(),
                Select::make('sectors')
                    ->label('Sectors')
                    ->multiple()
                    ->relationship('sectors', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(50),
            ]);
    }
}
