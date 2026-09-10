<?php

namespace App\Filament\Resources\CompanyInterestRequests;

use App\Filament\Resources\CompanyInterestRequests\Pages\ListCompanyInterestRequests;
use App\Filament\Resources\CompanyInterestRequests\Pages\ViewCompanyInterestRequest;
use App\Filament\Resources\CompanyInterestRequests\Schemas\CompanyInterestRequestInfolist;
use App\Filament\Resources\CompanyInterestRequests\Tables\CompanyInterestRequestsTable;
use App\Models\CompanyInterestRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyInterestRequestResource extends Resource
{
    protected static ?string $model = CompanyInterestRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Edition interest';

    protected static string|null|\UnitEnum $navigationGroup = 'Company';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CompanyInterestRequest::query()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompanyInterestRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyInterestRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyInterestRequests::route('/'),
            'view' => ViewCompanyInterestRequest::route('/{record}'),
        ];
    }
}
