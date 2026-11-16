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
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;

/** The edition's two anonymous feedback surveys. They are created with the edition; nothing is created by hand here. */
class FeedbackSurveysRelationManager extends RelationManager
{
    protected static string $relationship = 'feedbackSurveys';

    protected static ?string $title = 'Feedback';

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
            ->columns([
                TextColumn::make('form.name')->label('Survey'),
                TextColumn::make('audience')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('state')->label('Status')->badge()
                    ->state(fn (FeedbackSurvey $record): string => $record->state())
                    ->formatStateUsing(fn (string $state): string => ['draft' => 'Draft', 'open' => 'Open', 'not_open_yet' => 'Scheduled', 'closed' => 'Closed'][$state])
                    ->color(fn (string $state): string => ['draft' => 'gray', 'open' => 'success', 'not_open_yet' => 'warning', 'closed' => 'danger'][$state]),
                TextColumn::make('opens_at')->label('Opens')->dateTime('j M Y H:i', $timezone)->placeholder('Immediately'),
                TextColumn::make('closes_at')->label('Closes')->dateTime('j M Y H:i', $timezone)->placeholder('Never'),
                TextColumn::make('responses_count')->label('Responses')->numeric(),
                TextColumn::make('url')->label('Public link')->state(fn (FeedbackSurvey $record): string => $record->url())->copyable()->copyMessage('Link copied')->limit(28)->tooltip(fn (FeedbackSurvey $record): string => $record->url()),
            ])
            ->recordActions([
                Action::make('results')->icon('heroicon-o-chart-bar')->color('gray')->authorize('view')
                    ->url(fn (FeedbackSurvey $record): string => FeedbackResults::getUrl(['events' => [$record->event_id], 'audience' => $record->audience])),
                Action::make('editQuestions')->label('Edit questions')->icon('heroicon-o-pencil-square')->color('gray')
                    ->visible(fn (FeedbackSurvey $record): bool => Gate::allows('update', $record->form))
                    ->url(fn (FeedbackSurvey $record): string => FormResource::getUrl('edit', ['record' => $record->form_id])),
                Action::make('preview')->icon('heroicon-o-eye')->color('gray')->authorize('view')
                    ->modalSubmitAction(false)->modalWidth('3xl')->modalHeading(fn (FeedbackSurvey $record): string => 'Preview: '.$record->form->name)
                    ->modalContent(fn (FeedbackSurvey $record): View => view('filament.forms.preview', [
                        'schema' => $record->form->draft_schema,
                        'problems' => FormSchema::problems($record->form->draft_schema, $record->form->kind),
                    ])),
                Action::make('publish')->icon('heroicon-o-rocket-launch')->authorize('update')
                    ->visible(fn (FeedbackSurvey $record): bool => $record->status !== 'published')
                    ->requiresConfirmation()->modalHeading('Publish this survey?')
                    ->modalDescription('Publishes the current questions as a new version and opens the survey for its schedule. Anyone with the link can then answer anonymously.')
                    ->action(fn (FeedbackSurvey $record) => $this->run(fn () => app(FeedbackSurveys::class)->publish($record, auth()->user()), 'Survey published')),
                Action::make('unpublish')->icon('heroicon-o-eye-slash')->color('warning')->authorize('update')
                    ->visible(fn (FeedbackSurvey $record): bool => $record->status === 'published')
                    ->requiresConfirmation()->modalDescription('The public link stops working (404) until the survey is published again. Responses are kept.')
                    ->action(fn (FeedbackSurvey $record) => $this->run(fn () => app(FeedbackSurveys::class)->unpublish($record), 'Survey unpublished')),
                ActionGroup::make([
                    Action::make('schedule')->icon('heroicon-o-clock')->authorize('update')
                        ->fillForm(fn (FeedbackSurvey $record): array => ['opens_at' => $record->opens_at, 'closes_at' => $record->closes_at])
                        ->schema([
                            DateTimePicker::make('opens_at')->label('Opens at')->timezone($timezone)->helperText('Empty: open as soon as it is published.'),
                            DateTimePicker::make('closes_at')->label('Closes at')->timezone($timezone)->after('opens_at')->helperText('Empty: stays open until closed by hand.'),
                        ])
                        ->action(fn (array $data, FeedbackSurvey $record) => $this->run(fn () => $record->update(['opens_at' => $data['opens_at'], 'closes_at' => $data['closes_at']]), 'Schedule saved')),
                    Action::make('close')->label('Close now')->icon('heroicon-o-lock-closed')->color('warning')->authorize('update')
                        ->visible(fn (FeedbackSurvey $record): bool => $record->status === 'published')
                        ->requiresConfirmation()->modalDescription('Visitors will see that feedback collection has ended. You can publish it again later.')
                        ->action(fn (FeedbackSurvey $record) => $this->run(fn () => app(FeedbackSurveys::class)->close($record), 'Survey closed')),
                    Action::make('copyLink')->label('Copy public link')->icon('heroicon-o-clipboard')->authorize('view')
                        ->extraAttributes(fn (FeedbackSurvey $record): array => ['x-on:click' => 'navigator.clipboard.writeText('.Js::from($record->url())->toHtml().')'])
                        ->action(fn () => Notification::make()->success()->title('Link copied')->send()),
                    Action::make('qr')->label('QR code')->icon('heroicon-o-qr-code')->authorize('view')
                        ->modalSubmitAction(false)->modalCancelActionLabel('Close')->modalWidth('md')
                        ->modalHeading(fn (FeedbackSurvey $record): string => 'QR code: '.$record->form->name)
                        ->modalContent(fn (FeedbackSurvey $record): View => view('filament.feedback.qr', ['survey' => $record, 'svg' => QrCode::svg($record->url()), 'png' => extension_loaded('imagick')])),
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
