<?php

namespace App\Filament\Resources\CompanyProfileSubmissions;

use App\Filament\Resources\CompanyProfileSubmissions\Pages\ListCompanyProfileSubmissions;
use App\Filament\Resources\CompanyProfileSubmissions\Pages\ViewCompanyProfileSubmission;
use App\Filament\Resources\CompanyProfileSubmissions\Schemas\CompanyProfileSubmissionInfolist;
use App\Filament\Resources\CompanyProfileSubmissions\Tables\CompanyProfileSubmissionsTable;
use App\Models\CompanyProfileSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyProfileSubmissionResource extends Resource
{
    protected static ?string $model = CompanyProfileSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Profile submissions';

    protected static string|null|\UnitEnum $navigationGroup = 'Company';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = CompanyProfileSubmission::query()
            ->where('status', CompanyProfileSubmission::STATUS_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompanyProfileSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyProfileSubmissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyProfileSubmissions::route('/'),
            'view' => ViewCompanyProfileSubmission::route('/{record}'),
        ];
    }
}
