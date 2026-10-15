<?php

namespace App\Filament\Resources\CompanyProfileSubmissions;

use App\Exceptions\OutdatedSubmission;
use App\Filament\Resources\CompanyProfileSubmissions\Pages\ListCompanyProfileSubmissions;
use App\Filament\Resources\CompanyProfileSubmissions\Pages\ViewCompanyProfileSubmission;
use App\Filament\Resources\CompanyProfileSubmissions\Schemas\CompanyProfileSubmissionInfolist;
use App\Filament\Resources\CompanyProfileSubmissions\Tables\CompanyProfileSubmissionsTable;
use App\Models\CompanyProfileSubmission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

    /** Companies waiting for review. Each company has at most one pending submission. */
    public static function getNavigationBadge(): ?string
    {
        $count = CompanyProfileSubmission::query()
            ->where('status', CompanyProfileSubmission::STATUS_PENDING)
            ->distinct()
            ->count('company_id');

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Companies with changes waiting for review';
    }

    /**
     * Approves or rejects a submission and reports the result. A version that was
     * superseded or reviewed after the page was opened is refused and the
     * administrator is pointed to the latest version instead.
     *
     * @param  'approve'|'reject'  $decision
     */
    public static function review(CompanyProfileSubmission $record, string $decision, ?string $note): bool
    {
        try {
            $emailSent = $decision === 'approve' ? $record->approve($note) : $record->reject($note);
        } catch (OutdatedSubmission $exception) {
            $latest = $exception->latestPending();

            Notification::make()
                ->title('Submission changed')
                ->body($exception->getMessage())
                ->warning()
                ->persistent()
                ->actions($latest ? [
                    Action::make('reviewLatest')
                        ->label('Review latest version')
                        ->button()
                        ->url(static::getUrl('view', ['record' => $latest])),
                ] : [])
                ->send();

            return false;
        }

        $notification = Notification::make()
            ->title($decision === 'approve' ? 'Company profile updated' : 'Submission rejected')
            ->body($emailSent
                ? 'The company contact has been emailed.'
                : 'The email could not be sent. Check the mail settings before reviewing more submissions.');

        ($emailSent ? $notification->success() : $notification->warning())->send();

        return true;
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
