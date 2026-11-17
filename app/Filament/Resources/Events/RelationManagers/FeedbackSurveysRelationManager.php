<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Filament\Pages\FeedbackResults;
use App\Filament\Resources\Forms\FormResource;
use App\Models\FeedbackSurvey;
use App\Services\FeedbackSurveys;
use App\Support\Feedback\QrCode;
use App\Support\Forms\FormSchema;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;

/**
 * The edition's two anonymous feedback surveys, managed like the enrollment: an on/off switch plus an optional opening
 * and closing moment, with the effective status calculated from both (App\Support\Availability).
 */
class FeedbackSurveysRelationManager extends RelationManager
{
    protected static string $relationship = 'feedbackSurveys';

    protected static ?string $title = 'Feedback';

    private const STATES = ['disabled' => 'Disabled', 'not_open_yet' => 'Scheduled', 'open' => 'Open', 'closed' => 'Closed'];

    private const COLORS = ['disabled' => 'gray', 'not_open_yet' => 'warning', 'open' => 'success', 'closed' => 'danger'];

    public function isReadOnly(): bool
    {
        return false; // the actions check their own permissions; this must also work on the view page
    }

    public function table(Table $table): Table
    {
        $timezone = config('audit.display_timezone');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('responses')->with('form.latestVersion')->orderBy('audience', 'desc'))
            ->paginated(false)
            ->description('Each form is switched on or off by hand and opens and closes by itself within its dates. Switching it off always wins over the dates.')
            ->columns([
                TextColumn::make('form.name')->label('Form')->description(fn (FeedbackSurvey $record): string => ucfirst($record->audience)),
                TextColumn::make('state')->label('Status')->badge()
                    ->state(fn (FeedbackSurvey $record): string => $record->state())
                    ->formatStateUsing(fn (string $state): string => self::STATES[$state])
                    ->color(fn (string $state): string => self::COLORS[$state]),
                IconColumn::make('enabled')->label('Switched on')->boolean(),
                TextColumn::make('opens_at')->label('Opens')->dateTime('j M Y H:i', $timezone)->placeholder('No opening date'),
                TextColumn::make('closes_at')->label('Closes')->dateTime('j M Y H:i', $timezone)->placeholder('No closing date'),
                TextColumn::make('responses_count')->label('Responses')->numeric(),
                TextColumn::make('form.latestVersion.version')->label('Questions')->prefix('v')->placeholder('Not published'),
                TextColumn::make('url')->label('Public link')->state(fn (FeedbackSurvey $record): string => $record->url())->copyable()->copyMessage('Link copied')->limit(24)->tooltip(fn (FeedbackSurvey $record): string => $record->url()),
            ])
            ->recordActions([
                Action::make('settings')->label('Settings')->icon('heroicon-o-cog-6-tooth')->authorize('update')
                    ->modalHeading(fn (FeedbackSurvey $record): string => 'Settings: '.$record->form->name)->modalWidth('2xl')
                    ->fillForm(fn (FeedbackSurvey $record): array => ['name' => $record->form->name, 'enabled' => $record->enabled, 'opens_at' => $record->opens_at, 'closes_at' => $record->closes_at])
                    ->schema([
                        Section::make('General')->columns(2)->schema([
                            TextInput::make('name')->label('Form name')->required()->maxLength(255)->columnSpanFull(),
                            Placeholder::make('audience')->label('Audience')->content(fn (FeedbackSurvey $record): string => $record->audienceLabel()),
                            Placeholder::make('edition')->label('Edition')->content(fn (FeedbackSurvey $record): string => $record->event->name),
                        ]),
                        Section::make('Availability')->description('Off: the link is unavailable and submissions are rejected, whatever the dates say. On: the form opens and closes by itself.')->columns(2)->schema([
                            Toggle::make('enabled')->label('Form open')->columnSpanFull(),
                            DateTimePicker::make('opens_at')->label('Opens at')->timezone($timezone)->helperText('Empty: open as soon as it is switched on.'),
                            DateTimePicker::make('closes_at')->label('Closes at')->timezone($timezone)->after('opens_at')->helperText('Empty: stays open until switched off.'),
                            Placeholder::make('effective')->label('Current status')->columnSpanFull()
                                ->content(fn (FeedbackSurvey $record): string => self::STATES[$record->state()].' (saved settings; saving applies the new ones)'),
                        ]),
                    ])
                    ->action(fn (array $data, FeedbackSurvey $record) => $this->run(function () use ($data, $record): void {
                        DB::transaction(function () use ($data, $record): void {
                            $record->update(['enabled' => (bool) $data['enabled'], 'opens_at' => $data['opens_at'], 'closes_at' => $data['closes_at']]);
                            $record->form->update(['name' => $data['name']]);
                        });
                    }, 'Settings saved')),
                Action::make('activate')->icon('heroicon-o-play-circle')->color('success')->authorize('update')
                    ->visible(fn (FeedbackSurvey $record): bool => ! $record->enabled)
                    ->requiresConfirmation()->modalHeading('Switch this form on?')
                    ->modalDescription('The form opens and closes by itself within its dates. Anyone with the link can then answer anonymously while it is open.')
                    ->action(fn (FeedbackSurvey $record) => $this->run(fn () => app(FeedbackSurveys::class)->setEnabled($record, true), 'Form switched on')),
                Action::make('deactivate')->icon('heroicon-o-pause-circle')->color('warning')->authorize('update')
                    ->visible(fn (FeedbackSurvey $record): bool => $record->enabled)
                    ->requiresConfirmation()->modalHeading('Switch this form off?')
                    ->modalDescription('The link stops working and submissions are rejected right away, even during its dates. Responses and settings are kept; you can switch it on again at any time.')
                    ->action(fn (FeedbackSurvey $record) => $this->run(fn () => app(FeedbackSurveys::class)->setEnabled($record, false), 'Form switched off')),
                Action::make('editQuestions')->label('Edit questions')->icon('heroicon-o-pencil-square')->color('gray')
                    ->visible(fn (FeedbackSurvey $record): bool => Gate::allows('update', $record->form))
                    ->url(fn (FeedbackSurvey $record): string => FormResource::getUrl('edit', ['record' => $record->form_id])),
                Action::make('preview')->icon('heroicon-o-eye')->color('gray')->authorize('view')
                    ->modalSubmitAction(false)->modalWidth('3xl')->modalHeading(fn (FeedbackSurvey $record): string => 'Preview: '.$record->form->name)
                    ->modalContent(fn (FeedbackSurvey $record): View => view('filament.forms.preview', [
                        'schema' => $record->form->draft_schema,
                        'problems' => FormSchema::problems($record->form->draft_schema, $record->form->kind),
                    ])),
                ActionGroup::make([
                    Action::make('results')->label('View results')->icon('heroicon-o-chart-bar')->authorize('view')
                        ->url(fn (FeedbackSurvey $record): string => FeedbackResults::getUrl(['events' => [$record->event_id], 'audience' => $record->audience])),
                    Action::make('copyLink')->label('Copy public link')->icon('heroicon-o-clipboard')->authorize('view')
                        ->extraAttributes(fn (FeedbackSurvey $record): array => ['x-on:click' => 'navigator.clipboard.writeText('.Js::from($record->url())->toHtml().')'])
                        ->action(fn () => Notification::make()->success()->title('Link copied')->send()),
                    Action::make('qr')->label('QR code')->icon('heroicon-o-qr-code')->authorize('view')
                        ->modalSubmitAction(false)->modalCancelActionLabel('Close')->modalWidth('md')
                        ->modalHeading(fn (FeedbackSurvey $record): string => 'QR code: '.$record->form->name)
                        ->modalContent(fn (FeedbackSurvey $record): View => view('filament.feedback.qr', ['survey' => $record, 'svg' => QrCode::svg($record->url())])),
                    Action::make('reset')->label('Reset to default template')->icon('heroicon-o-arrow-uturn-left')->color('danger')->authorize('update')
                        ->requiresConfirmation()->modalHeading('Reset the questions to the default template?')
                        ->modalDescription('Replaces the questions with the current published default template as a new version. Earlier versions and every response stay and remain readable.')
                        ->action(fn (FeedbackSurvey $record) => $this->run(fn () => app(FeedbackSurveys::class)->reset($record, auth()->user()), 'Questions reset to the default template')),
                ]),
            ])
            ->toolbarActions([]);
    }

    private function run(\Closure $do, string $success): void
    {
        try {
            $do();
        } catch (ValidationException $e) {
            Notification::make()->danger()->title('Not possible')->body(collect($e->errors())->flatten()->implode("\n"))->persistent()->send();

            return;
        }

        Notification::make()->success()->title($success)->send();
    }
}
