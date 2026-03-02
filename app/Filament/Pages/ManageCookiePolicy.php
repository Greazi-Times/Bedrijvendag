<?php

namespace App\Filament\Pages;

use App\Settings\CookiePolicySettings;
use Filafly\Icons\Phosphor\Enums\Phosphor;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;

class ManageCookiePolicy extends SettingsPage
{
    protected static string|null|\UnitEnum $navigationGroup = 'Settings';

    protected static string|null|\BackedEnum $navigationIcon = Phosphor::Cookie;

    protected static ?string $navigationLabel = 'Cookie policy';

    protected static ?string $title = 'Cookie policy';

    protected static string $settings = CookiePolicySettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            RichEditor::make('content')
                ->label('Cookie Policy')
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
