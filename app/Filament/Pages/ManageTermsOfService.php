<?php

namespace App\Filament\Pages;

use App\Settings\TermsOfServiceSettings;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageTermsOfService extends SettingsPage
{
    protected static string|null|\UnitEnum $navigationGroup = 'Settings';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static ?string $navigationLabel = 'Terms of service';
    protected static ?string $title = 'Terms of service';

    protected static string $settings = TermsOfServiceSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            RichEditor::make('content')
                ->label('Terms Of Service')
                ->columnSpanFull()
                ->toolbarButtons([
                    ['bold',
                        'italic',
                        'underline',
                        'strike',
                        'bulletList',
                        'orderedList',
                        'h1',
                        'h2',
                        'h3',
                        'blockquote',
                        'link',
                        'table', ],
                    ['undo', 'redo'],
                ])
                ->extraInputAttributes([
                    // Bigger editor, but still fits on most screens.
                    'style' => 'min-height: 40vh; max-height: 50vh; overflow-y: auto;',
                ])
                ->required(),
        ]);
    }
}
