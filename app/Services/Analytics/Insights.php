<?php

namespace App\Services\Analytics;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\EventStands\EventStandResource;
use App\Models\User;

/**
 * Short, deterministic sentences derived from figures that already exist. A sentence is only produced when its
 * numbers exist; nothing is inferred about causes or the future.
 */
class Insights
{
    private const ORDINALS = [3 => 'derde', 4 => 'vierde', 5 => 'vijfde', 6 => 'zesde', 7 => 'zevende', 8 => 'achtste'];

    public function __construct(private Participation $participation, private EnrollmentStats $enrollments, private EventPlanningStats $planning) {}

    /** @return list<array{text: string, url: ?string}> */
    public function for(Scope $scope, User $user): array
    {
        $event = $scope->event;

        if (! $event) {
            return [];
        }

        $items = [];
        $history = Dashboard::getUrl(['sectie' => 'bedrijven', 'onderdeel' => 'deelnamegeschiedenis']);

        if ($user->can('enrollments.view') && $scope->compare) {
            $now = $this->enrollments->summary($event->id)['approved'];
            $then = $this->enrollments->summary($scope->compare->id)['approved'];
            $change = Fmt::change($now, $then);

            if ($change['percent'] !== null && $change['absolute'] != 0) {
                $items[] = ['text' => 'Het aantal goedgekeurde bedrijven is '.Fmt::dec(abs($change['percent'])).'% '.($change['absolute'] > 0 ? 'hoger' : 'lager')." dan bij {$scope->compare->name}.", 'url' => null];
            }
        }

        if ($user->can('companies.view')) {
            $class = $this->participation->classify($event->id);

            if (count($class['returning']) > 0) {
                $items[] = ['text' => count($class['returning']).' bedrijven hebben eerder deelgenomen.', 'url' => $history];
            }

            $streaks = collect($this->participation->companyRows($event->id))->where('selected', true)->countBy('streak')->filter(fn (int $n, int $streak): bool => $streak >= 3)->sortKeysDesc();

            if ($streaks->isNotEmpty()) {
                $streak = $streaks->keys()->first();
                $items[] = ['text' => $streaks->first().' bedrijven nemen voor de '.(self::ORDINALS[$streak] ?? "{$streak}e").' achtereenvolgende editie deel.', 'url' => $history];
            }

            if ($class['retention'] !== null && ! $class['provisional']) {
                $items[] = ['text' => Fmt::pct($class['retention']).' van de bedrijven van de vorige editie is teruggekeerd; '.count($class['lost']).' deden niet meer mee.', 'url' => $history];
            }
        }

        if ($user->can('event-stands.view')) {
            $without = count($this->planning->stands($event->id)['approved_without_stand'] ?? []);

            if ($without > 0) {
                $items[] = ['text' => "{$without} goedgekeurde bedrijven hebben nog geen stand.", 'url' => EventStandResource::getUrl('index')];
            }
        }

        return $items;
    }
}
