<?php

namespace App\Filament\Resources\CompanyAccessRequests;

use App\Filament\Resources\CompanyAccessRequests\Pages\ListCompanyAccessRequests;
use App\Filament\Resources\CompanyAccessRequests\Pages\ViewCompanyAccessRequest;
use App\Filament\Resources\CompanyAccessRequests\Schemas\CompanyAccessRequestInfolist;
use App\Filament\Resources\CompanyAccessRequests\Tables\CompanyAccessRequestsTable;
use App\Models\CompanyAccessRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyAccessRequestResource extends Resource
{
    protected static ?string $model = CompanyAccessRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Access requests';

    protected static string|null|\UnitEnum $navigationGroup = 'Company';

    protected static ?int $navigationSort = 14;

    public static function getNavigationBadge(): ?string
    {
        $count = CompanyAccessRequest::query()
            ->where('status', CompanyAccessRequest::STATUS_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompanyAccessRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyAccessRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyAccessRequests::route('/'),
            'view' => ViewCompanyAccessRequest::route('/{record}'),
        ];
    }
}
