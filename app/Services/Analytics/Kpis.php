<?php

namespace App\Services\Analytics;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\User;

/**
 * The stat cards of every dashboard section. Each card: label, value, optional description / color / url / tooltip.
 * Cards the user may not see are left out before they are computed.
 */
class Kpis
{
    public const FINANCIAL_PERMISSION = 'statistics.financial';

    public function __construct(
        private Participation $participation,
        private EnrollmentStats $enrollments,
        private EventPlanningStats $planning,
        private CateringStats $catering,
        private TaxonomyStats $taxonomy,
        private AdminStats $admin,
    ) {}

    /** @return list<array{label: string, value: string, description: ?string, color: string, url: ?string, tooltip: ?string}> */
    public function section(string $section, Scope $scope, User $user): array
    {
        $cards = match ($section) {
            'overview' => $this->overview($scope, $user),
            'actions' => $this->actions($scope, $user),
            'admin' => array_values(array_filter($this->enrollmentCards($scope, $user), fn (array $c): bool => in_array($c['label'], ['In behandeling', 'Gemiddelde beoordelingstijd', 'Oudste openstaande inschrijving'], true))),
            'enrollments' => $this->enrollmentCards($scope, $user),
            'breakdown' => $this->breakdown($scope, $user),
            'retention' => $this->retention($scope, $user),
            'stands' => $this->stands($scope, $user),
            'finance' => $this->finance($scope, $user),
            'catering' => $this->catering($scope, $user),
            'promo' => $this->promo($scope, $user),
            'taxonomy' => $this->taxonomy($scope, $user),
            default => [],
        };

        if ($cards === [] && ! $scope->event && ! in_array($section, ['enrollments', 'actions', 'retention'], true)) {
            return [$this->card('Kies een editie', Fmt::DASH, 'Deze cijfers gelden per editie. Selecteer een editie in het filter.')];
        }

        return $cards;
    }

