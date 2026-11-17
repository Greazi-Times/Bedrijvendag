<?php

namespace App\Filament\Pages;

use App\Models\CompanyAttendance;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventStand;
use App\Services\Analytics\Analytics;
use App\Services\Analytics\CateringStats;
use App\Support\Forms\FormSchema;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Check companies in on the day. The companies and stands come from the event map (event_stands) and attendance is the
 * only thing stored here. Dietary wishes are read live from the latest submitted information form while the confirmation
 * is open: never copied, never part of the table rows, and gone as soon as the retention cleanup removes the answers.
 */
class Attendance extends Page
{
    protected string $view = 'filament.pages.attendance';

    protected static ?string $slug = 'attendance';

    protected static ?string $title = 'Aanwezigheid';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Edition';

    protected static ?int $navigationSort = 5;

    #[Url]
    public ?int $eventId = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $sort = 'name';

    /** Company shown in the confirmation; only the id is kept in the component state. */
    public ?int $detailCompanyId = null;

    public bool $justCheckedIn = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('attendance.view') ?? false;
    }

    public function mount(): void
    {
        $this->eventId = Event::query()->whereKey($this->eventId)->value('id') ?? app(Analytics::class)->defaultEvent()?->id;
    }

    public function checkIn(int $companyId): void
    {
        $this->authorizeUpdate();

        if (! $this->standsOf($companyId)) {
            Notification::make()->title('This company has no stand in this edition.')->danger()->send();

            return;
        }

        try {
            $changed = DB::transaction(function () use ($companyId): bool {
                // insertOrIgnore: two tabs racing on the unique key must not turn into an error for the second one.
                CompanyAttendance::query()->insertOrIgnore(['event_id' => $this->eventId, 'company_id' => $companyId, 'created_at' => now(), 'updated_at' => now()]);
                $row = CompanyAttendance::query()->where(['event_id' => $this->eventId, 'company_id' => $companyId])->lockForUpdate()->first(); // a second click or tab never overwrites the first timestamp

                return $row->checked_in_at === null && $row->forceFill(['checked_in_at' => now(), 'checked_in_by' => auth()->id()])->save();
            });
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Check-in failed. Nothing was saved, please try again.')->danger()->send();

            return;
        }

        $this->detailCompanyId = $companyId;
        $this->justCheckedIn = $changed;

        if (! $changed) {
            Notification::make()->title('Already checked in; the original time is kept.')->warning()->send();
        }
    }

    public function reverse(int $companyId): void
    {
        $this->authorizeUpdate();

        CompanyAttendance::query()->where(['event_id' => $this->eventId, 'company_id' => $companyId])->whereNotNull('checked_in_at')->first()
            ?->forceFill(['checked_in_at' => null, 'checked_in_by' => null])->save();

        $this->closeDetails();
    }

    public function showDetails(int $companyId): void
    {
        $this->detailCompanyId = $companyId;
        $this->justCheckedIn = false;
    }

    public function closeDetails(): void
    {
        $this->detailCompanyId = null;
        $this->justCheckedIn = false;
    }

    private function authorizeUpdate(): void
    {
        abort_unless(auth()->user()?->can('attendance.update'), 403);
    }

    /** @return list<string>|null stand numbers of the company in the selected edition, null when it has none */
    private function standsOf(int $companyId): ?array
    {
        return $this->companies()->firstWhere('id', $companyId)['stands'] ?? null;
    }

    /** @return Collection<int, array{id: int, name: string, stands: list<string>, checked_in_at: ?\Illuminate\Support\Carbon}> one entry per company */
    public function companies(): Collection
    {
        $attendance = CompanyAttendance::query()->where('event_id', $this->eventId)->get()->keyBy('company_id');

        return EventStand::query()->where('event_id', $this->eventId)->whereNotNull('company_id')->with('company:id,name')->get()
            ->groupBy('company_id')
            ->map(fn (Collection $stands, int $companyId): array => [
                'id' => $companyId,
                'name' => (string) $stands->first()->company?->name,
                'stands' => $stands->pluck('stand_number')->map(fn ($n): string => (string) $n)->sortBy(fn (string $n): int => (int) $n)->values()->all(),
                'checked_in_at' => $attendance[$companyId]->checked_in_at ?? null,
            ])->values();
    }

    /**
     * Dietary wishes as "N× diet" lines from the latest submission of the company's approved enrollment.
     * Empty when nothing was submitted or the retention cleanup already removed the answers.
     *
     * @return list<string>
     */
    private function dietary(int $companyId): array
    {
        $submission = Enrollment::query()->where(['event_id' => $this->eventId, 'company_id' => $companyId, 'status' => Enrollment::STATUS_APPROVED])
            ->with('latestInfoSubmission.formVersion:id,schema')->first()?->latestInfoSubmission;
        $answers = $submission?->answers;

        if (! is_array($answers)) {
            return [];
        }

        $entries = $answers[FormSchema::mappedKey($submission->formVersion?->schema ?? [], 'lunch.participants') ?? 'lunch_participants'] ?? [];
        $counts = [];

        foreach (is_array($entries) ? $entries : [] as $entry) {
            foreach ((array) ($entry['lunch_diet'] ?? []) as $diet) {
                if (! isset(CateringStats::DIETS[$diet])) {
                    continue; // "none" is not a wish
                }

                $label = $diet === 'other' && filled($entry['lunch_diet_other'] ?? null) ? CateringStats::DIETS[$diet].': '.$entry['lunch_diet_other'] : CateringStats::DIETS[$diet];
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }
        }

        return collect($counts)->map(fn (int $n, string $label): string => "{$n}× {$label}")->values()->all();
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $all = $this->companies();
        $present = $all->filter(fn (array $c): bool => $c['checked_in_at'] !== null)->count();
        $needle = mb_strtolower(trim($this->search));

        $rows = $all
            ->filter(fn (array $c): bool => $needle === '' || str_contains(mb_strtolower($c['name']), $needle) || in_array($needle, $c['stands'], true))
            ->filter(fn (array $c): bool => match ($this->status) {
                'present' => $c['checked_in_at'] !== null,
                'not_checked_in' => $c['checked_in_at'] === null,
                default => true,
            })
            ->sortBy(match ($this->sort) {
                'stand' => fn (array $c): int => (int) ($c['stands'][0] ?? 0),
                'checked_in' => fn (array $c): int => $c['checked_in_at']?->getTimestamp() ?? PHP_INT_MAX,
                default => fn (array $c): string => mb_strtolower($c['name']),
            })->values();

        $detail = $this->detailCompanyId ? $all->firstWhere('id', $this->detailCompanyId) : null;

        return [
            'editions' => Event::query()->orderByDesc('date')->orderByDesc('id')->get(['id', 'name']),
            'rows' => $rows,
            'stats' => ['total' => $all->count(), 'present' => $present, 'missing' => $all->count() - $present, 'percentage' => $all->isEmpty() ? 0 : (int) round($present / $all->count() * 100)],
            'detail' => $detail,
            'dietary' => $detail && auth()->user()?->can('attendance.dietary') ? $this->dietary($detail['id']) : null,
            'canUpdate' => auth()->user()?->can('attendance.update'),
        ];
    }
}
