<?php

namespace App\Services\Analytics;

use App\Models\Education;
use App\Models\Sector;
use Illuminate\Support\Facades\DB;

/**
 * Sector and education representation of the participating companies of an edition.
 *
 * Companies carry their CURRENT sectors and educations; editions are not snapshotted, so a past edition is shown with
 * today's profile of the companies that took part. A company can have several of each, so category counts add up to
 * more than the number of companies.
 */
class TaxonomyStats
{
    public function __construct(private Analytics $analytics, private Participation $participation) {}

    /**
     * Per category: participants, new and returning companies for one edition.
     *
     * @param  'sector'|'education'  $kind
     * @return list<array{id: int, name: string, total: int, new: int, returning: int}>
     */
    public function distribution(string $kind, int $eventId): array
    {
        return $this->analytics->remember("taxonomy:{$kind}:{$eventId}", function () use ($kind, $eventId): array {
            $links = $this->links($kind);
            $class = $this->participation->classify($eventId);
            $new = array_flip($class['new']);
            $participants = array_flip($this->participation->sets()[$eventId] ?? []);
            $rows = [];

            foreach ($this->categories($kind) as $id => $name) {
                $members = array_intersect_key($links[$id] ?? [], $participants);
                $rows[] = ['id' => $id, 'name' => $name, 'total' => count($members), 'new' => count(array_intersect_key($members, $new)), 'returning' => count($members) - count(array_intersect_key($members, $new))];
            }

            return $rows;
        });
    }

    /**
     * Participants per category for every edition (stacked and trend charts, growth).
     *
     * @param  'sector'|'education'  $kind
     * @return array<int, array<int, int>> event id => category id => companies
     */
    public function perEdition(string $kind): array
    {
        return $this->analytics->remember("taxonomy-all:{$kind}", function () use ($kind): array {
            $links = $this->links($kind);
            $out = [];

            foreach ($this->participation->editions() as $edition) {
                $participants = array_flip($this->participation->sets()[$edition['id']] ?? []);
                $out[$edition['id']] = array_map(fn (array $members): int => count(array_intersect_key($members, $participants)), $links);
            }

            return $out;
        });
    }

    /**
     * Participants of an edition by the number of educations they target.
     *
     * @return array{multiple: int, none: int, companies: int}
     */
    public function educationReach(int $eventId): array
    {
        return $this->analytics->remember("education-reach:{$eventId}", function () use ($eventId): array {
            $ids = $this->participation->sets()[$eventId] ?? [];
            $perCompany = DB::table('company_education')->whereIn('company_id', $ids)->select('company_id', DB::raw('count(*) as n'))->groupBy('company_id')->pluck('n', 'company_id');

            return ['multiple' => $perCompany->filter(fn ($n): bool => $n > 1)->count(), 'none' => count($ids) - $perCompany->count(), 'companies' => count($ids)];
        });
    }

    /** @param 'sector'|'education' $kind @return array<int, string> */
    public function categories(string $kind): array
    {
        return $kind === 'sector' ? Sector::query()->orderBy('id')->pluck('name', 'id')->all() : Education::query()->orderBy('id')->pluck('name', 'id')->all();
    }

    /** @param 'sector'|'education' $kind @return array<int, array<int, true>> category id => company ids */
    private function links(string $kind): array
    {
        [$table, $column] = $kind === 'sector' ? ['company_sector', 'sector_id'] : ['company_education', 'education_id'];
        $links = array_fill_keys(array_keys($this->categories($kind)), []);

        foreach (DB::table($table)->get(['company_id', $column]) as $row) {
            $links[(int) $row->{$column}][(int) $row->company_id] = true;
        }

        return $links;
    }
}
