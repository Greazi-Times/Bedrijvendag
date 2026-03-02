<?php

namespace App\Filament\Resources\BorrelEnrollments;

use App\Filament\Resources\BorrelEnrollments\Pages\CreateBorrelEnrollment;
use App\Filament\Resources\BorrelEnrollments\Pages\EditBorrelEnrollment;
use App\Filament\Resources\BorrelEnrollments\Pages\ListBorrelEnrollments;
use App\Filament\Resources\BorrelEnrollments\Pages\ViewBorrelEnrollment;
use App\Filament\Resources\BorrelEnrollments\Schemas\BorrelEnrollmentForm;
use App\Filament\Resources\BorrelEnrollments\Schemas\BorrelEnrollmentInfolist;
use App\Filament\Resources\BorrelEnrollments\Tables\BorrelEnrollmentsTable;
use App\Models\BorrelEnrollment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BorrelEnrollmentResource extends Resource
{
    protected static ?string $model = BorrelEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|null|\UnitEnum $navigationGroup = 'Edition';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return BorrelEnrollmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BorrelEnrollmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BorrelEnrollmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBorrelEnrollments::route('/'),
            'create' => CreateBorrelEnrollment::route('/create'),
            'view' => ViewBorrelEnrollment::route('/{record}'),
            'edit' => EditBorrelEnrollment::route('/{record}/edit'),
        ];
    }
}