    /** @return list<array<string, mixed>> */
    private function overview(Scope $scope, User $user): array
    {
        $event = $scope->event;
        $cards = [];

        if ($user->can('enrollments.view')) {
            $summary = $this->enrollments->summary($event?->id);
            $previous = $scope->compare ? $this->enrollments->summary($scope->compare->id) : null;
            $vs = fn ($key, bool $better = true) => $previous ? Fmt::versus($summary[$key], $previous[$key], $scope->compare->name, $better) : null;
            $list = fn (array $filters = []) => EnrollmentResource::getUrl('index', ['filters' => array_filter(['event_id' => ['value' => $event?->id], ...$filters], fn ($f) => $f !== ['value' => null])]);

            $cards[] = $this->card('Geregistreerde bedrijven', Fmt::int($summary['total']), $vs('total')['text'] ?? 'Alle inschrijvingen', $vs('total')['color'] ?? 'gray', $list(), 'Aantal ingediende inschrijvingen, ongeacht status.');
            $cards[] = $this->card('Goedgekeurde bedrijven', Fmt::int($summary['approved']), $vs('approved')['text'] ?? null, $vs('approved')['color'] ?? 'success', $list(['status' => ['value' => 'approved']]), 'Bevestigde inschrijvingen. Dit is registratie, geen gemeten aanwezigheid.');
            $cards[] = $this->card('In behandeling', Fmt::int($summary['pending']), null, $summary['pending'] > 0 ? 'warning' : 'gray', $list(['status' => ['value' => 'pending']]));
            $cards[] = $this->card('Afgewezen', Fmt::int($summary['rejected']), null, 'gray', $list(['status' => ['value' => 'rejected']]));
            $cards[] = $this->card('Tweede formulier ingevuld', Fmt::int($summary['info_done']), Fmt::pct($summary['info_rate']).' van de goedgekeurde bedrijven', 'info', $list(['status' => ['value' => 'approved'], 'info_status' => ['value' => 'submitted']]));
            $cards[] = $this->card('Tweede formulier ontbreekt', Fmt::int($summary['info_missing']), null, $summary['info_missing'] > 0 ? 'warning' : 'gray', $list(['status' => ['value' => 'approved'], 'info_status' => ['value' => 'awaiting']]));
        }

        if (! $event) {
            return $cards;
        }

        $class = $this->participation->classify($event->id);
        $per = $this->planning->perEdition();
        $prev = $scope->compare ? ($per[$scope->compare->id] ?? null) : null;
        $now = $per[$event->id] ?? [];
        $approved = $class['participants'];

        $cards[] = $this->card('Nieuwe bedrijven', Fmt::int(count($class['new'])), $approved > 0 ? Fmt::pct(count($class['new']) / $approved * 100).' van de deelnemers' : null, 'info', null, 'Bedrijven die voor het eerst deelnemen.');
        $cards[] = $this->card('Terugkerende bedrijven', Fmt::int(count($class['returning'])), $approved > 0 ? Fmt::pct(count($class['returning']) / $approved * 100).' van de deelnemers' : null, 'success', null, 'Bedrijven die eerder aan een editie hebben deelgenomen.');
        $cards[] = $this->card('Partners', Fmt::int($now['partners'] ?? 0), $prev ? (Fmt::versus($now['partners'] ?? 0, $prev['partners'], $scope->compare->name)['text'] ?? null) : null, 'gray', null, 'Unieke partners met een stand in deze editie.');

        if ($user->can('event-stands.view')) {
            $stands = $this->planning->stands($event->id);
            $cards[] = $this->card('Beschikbare stands', Fmt::int($stands['company_total']), 'Bedrijfsstands', 'gray');
            $cards[] = $this->card('Bezette stands', Fmt::int($stands['company_occupied']), $prev ? (Fmt::versus($stands['company_occupied'], $prev['stands_occupied'], $scope->compare->name)['text'] ?? null) : null, 'primary');
            $cards[] = $this->card('Standbezetting', Fmt::pct($stands['occupancy']), null, ($stands['occupancy'] ?? 0) >= 90 ? 'success' : 'gray');
            $cards[] = $this->card('Goedgekeurd zonder stand', $stands['approved_without_stand'] === null ? Fmt::DASH : Fmt::int(count($stands['approved_without_stand'])), $stands['approved_without_stand'] === null ? 'Geen inschrijvingen in deze editie' : null, ($stands['approved_without_stand'] ?? []) !== [] ? 'warning' : 'gray', \App\Filament\Resources\EventStands\EventStandResource::getUrl('index'));
        }

        if ($user->can(self::FINANCIAL_PERMISSION)) {
            $cards[] = $this->card('Verwachte deelnamekosten', $this->revenueText($event->id), 'Geaccepteerde deelnamekosten, geen ontvangen betaling', 'success', null, 'Som van de bij inschrijving geaccepteerde bedragen van goedgekeurde bedrijven.');
        }

        if ($user->can('enrollments.view')) {
            $catering = $this->catering->forEvent($event->id);
            $cards[] = $this->card('Lunchdeelnemers', $catering['available'] ? Fmt::int($catering['lunch']['participants']) : Fmt::DASH, $catering['available'] ? null : 'Niet beschikbaar', 'gray');
            $cards[] = $this->card('Verwachte borrelgasten', $catering['available'] ? Fmt::int($catering['drinks']['people']) : Fmt::DASH, $catering['available'] ? $catering['drinks']['yes'].' bedrijven komen' : 'Niet beschikbaar', 'gray');
        }

        return $cards;
    }

    /** @return list<array<string, mixed>> */
    private function actions(Scope $scope, User $user): array
    {
        return array_map(fn (array $item): array => $this->card($item['label'], Fmt::int($item['count']), $item['group'], $item['color'], $item['url'], $item['tooltip']), $this->admin->items($scope, $user));
    }

