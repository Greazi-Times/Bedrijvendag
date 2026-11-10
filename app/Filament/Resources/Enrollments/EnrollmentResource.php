<?php

namespace App\Filament\Resources\Enrollments;

use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Filament\Resources\Enrollments\Pages\ViewEnrollment;
use App\Models\Enrollment;
use App\Models\Event;
use App\Support\Privacy\EmailSearch;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static string|null|\UnitEnum $navigationGroup = 'Company';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $pending = Enrollment::query()->where('status', Enrollment::STATUS_PENDING)->count();

        return $pending ? (string) $pending : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['event', 'formVersion']);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Enrollment')->columns(3)->schema([
                TextEntry::make('company_name'),
                TextEntry::make('event.name')->label('Edition'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => Enrollment::STATUS_COLORS[$state] ?? 'gray'),
                TextEntry::make('contact_name')->label('Original contact name')->placeholder('Not asked')->helperText('Optional. The default forms identify the contact by email address only.'),
                TextEntry::make('contact_email')->label('Original contact email')->copyable(),
                TextEntry::make('info_status')->label('Information form')->badge()->color(fn (string $state): string => Enrollment::INFO_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->ucfirst()->toString()),
                TextEntry::make('submitted_at')->dateTime(),
                TextEntry::make('company.name')->label('Linked company')->placeholder('Not linked yet'),
                TextEntry::make('rejection_reason')->visible(fn (Enrollment $record): bool => filled($record->rejection_reason))->columnSpanFull(),
            ]),
            Section::make('Contacts')->schema([View::make('filament.enrollments.contacts')]),
            Section::make('Possible existing companies')->schema([View::make('filament.enrollments.matches')]),
            Section::make('Company details vs. stored profile')->schema([View::make('filament.enrollments.comparison')]),
            Section::make('Submitted answers')->description('Exactly as answered, against the form version that was live at submission.')->schema([View::make('filament.enrollments.answers')]),
            Section::make('Additional information (second form)')->schema([View::make('filament.enrollments.info')]),
            Section::make('Invitation')->schema([View::make('filament.enrollments.invitations')]),
            Section::make('Accepted terms and fee')->columns(3)->collapsed()->schema([
                TextEntry::make('fee_cents_accepted')->label('Fee')->formatStateUsing(fn ($state, Enrollment $r): string => number_format($state / 100, 2, ',', '.').' '.$r->currency_accepted),
                TextEntry::make('terms_version_accepted')->label('Terms version'),
                TextEntry::make('accepted_at')->dateTime(),
                TextEntry::make('terms_html_accepted')->label('Terms text at acceptance')->html()->columnSpanFull()->placeholder('None'),
            ]),
            Section::make('History')->collapsed()->schema([View::make('filament.enrollments.history')]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')->searchable()->sortable(),
                TextColumn::make('event.name')->label('Edition')->sortable(),
                TextColumn::make('contact_email')->searchable(query: EmailSearch::exact('contact_email'))->tooltip('Search finds an exact email address; stored addresses are encrypted.'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => Enrollment::STATUS_COLORS[$state] ?? 'gray')->sortable(),
                TextColumn::make('info_status')->label('Information form')->badge()->color(fn (string $state): string => Enrollment::INFO_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->ucfirst()->toString()),
                TextColumn::make('submitted_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_id')->label('Edition')->options(fn (): array => Event::query()->orderByDesc('date')->pluck('name', 'id')->all()),
                SelectFilter::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                SelectFilter::make('info_status')->label('Information form')->options(['not_invited' => 'Not invited', 'awaiting' => 'Awaiting information', 'submitted' => 'Submitted']),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->recordUrl(fn (Enrollment $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnrollments::route('/'),
            'view' => ViewEnrollment::route('/{record}'),
        ];
    }
}
