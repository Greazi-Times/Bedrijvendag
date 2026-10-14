<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Audit log';

    protected static string|null|\UnitEnum $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 4;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('created_at')->label('When')->dateTime(),
                TextEntry::make('user.name')->label('By')->placeholder('System'),
                TextEntry::make('action')->badge(),
                TextEntry::make('subject_label')->label('Subject')->placeholder('-'),
                TextEntry::make('changes')
                    ->columnSpanFull()
                    ->placeholder('No details')
                    ->state(fn (AuditLog $record): ?string => $record->changes ? json_encode($record->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null)
                    ->fontFamily('mono')
                    ->extraAttributes(['class' => 'whitespace-pre-wrap']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user:id,name'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('System')
                    ->searchable(),
                TextColumn::make('action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('subject_label')
                    ->label('Subject')
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options(fn (): array => AuditLog::query()->distinct()->orderBy('action')->pluck('action', 'action')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No administrative changes recorded yet');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
