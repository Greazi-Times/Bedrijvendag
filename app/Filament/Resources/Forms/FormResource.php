<?php

namespace App\Filament\Resources\Forms;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Forms\Schemas\FormBuilderSchema;
use App\Models\Form;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|null|\UnitEnum $navigationGroup = 'Edition';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return FormBuilderSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('kind')->badge()->formatStateUsing(fn (string $state): string => $state === 'enrollment' ? 'Enrollment' : 'Information'),
                TextColumn::make('scope')->label('Used by')->badge()
                    ->state(fn (Form $record): string => $record->isGlobal() ? 'Every edition without its own form' : ($record->event_id ? 'Edition: '.$record->event?->name : 'Reusable template'))
                    ->color(fn (Form $record): string => $record->isGlobal() ? 'success' : ($record->event_id ? 'info' : 'gray')),
                TextColumn::make('latestVersion.version')->label('Published')->prefix('v')->placeholder('Never'),
                IconColumn::make('is_template')->label('Template')->boolean(),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordUrl(fn (Form $record): string => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }
}
