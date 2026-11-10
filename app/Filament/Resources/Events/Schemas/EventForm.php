<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Models\Event;
use App\Services\FormResolver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                TextInput::make('max_stands')
                    ->label('Company stands')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),
                TextInput::make('partner_stand_count')
                    ->label('Partner stands')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->nullable(),
                Select::make('eventPartners')
                    ->label('Organising partners')
                    ->relationship('eventPartners', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->nullable(),
                TextInput::make('google_photos_album_url')
                    ->url()
                    ->default(null),
                RichEditor::make('description')
                    ->label('Description')
                    ->default(null)
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'bulletList',
                        'orderedList',
                        'h2',
                        'h3',
                        'blockquote',
                        'link',
                    ])
                    ->required(),
                FileUpload::make('header_image_path')
                    ->label('Header image')
                    ->image()
                    ->directory('event-headers')
                    ->disk('public')
                    ->imageEditor()
                    ->nullable(),
                Select::make('copy_forms_from')
                    ->label('Copy custom forms from')
                    ->placeholder('Use the global default forms (recommended)')
                    ->helperText('Only editions with their own custom form are listed. Editions using the global defaults need no copying: new editions inherit them automatically.')
                    ->options(fn (): array => Event::query()->where(fn ($q) => $q->whereNotNull('enrollment_form_id')->orWhereNotNull('info_form_id'))->orderByDesc('date')->pluck('name', 'id')->all())
                    ->dehydrated(false)
                    ->visible(fn (?Event $record): bool => $record === null),
                Section::make('Enrollment')
                    ->description('Companies enroll through a private link per edition.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->visible(fn (?Event $record): bool => $record !== null)
                    ->schema([
                        TextInput::make('enrollment_link')
                            ->label('Enrollment link')
                            ->columnSpanFull()
                            ->readOnly()
                            ->dehydrated(false)
                            ->copyable(copyMessage: 'Link copied')
                            ->formatStateUsing(fn (?Event $record): ?string => $record?->enrollmentUrl()),
                        Toggle::make('enrollment_enabled')->label('Enrollment open')->helperText('Off: the link shows a "closed" message.'),
                        TextInput::make('max_lunch_participants')->label('Max lunch participants per company')->numeric()->minValue(0)->maxValue(20)->default(3),
                        DateTimePicker::make('enrollment_opens_at')->label('Opens at'),
                        DateTimePicker::make('enrollment_closes_at')->label('Closes at')->after('enrollment_opens_at'),
                        DateTimePicker::make('info_closes_at')->label('Information form editable until')->helperText('Empty: no deadline.')->columnSpanFull(),
                        TextInput::make('fee_cents')->label('Participation fee')->numeric()->minValue(0)->step(0.01)
                            ->formatStateUsing(fn ($state): ?string => $state === null ? null : number_format($state / 100, 2, '.', ''))
                            ->dehydrateStateUsing(fn ($state): int => (int) round(((float) $state) * 100)),
                        Select::make('currency')->options(['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP'])->default('EUR')->required(),
                        RichEditor::make('terms_html')->label('Terms and conditions')->columnSpanFull()
                            ->helperText('Editing the text starts a new terms version; earlier acceptances keep the version they accepted.'),
                        TextInput::make('enrollment_form_status')->label('Enrollment form')
                            ->disabled()->dehydrated(false)
                            ->formatStateUsing(fn (?Event $record): ?string => $record ? self::formStatus($record, 'enrollment') : null)
                            ->helperText('Inherited from the global default unless customized. Use the buttons at the top of the page.'),
                        TextInput::make('info_form_status')->label('Information form (after approval)')
                            ->disabled()->dehydrated(false)
                            ->formatStateUsing(fn (?Event $record): ?string => $record ? self::formStatus($record, 'info') : null),
                    ]),
            ]);
    }

    /** Plain-language description of which form an edition uses. */
    public static function formStatus(Event $event, string $kind): string
    {
        $resolver = app(FormResolver::class);
        $form = $resolver->resolve($event, $kind);
        $version = $form?->latestVersion?->version;
        $published = $version ? "version {$version}" : 'not published yet';

        return $resolver->isCustomized($event, $kind)
            ? "Custom for this edition: {$form->name} ({$published})"
            : "Global default ({$published})";
    }
}
