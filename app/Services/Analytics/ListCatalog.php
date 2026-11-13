<?php

namespace App\Services\Analytics;

use App\Models\Company;
use App\Models\Event;
use App\Models\EventStand;
use App\Models\User;
use App\Services\StandEligibility;

/** The rows of the dashboard's lists, by key. Only company names and stand numbers leave here, never contact or dietary data. */
class ListCatalog
{
    /** key => [permission, heading] */
    public const LISTS = [
        'participation' => ['companies.view', 'Deelname per bedrijf'],
        'approved_no_stand' => ['event-stands.view', 'Goedgekeurde bedrijven zonder stand'],
        'flagged_stands' => ['event-stands.view', 'Stands met een waarschuwing'],
        'free_partner_stands' => ['event-stands.view', 'Vrije partnerstands'],
        'multi_stand' => ['event-stands.view', 'Bedrijven met meerdere stands'],
        'grandfathered' => ['event-stands.view', 'Historische toewijzingen zonder goedgekeurde inschrijving'],
        'promo_special' => ['enrollments.view', 'Bedrijven met groot of speciaal materiaal'],
    ];

    public function __construct(private Participation $participation, private EventPlanningStats $planning, private CateringStats $catering) {}

    public static function permission(string $key): ?string
    {
        return self::LISTS[$key][0] ?? null;
    }

    public static function heading(string $key): string
    {
        return self::LISTS[$key][1] ?? '';
    }

    /** @return list<array<string, mixed>> */
    public function rows(string $key, Scope $scope, User $user): array
    {
        $permission = self::permission($key);

        if ($permission === null || ! $user->can($permission) || ! $scope->event) {
            return [];
        }

        $event = $scope->event;

        return match ($key) {
            'participation' => $this->participationRows($event),
            'approved_no_stand' => $this->companies($this->planning->stands($event->id)['approved_without_stand'] ?? []),
            'grandfathered' => $this->companies($this->planning->stands($event->id)['grandfathered']),
            'multi_stand' => $this->multiStand($event),
            'flagged_stands' => $this->flagged($event),
            'free_partner_stands' => array_map(fn (string $n): array => ['id' => $n, 'stand' => "P{$n}"], $this->planning->stands($event->id)['free_partner_numbers']),
            'promo_special' => array_map(fn (array $row): array => ['id' => $row['company_id'], 'company_id' => $row['company_id'], 'name' => $row['name'], 'items' => implode(', ', $row['items'])], $this->catering->forEvent($event->id)['promo']['special']),
            default => [],
        };
    }

    /** @return list<array<string, mixed>> */
    private function participationRows(Event $event): array
    {
        $editions = array_column($this->participation->editions(), 'name');

        return array_map(function (array $row) use ($editions): array {
            $symbols = implode('', array_map(fn (bool $in): string => $in ? '●' : '○', $row['timeline']));
            $detail = implode("\n", array_map(fn (bool $in, string $name): string => "{$name}: ".($in ? 'ja' : 'nee'), $row['timeline'], array_slice($editions, 0, count($row['timeline']))));

            return [...$row, 'company_id' => $row['id'], 'symbols' => $symbols, 'detail' => $detail, 'previous_text' => $row['previous'] ? 'Ja' : 'Nee', 'selected_text' => $row['selected'] ? 'Ja' : 'Nee'];
        }, $this->participation->companyRows($event->id));
    }

    /** @param list<int> $ids @return list<array<string, mixed>> */
    private function companies(array $ids): array
    {
        return Company::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])->map(fn (Company $c): array => ['id' => $c->id, 'company_id' => $c->id, 'name' => $c->name])->all();
    }

    /** @return list<array<string, mixed>> */
    private function multiStand(Event $event): array
    {
        $multiple = $this->planning->stands($event->id)['multiple'];
        $stands = EventStand::query()->where('event_id', $event->id)->whereIn('company_id', array_keys($multiple))->orderByRaw('CAST(stand_number AS UNSIGNED)')->get(['company_id', 'stand_number'])->groupBy('company_id');

        return Company::query()->whereIn('id', array_keys($multiple))->orderBy('name')->get(['id', 'name'])
            ->map(fn (Company $c): array => ['id' => $c->id, 'company_id' => $c->id, 'name' => $c->name, 'stands' => $stands[$c->id]->pluck('stand_number')->implode(', ')])->all();
    }

    /** @return list<array<string, mixed>> */
    private function flagged(Event $event): array
    {
        $eligibility = app(StandEligibility::class);

        return $eligibility->flaggedStands($event)->map(fn (EventStand $s): array => [
            'id' => $s->id, 'company_id' => $s->company_id, 'name' => $s->company?->name ?? '?', 'stand' => (string) (int) $s->stand_number, 'reason' => $eligibility->explain($s)['short'],
        ])->all();
    }
}
