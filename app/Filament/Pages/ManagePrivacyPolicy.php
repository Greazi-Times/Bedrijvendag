<?php

namespace App\Filament\Pages;

use App\Settings\PrivacyPolicySettings;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManagePrivacyPolicy extends SettingsPage
{
    protected static string|null|\UnitEnum $navigationGroup = 'Settings';

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $navigationLabel = 'Privacy policy';

    protected static ?string $title = 'Privacy policy';

    protected static string $settings = PrivacyPolicySettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            RichEditor::make('content')
                ->label('Privacy policy')
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
