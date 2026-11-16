<?php

namespace App\Services\Analytics;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\EventStands\EventStandResource;
use App\Models\User;

/** Everything the Overzicht needs in one structure, so the tab is a single widget. Cards and lists the user may not see are left out. */
class OverviewData
{
    /** Attention items shown on the overview, in order. Keys are AdminStats keys. */
    private const ATTENTION = ['pending', 'profile', 'no_stand', 'info_missing', 'contact_changes', 'stand_warnings'];

    public function __construct(
        private EnrollmentStats $enrollments,
        private Participation $participation,
        private EventPlanningStats $planning,
        private CateringStats $catering,
        private AdminStats $admin,
        private Insights $insights,
    ) {}

    /** @return array<string, mixed> */
    public function build(Scope $scope, User $user): array
    {
        $event = $scope->event;
        $eventId = $event?->id;
        $s = $user->can('enrollments.view') ? $this->enrollments->summary($eventId) : null;
        $prev = $s && $scope->compare ? $this->enrollments->summary($scope->compare->id) : null;
        $name = $scope->compare?->name;
        $list = fn (array $f = []) => EnrollmentResource::getUrl('index', ['filters' => array_filter(['event_id' => ['value' => $eventId], ...$f], fn ($x) => $x !== ['value' => null])]);
        $status = fn (string $v) => $list(['status' => ['value' => $v]]);
        $primary = [];
        $secondary = [];

        if ($s) {
            $vs = $prev ? Fmt::versus($s['approved'], $prev['approved'], $name) : null;
            $primary[] = ['label' => 'Goedgekeurde bedrijven', 'value' => Fmt::int($s['approved']), 'sub' => $vs['text'] ?? 'Bevestigde inschrijvingen', 'tone' => $vs['color'] ?? 'gray', 'url' => $status('approved'), 'hint' => 'Registratie, geen gemeten aanwezigheid.'];
        }

        if ($event && $user->can('companies.view')) {
            $class = $this->participation->classify($event->id);
            $total = max(1, $class['participants']);
            $history = Dashboard::getUrl(['sectie' => 'bedrijven', 'onderdeel' => 'deelnamegeschiedenis']);
            $primary[] = ['label' => 'Nieuwe bedrijven', 'value' => Fmt::int(count($class['new'])), 'sub' => Fmt::pct(count($class['new']) / $total * 100).' van de deelnemers', 'tone' => 'info', 'url' => $history, 'hint' => 'Bedrijven die voor het eerst deelnemen.'];
            $primary[] = ['label' => 'Terugkerende bedrijven', 'value' => Fmt::int(count($class['returning'])), 'sub' => Fmt::pct(count($class['returning']) / $total * 100).' van de deelnemers', 'tone' => 'success', 'url' => $history, 'hint' => 'Bedrijven die eerder hebben deelgenomen.'];
        }

        if ($event && $user->can('event-stands.view')) {
            $stands = $this->planning->stands($event->id);
            $per = $this->planning->perEdition();
            $before = $scope->compare ? ($per[$scope->compare->id]['occupancy'] ?? null) : null;
            $vs = $before !== null && $stands['occupancy'] !== null ? Fmt::versus($stands['occupancy'], $before, $name) : null;
            $primary[] = ['label' => 'Standbezetting', 'value' => Fmt::pct($stands['occupancy']), 'sub' => Fmt::int($stands['company_occupied']).' van '.Fmt::int($stands['company_total']).' bedrijfsstands'.($vs ? ' · '.$vs['text'] : ''), 'tone' => 'gray', 'url' => EventStandResource::getUrl('index'), 'hint' => 'Bezette bedrijfsstands; partnerstands worden apart geteld.', 'progress' => $stands['occupancy']];
            $secondary[] = ['label' => 'Goedgekeurd zonder stand', 'value' => $stands['approved_without_stand'] === null ? Fmt::DASH : Fmt::int(count($stands['approved_without_stand'])), 'url' => EventStandResource::getUrl('index')];
        }

        if ($event && $user->can(Kpis::FINANCIAL_PERMISSION)) {
            $money = $this->planning->revenue()[$event->id]['currencies'] ?? null;
            $primary[] = ['label' => 'Verwachte opbrengst', 'value' => $money === null ? Fmt::DASH : Fmt::moneyPerCurrency(collect($money)->map(fn (array $c): int => $c['approved'])->all()), 'sub' => 'Geaccepteerd, geen ontvangen betaling', 'tone' => 'gray', 'url' => Dashboard::getUrl(['sectie' => 'financien']), 'hint' => 'Som van de bij inschrijving geaccepteerde bedragen van goedgekeurde bedrijven.'];
        }

        if ($s) {
            $primary[] = ['label' => 'Tweede formulier ingevuld', 'value' => Fmt::pct($s['info_rate']), 'sub' => Fmt::int($s['info_done']).' van '.Fmt::int($s['approved']).' goedgekeurd', 'tone' => 'gray', 'url' => $list(['status' => ['value' => 'approved'], 'info_status' => ['value' => 'submitted']])];
            array_unshift($secondary,
                ['label' => 'In behandeling', 'value' => Fmt::int($s['pending']), 'url' => $status('pending')],
                ['label' => 'Afgewezen', 'value' => Fmt::int($s['rejected']), 'url' => $status('rejected')],
            );
            $secondary[] = ['label' => 'Tweede formulier ontbreekt', 'value' => Fmt::int($s['info_missing']), 'url' => $list(['status' => ['value' => 'approved'], 'info_status' => ['value' => 'awaiting']])];
        }

        if ($event && $user->can('enrollments.view')) {
            $c = $this->catering->forEvent($event->id);
            $secondary[] = ['label' => 'Lunchdeelnemers', 'value' => $c['available'] ? Fmt::int($c['lunch']['participants']) : Fmt::DASH, 'url' => Dashboard::getUrl(['sectie' => 'evenement', 'onderdeel' => 'catering-en-borrel'])];
            $secondary[] = ['label' => 'Borrelgasten', 'value' => $c['available'] ? Fmt::int($c['drinks']['people']) : Fmt::DASH, 'url' => Dashboard::getUrl(['sectie' => 'evenement', 'onderdeel' => 'catering-en-borrel'])];
        }

        $attention = collect($this->admin->items($scope, $user))->whereIn('key', self::ATTENTION)
            ->sortBy(fn (array $i): int => array_search($i['key'], self::ATTENTION, true))->values()->all();

        return [
            'event' => $event,
            'compare' => $scope->compare,
            'primary' => $primary,
            'secondary' => $secondary,
            'attention' => $attention,
            'insights' => $this->insights->for($scope, $user),
        ];
    }
}
