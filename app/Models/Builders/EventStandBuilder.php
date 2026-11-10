<?php

namespace App\Models\Builders;

use App\Models\EventStand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Query-builder writes skip model events, which would let a company onto a stand without the eligibility check.
 * Updates that change who is on a stand are therefore run per model; inserts that assign a company are refused.
 *
 * @extends Builder<EventStand>
 */
class EventStandBuilder extends Builder
{
    private const ASSIGNMENT_COLUMNS = ['company_id', 'event_id', 'type'];

    /** Set by EventStand when this query is the model's own save: it was already checked by the saving event. */
    private bool $forModelSave = false;

    public function forModelSave(): static
    {
        $this->forModelSave = true;

        return $this;
    }

    public function update(array $values)
    {
        if ($this->forModelSave || array_intersect(array_keys($values), self::ASSIGNMENT_COLUMNS) === []
            || (array_key_exists('company_id', $values) && $values['company_id'] === null && ! array_key_exists('event_id', $values) && ! array_key_exists('type', $values))) {
            return parent::update($values); // layout, markers, or plain unassigning: nothing to check
        }

        return DB::transaction(fn (): int => $this->get()->each(fn (EventStand $stand) => $stand->update($values))->count());
    }

    public function insert(array $values)
    {
        $this->refuseAssignments($values);

        return parent::insert($values);
    }

    public function upsert(array $values, $uniqueBy, $update = null)
    {
        $this->refuseAssignments($values);

        if ($update !== null && array_intersect((array) $update, self::ASSIGNMENT_COLUMNS) !== []) {
            throw new LogicException('Upserting assignment columns on event_stands bypasses the eligibility check; save the models instead.');
        }

        return parent::upsert($values, $uniqueBy, $update);
    }

    /** Rows are accepted only when they leave the stand without a company (e.g. creating empty stand rows). */
    private function refuseAssignments(array $values): void
    {
        $rows = array_is_list($values) ? $values : [$values];

        foreach ($rows as $row) {
            if (! empty($row['company_id'])) {
                throw new LogicException('Inserting a company onto event_stands in bulk bypasses the eligibility check; use EventStand::create().');
            }
        }
    }
}
