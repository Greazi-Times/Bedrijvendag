<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackResponse;
use App\Models\FeedbackSurvey;
use App\Services\Analytics\FeedbackStats;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use UnitEnum;

/**
 * All feedback statistics on one page and one Livewire component: the numbers are aggregated on the server and drawn as
 * plain HTML, so the page never mounts a widget per question (Livewire refuses requests with more than 20 components).
 */
class FeedbackResults extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.feedback-results';

    protected static ?string $slug = 'feedback-results';

    protected static ?string $title = 'Feedback results';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Edition';

    protected static ?int $navigationSort = 4;

    private const MAX_EDITIONS = 8;

    /** @var list<int|string> */
    #[Url]
    public array $events = [];

    #[Url]
    public string $audience = FeedbackSurvey::STUDENTS;

    #[Url]
    public ?string $question = null;

    #[Url]
    public string $search = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('feedback.view') ?? false;
    }

    public function mount(): void
    {
        $this->events = $this->events !== [] ? $this->events : array_filter([Event::query()->whereHas('feedbackSurveys.responses')->orderByDesc('date')->value('id') ?? Event::query()->orderByDesc('date')->value('id')]);
    }

    public function updated(string $property): void
    {
        $this->resetPage();
        $property === 'audience' && $this->question = null;
    }

    /** @return list<int> */
    private function eventIds(): array
    {
        return array_slice(array_values(array_unique(array_map('intval', $this->events))), 0, self::MAX_EDITIONS);
    }

    /** @return EloquentCollection<int, FeedbackSurvey> oldest edition first */
    private function surveys(?string $audience = null): EloquentCollection
    {
        return FeedbackSurvey::query()->with(['event', 'form'])->whereIn('event_id', $this->eventIds())
            ->when($audience ?? $this->audience, fn ($q, string $a) => $q->where('audience', $a))->get()
            ->sortBy(fn (FeedbackSurvey $s) => $s->event->date)->values();
    }

    /** Everything the view shows, computed once per render. @return array<string, mixed> */
    public function getReport(): array
    {
        $stats = app(FeedbackStats::class);
        $surveys = $this->surveys();
        $groups = $stats->merge($surveys);
        $ratings = collect($groups)->where('type', 'rating');
        $ranked = $ratings->filter(fn (array $g) => FeedbackStats::score($g) !== null)->sortByDesc(fn (array $g) => FeedbackStats::score($g))->values();
        $newest = $surveys->last();
        $headline = $newest ? collect($stats->survey($newest))->firstWhere('type', 'rating') : null;
        $headline = $headline ? ($groups[$headline['key'].'|'.$headline['signature']] ?? null) : null;

        $perAudience = FeedbackResponse::query()->join('feedback_surveys as s', 's.id', '=', 'feedback_responses.feedback_survey_id')
            ->whereIn('s.event_id', $this->eventIds())->groupBy('s.audience')->selectRaw('s.audience, COUNT(*) as c')->pluck('c', 'audience');

        $trend = FeedbackResponse::query()->whereIn('feedback_survey_id', $surveys->pluck('id'))->groupBy('submitted_on')
            ->orderBy('submitted_on')->selectRaw('submitted_on, COUNT(*) as c')->pluck('c', 'submitted_on')->mapWithKeys(fn ($c, $d) => [substr((string) $d, 0, 10) => $c]);

        return [
            'surveys' => $surveys,
            'total' => $perAudience->sum(),
            'students' => (int) ($perAudience[FeedbackSurvey::STUDENTS] ?? 0),
            'companies' => (int) ($perAudience[FeedbackSurvey::COMPANIES] ?? 0),
            'headline' => $headline,
            'best' => $ranked->take(3),
            'worst' => $ranked->count() > 3 ? $ranked->reverse()->take(3)->values() : collect(),
            'trend' => $trend,
            'groups' => collect($groups),
            'comparable' => collect($groups)->filter(fn (array $g) => count($g['per_survey']) > 1 && in_array($g['type'], ['rating', 'radio', 'select'], true))->values(),
            'textQuestions' => collect($groups)->whereIn('type', ['text', 'textarea'])->pluck('label', 'key'),
            'texts' => $stats->texts($surveys->pluck('id')->all(), $this->question ?: null, trim($this->search) ?: null),
            'editions' => Event::query()->whereHas('feedbackSurveys')->orderByDesc('date')->get(['id', 'name', 'date']),
        ];
    }

    public function redact(int $id): void
    {
        $answer = $this->moderatable($id);
        $answer->forceFill(['value_text' => null, 'redacted_at' => now()])->save();
        Notification::make()->success()->title('Answer redacted')->send();
    }

    public function remove(int $id): void
    {
        $this->moderatable($id)->delete();
        Notification::make()->success()->title('Answer removed')->send();
    }

    /** Only written answers of a survey in the current selection, only for staff who may delete feedback. */
    private function moderatable(int $id): FeedbackAnswer
    {
        $answer = FeedbackAnswer::query()->with('response.survey', 'response.formVersion')->findOrFail($id);
        Gate::authorize('delete', $answer->response->survey);
        abort_unless($this->surveys()->contains('id', $answer->response->feedback_survey_id) && app(FeedbackStats::class)->isText($answer), 404);

        return $answer;
    }
}