    /** @return list<array<string, mixed>> */
    private function enrollmentCards(Scope $scope, User $user): array
    {
        if (! $user->can('enrollments.view')) {
            return [];
        }

        $s = $this->enrollments->summary($scope->event?->id);
        $list = fn (array $f = []) => EnrollmentResource::getUrl('index', ['filters' => array_filter(['event_id' => ['value' => $scope->event?->id], ...$f], fn ($x) => $x !== ['value' => null])]);

        return [
            $this->card('Inschrijvingen totaal', Fmt::int($s['total']), null, 'gray', $list()),
            $this->card('Goedgekeurd', Fmt::int($s['approved']), null, 'success', $list(['status' => ['value' => 'approved']])),
            $this->card('In behandeling', Fmt::int($s['pending']), null, 'warning', $list(['status' => ['value' => 'pending']])),
            $this->card('Afgewezen', Fmt::int($s['rejected']), null, 'danger', $list(['status' => ['value' => 'rejected']])),
            $this->card('Goedkeuringspercentage', Fmt::pct($s['approval_rate']), 'Van de beoordeelde inschrijvingen', 'success', null, 'Goedgekeurd / (goedgekeurd + afgewezen). In behandeling telt niet mee.'),
            $this->card('Afwijzingspercentage', Fmt::pct($s['rejection_rate']), 'Van de beoordeelde inschrijvingen', 'danger', null, 'Afgewezen / (goedgekeurd + afgewezen).'),
            $this->card('Gemiddelde beoordelingstijd', Fmt::duration($s['avg_review_seconds']), 'Van indienen tot beoordeling', 'gray'),
            $this->card('Oudste openstaande inschrijving', Fmt::date($s['oldest_pending']), $s['oldest_pending'] ? \Illuminate\Support\Carbon::parse($s['oldest_pending'])->diffForHumans() : 'Niets in behandeling', 'warning'),
            $this->card('Ontvangen deze week', Fmt::int($s['this_week']), null, 'info'),
            $this->card('Ontvangen deze maand', Fmt::int($s['this_month']), null, 'info'),
            $this->card('Goedgekeurd, tweede formulier ontbreekt', Fmt::int($s['info_missing']), null, 'warning', $list(['status' => ['value' => 'approved'], 'info_status' => ['value' => 'awaiting']])),
            $this->card('Tweede formulier ingevuld', Fmt::pct($s['info_rate']), Fmt::int($s['info_done']).' van '.Fmt::int($s['approved']).' goedgekeurd', 'success'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function breakdown(Scope $scope, User $user): array
    {
        if (! $scope->event || ! $user->can('enrollments.view')) {
            return [];
        }

        $c = $this->participation->classify($scope->event->id);
        $s = $this->enrollments->summary($scope->event->id);

        return [
            $this->card('Nieuwe bedrijven', Fmt::int(count($c['new'])), 'Eerste deelname ooit', 'info'),
            $this->card('Terugkerende bedrijven', Fmt::int(count($c['returning'])), 'Eerder deelgenomen', 'success'),
            $this->card('Direct opvolgend (behouden)', Fmt::int(count($c['retained'])), 'Ook in de vorige editie', 'success'),
            $this->card('Na gemiste editie terug', Fmt::int(count($c['reactivated'])), 'Eerder, maar niet in de vorige editie', 'warning'),
            $this->card('Bestaand bedrijfsrecord', Fmt::int($s['new_records'] === null ? null : $s['approved'] - $s['new_records']), 'Bedrijf stond al in de database bij inschrijving', 'gray', null, 'Een bestaand record zegt niets over eerdere deelname.'),
            $this->card('Nieuw aangemaakt record', Fmt::int($s['new_records']), 'Aangemaakt bij goedkeuring', 'gray'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function retention(Scope $scope, User $user): array
    {
        if (! $user->can('companies.view')) {
            return [];
        }

        $h = $this->participation->history();
        $cards = [];

        if ($scope->event) {
            $c = $this->participation->classify($scope->event->id);
            $note = $c['provisional'] ? 'Voorlopig: deelnemerslijst is nog niet definitief' : null;
            $cards = [
                $this->card('Eerste keer deelnemend', Fmt::int(count($c['new'])), null, 'info'),
                $this->card('Terugkerend', Fmt::int(count($c['returning'])), null, 'success', null, 'Heeft eerder deelgenomen, ook na een gemiste editie.'),
                $this->card('Behouden', Fmt::int(count($c['retained'])), 'Vorige én deze editie', 'success'),
                $this->card('Gereactiveerd', Fmt::int(count($c['reactivated'])), 'Eerder, niet in de vorige editie', 'warning'),
                $this->card('Verloren', Fmt::int(count($c['lost'])), $note ?? 'Vorige editie, niet in deze', 'danger'),
                $this->card('Retentiepercentage', Fmt::pct($c['retention']), $note ?? 'Behouden / deelnemers vorige editie', 'success', null, 'Bedrijven in vorige én geselecteerde editie / bedrijven in de vorige editie × 100%.'),
                $this->card('Verlooppercentage (churn)', Fmt::pct($c['churn']), $note ?? 'Verloren / deelnemers vorige editie', 'danger', null, 'Bedrijven uit de vorige editie die ontbreken in de geselecteerde editie / bedrijven in de vorige editie × 100%.'),
                $this->card('Terugkeerpercentage', Fmt::pct($c['return_rate']), 'Terugkerend / deelnemers deze editie', 'info'),
            ];
        }

        return [...$cards,
            $this->card('Minstens 2 edities', Fmt::int($h['at_least_2']), null, 'gray'),
            $this->card('Minstens 3 edities', Fmt::int($h['at_least_3']), null, 'gray'),
            $this->card('Minstens 5 edities', Fmt::int($h['at_least_5']), null, 'gray'),
            $this->card('Gemiddeld aantal edities', Fmt::dec($h['average'], 2), 'Per bedrijf', 'gray'),
            $this->card('Langste aaneengesloten reeks', Fmt::int($h['longest_streak']).' edities', null, 'gray'),
            $this->card('Bedrijven ooit deelgenomen', Fmt::int($h['ever']), 'Alle edities', 'primary'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function stands(Scope $scope, User $user): array
    {
        if (! $scope->event || ! $user->can('event-stands.view')) {
            return [];
        }

        $s = $this->planning->stands($scope->event->id);
        $url = \App\Filament\Resources\EventStands\EventStandResource::getUrl('index');
        $without = $s['approved_without_stand'];

        return [
            $this->card('Bedrijfsstands beschikbaar', Fmt::int($s['company_total']), null, 'gray'),
            $this->card('Bezet', Fmt::int($s['company_occupied']), null, 'primary', $url),
            $this->card('Vrij', Fmt::int($s['company_free']), null, 'gray'),
            $this->card('Bezettingsgraad', Fmt::pct($s['occupancy']), null, 'success'),
            $this->card('Goedgekeurd zonder stand', $without === null ? Fmt::DASH : Fmt::int(count($without)), $without === null ? 'Geen inschrijvingen in deze editie' : null, ($without ?? []) !== [] ? 'warning' : 'gray', $url),
            $this->card('Bedrijven met stand', Fmt::int($s['allocated_companies']), null, 'info'),
            $this->card('Partnerstands', Fmt::int($s['partner_total']), 'Los van de bedrijfsstands', 'gray'),
            $this->card('Partnerstands bezet', Fmt::int($s['partner_occupied']), null, 'primary'),
            $this->card('Partnerstands vrij', Fmt::int($s['partner_free']), null, 'gray'),
            $this->card('Standwaarschuwingen', Fmt::int($s['warnings']), 'Stand zonder goedgekeurde inschrijving', $s['warnings'] > 0 ? 'danger' : 'gray', $url),
            $this->card('Historische toewijzingen zonder inschrijving', Fmt::int(count($s['grandfathered'])), 'Blijven staan, tellen niet als deelname', 'gray', null, 'Stands uit de tijd vóór inschrijvingen. Ze worden niet gewijzigd of als goedgekeurde deelname geteld.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function finance(Scope $scope, User $user): array
    {
        if (! $scope->event || ! $user->can(self::FINANCIAL_PERMISSION)) {
            return [];
        }

        $revenue = $this->planning->revenue();
        $money = $revenue[$scope->event->id]['currencies'] ?? null;
        $previous = $scope->compare ? ($revenue[$scope->compare->id]['currencies'] ?? null) : null;

        if ($money === null) {
            return [$this->card('Geen financiële gegevens', Fmt::DASH, 'Voor deze editie zijn geen inschrijvingen met deelnamekosten vastgelegd.')];
        }

        $sum = fn (array $set, string $key): array => collect($set)->map(fn (array $c): int => $c[$key])->all();
        $avg = collect($money)->map(fn (array $c): int => $c['count'] > 0 ? (int) round($c['approved'] / $c['count']) : 0)->all();
        $growth = null;

        if ($previous && count($money) === 1 && count($previous) === 1 && array_key_first($money) === array_key_first($previous)) {
            $growth = Fmt::versus($money[array_key_first($money)]['approved'], $previous[array_key_first($previous)]['approved'], $scope->compare->name);
        }

        return [
            $this->card('Verwachte deelnamekosten', Fmt::moneyPerCurrency($sum($money, 'approved')), 'Geaccepteerd, niet ontvangen', 'success', null, 'Geaccepteerde deelnamekosten van goedgekeurde bedrijven. Er is geen factuur- of betaalstatus beschikbaar.'),
            $this->card('Gemiddeld geaccepteerd bedrag', Fmt::moneyPerCurrency($avg), 'Per goedgekeurd bedrijf', 'gray'),
            $this->card('Verwacht van nieuwe bedrijven', Fmt::moneyPerCurrency($sum($money, 'new')), null, 'info'),
            $this->card('Verwacht van terugkerende bedrijven', Fmt::moneyPerCurrency($sum($money, 'returning')), null, 'success'),
            $this->card('Potentiële waarde openstaande inschrijvingen', Fmt::moneyPerCurrency($sum($money, 'pending')), 'Nog niet goedgekeurd', 'warning'),
            $this->card('Uitgesloten door afwijzingen', Fmt::moneyPerCurrency($sum($money, 'rejected')), 'Afgewezen inschrijvingen', 'gray'),
            $this->card('Groei t.o.v. vorige editie', $growth['text'] ?? Fmt::DASH, $growth ? null : 'Niet vergelijkbaar (geen vorige editie, geen gegevens of andere valuta)', $growth['color'] ?? 'gray'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function catering(Scope $scope, User $user): array
    {
        if (! $scope->event || ! $user->can('enrollments.view')) {
            return [];
        }

        $c = $this->catering->forEvent($scope->event->id);

        if (! $c['available']) {
            return [$this->card('Niet beschikbaar', Fmt::DASH, 'Voor deze editie zijn geen tweede formulieren (meer) opgeslagen, bijvoorbeeld door de bewaartermijn.', 'gray')];
        }

        $l = $c['lunch'];
        $d = $c['drinks'];
        $answered = $d['yes'] + $d['no'] + $d['unsure'];
        $diet = fn (string $key) => $l['diet_known'] > 0 ? Fmt::int($l['diet'][$key]) : Fmt::DASH;

        return [
            $this->card('Lunchdeelnemers', Fmt::int($l['participants']), 'Totaal, laatste versie per bedrijf', 'primary'),
            $this->card('Bedrijven met lunch', Fmt::int($l['companies']), null, 'info'),
            $this->card('Gemiddeld per bedrijf', Fmt::dec($l['companies'] > 0 ? $l['participants'] / $l['companies'] : null), null, 'gray'),
            $this->card('Lunchgegevens ontbreken', Fmt::int($l['missing']), 'Goedgekeurd zonder tweede formulier', $l['missing'] > 0 ? 'warning' : 'gray'),
            $this->card('Vegetarisch', $diet('vegetarian'), 'Selecties kunnen overlappen', 'gray', null, 'Een deelnemer kan meerdere voorkeuren kiezen; de categorieën zijn geen losse maaltijdaantallen.'),
            $this->card('Veganistisch', $diet('vegan'), null, 'gray'),
            $this->card('Halal', $diet('halal'), null, 'gray'),
            $this->card('Glutenvrij', $diet('gluten_free'), null, 'gray'),
            $this->card('Lactosevrij', $diet('lactose_free'), null, 'gray'),
            $this->card('Overige wensen', $diet('other'), null, 'gray'),
            $this->card('Zonder dieetwensen', $l['diet_known'] > 0 ? Fmt::int($l['none']) : Fmt::DASH, $l['diet_known'] > 0 ? null : 'Dieetgegevens zijn niet meer beschikbaar', 'gray'),
            $this->card('Borrel: komen', Fmt::int($d['yes']), null, 'success'),
            $this->card('Borrel: komen niet', Fmt::int($d['no']), null, 'gray'),
            $this->card('Borrel: nog onzeker', Fmt::int($d['unsure']), null, 'warning'),
            $this->card('Borrel: geen antwoord', Fmt::int($d['missing']), null, $d['missing'] > 0 ? 'warning' : 'gray'),
            $this->card('Verwachte borrelgasten', Fmt::int($d['people']), $d['people_missing'] > 0 ? $d['people_missing'].' bedrijf/bedrijven zonder aantal' : null, 'primary'),
            $this->card('Gemiddeld per bedrijf', Fmt::dec($d['yes'] > 0 ? $d['people'] / $d['yes'] : null), 'Per bedrijf dat komt', 'gray'),
            $this->card('Borrel-deelname', Fmt::pct($c['approved'] > 0 ? $d['yes'] / $c['approved'] * 100 : null), 'Van alle goedgekeurde bedrijven', 'info', null, 'Bedrijven die komen / goedgekeurde bedrijven. Bedrijven zonder antwoord tellen mee in de noemer.'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function promo(Scope $scope, User $user): array
    {
        if (! $scope->event || ! $user->can('enrollments.view')) {
            return [];
        }

        $c = $this->catering->forEvent($scope->event->id);

        if (! $c['available']) {
            return [$this->card('Niet beschikbaar', Fmt::DASH, 'Voor deze editie zijn geen tweede formulieren (meer) opgeslagen.', 'gray')];
        }

        $p = $c['promo'];

        return [
            $this->card('Klein promotiemateriaal', Fmt::int($p['small']), 'Bedrijven', 'gray'),
            $this->card('Middelgroot promotiemateriaal', Fmt::int($p['medium']), 'Bedrijven', 'gray'),
            $this->card('Groot promotiemateriaal', Fmt::int($p['large']), 'Bedrijven', 'warning'),
            $this->card('Meerdere categorieën', Fmt::int($p['multiple']), 'Bedrijven', 'gray'),
            $this->card('Schermen', Fmt::int($p['screens']), 'Bedrijven', 'info'),
            $this->card('Prototypes', Fmt::int($p['prototypes']), 'Bedrijven', 'info'),
            $this->card('Extra ruimte gevraagd', Fmt::DASH, 'Het formulier bevat hier geen vraag over', 'gray'),
            $this->card('Gegevens ontbreken', Fmt::int($p['missing']), 'Geen antwoord over promotiemateriaal', $p['missing'] > 0 ? 'warning' : 'gray'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function taxonomy(Scope $scope, User $user): array
    {
        if (! $scope->event) {
            return [];
        }

        $cards = [];

        foreach (['sector' => ['sectors.view', 'Sector'], 'education' => ['education.view', 'Opleiding']] as $kind => [$permission, $label]) {
            if (! $user->can($permission)) {
                continue;
            }

            $rows = collect($this->taxonomy->distribution($kind, $scope->event->id));
            $present = $rows->where('total', '>', 0);
            $top = $rows->sortByDesc('total')->first();
            $low = $present->sortBy('total')->first();

            $cards[] = $this->card("{$label}en met deelnemers", Fmt::int($present->count()), 'Van '.Fmt::int($rows->count()).' beschikbaar', 'info');
            $cards[] = $this->card("Meest vertegenwoordigd ({$label})", $top && $top['total'] > 0 ? $top['name'] : Fmt::DASH, $top && $top['total'] > 0 ? $top['total'].' bedrijven' : null, 'success');
            $cards[] = $this->card("Minst vertegenwoordigd ({$label})", $low['name'] ?? Fmt::DASH, $low ? $low['total'].' bedrijven' : 'Alleen categorieën met deelnemers', 'gray');
        }

        if ($user->can('education.view')) {
            $reach = $this->taxonomy->educationReach($scope->event->id);
            $cards[] = $this->card('Bedrijven met meerdere opleidingen', Fmt::int($reach['multiple']), 'Van '.Fmt::int($reach['companies']).' deelnemers', 'primary', null, 'Een bedrijf kan meerdere opleidingen of sectoren hebben; de aantallen per categorie tellen daarom op tot meer dan het aantal bedrijven.');
        }

        return $cards;
    }

    private function revenueText(int $eventId): string
    {
        $money = $this->planning->revenue()[$eventId]['currencies'] ?? null;

        return $money === null ? Fmt::DASH : Fmt::moneyPerCurrency(collect($money)->map(fn (array $c): int => $c['approved'])->all());
    }

    /** @return array{label: string, value: string, description: ?string, color: string, url: ?string, tooltip: ?string} */
    private function card(string $label, string $value, ?string $description = null, string $color = 'gray', ?string $url = null, ?string $tooltip = null): array
    {
        return compact('label', 'value', 'description', 'color', 'url', 'tooltip');
    }
}
