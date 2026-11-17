<?php

namespace App\Models;

use App\Models\Builders\EventStandBuilder;
use App\Models\Concerns\Audited;
use App\Services\StandEligibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class EventStand extends Model
{
    use Audited;

    protected $table = 'event_stands';

    protected $fillable = [
        'event_id',
        'company_id',
        'partner_id',
        'type',
        'stand_number',
        'x_percent',
        'y_percent',
    ];

    protected $casts = [
        'x_percent' => 'float',
        'y_percent' => 'float',
        'participation_confirmed_at' => 'datetime',
    ];

    /** One-shot carrier of a Super Admin historical override; only assignWithHistoricalOverride() sets it. */
    public ?string $historicalOverrideReason = null;

    public bool $overrideUsed = false;

    /**
     * Places a company on this stand, bypassing the enrollment requirement. Allowed only for a Super Admin on a
     * historical edition (checked again in StandEligibility); the override lives for this one save and is logged.
     */
    public function assignWithHistoricalOverride(int $companyId, string $reason, ?string $previousStandNumber = null): void
    {
        DB::transaction(function () use ($companyId, $reason, $previousStandNumber): void {
            $previousCompany = $this->company_id;
            $this->historicalOverrideReason = $reason;
            $this->overrideUsed = false;

            try {
                $this->fill(['company_id' => $companyId, 'partner_id' => null, 'type' => 'company'])->save();
            } finally {
                $this->historicalOverrideReason = null;
            }

            if (! $this->overrideUsed) {
                return;
            }

            activity('event-stands')
                ->causedBy(auth()->user())
                ->performedOn($this)
                ->withProperties([
                    'enrollment_requirement_overridden' => true,
                    'event_id' => $this->event_id,
                    'company_id' => $companyId,
                    'previous_company_id' => $previousCompany,
                    'previous_stand_of_company' => $previousStandNumber,
                    'new_stand' => $this->stand_number,
                    'reason' => $reason,
                ])
                ->log('Historical stand assignment: enrollment requirement overridden');
        });
    }

    protected static function booted(): void
    {
        static::saving(function (self $eventStand): void {
            // A new placement is not confirmed participation unless the override sets it again below.
            if ($eventStand->isDirty(['company_id', 'event_id', 'type'])) {
                $eventStand->participation_confirmed_at = null;
            }

            $eventStand->stand_number = (string) ((int) preg_replace('/\D+/', '', (string) $eventStand->stand_number));

            if ($eventStand->type === 'partner' || $eventStand->partner_id) {
                $eventStand->company_id = null;
                $eventStand->type = 'partner';

                return;
            }

            if ($eventStand->type === 'company' || $eventStand->company_id) {
                $eventStand->partner_id = null;
                $eventStand->type = 'company';
            }

            // Only a change of company or edition is checked, so layout edits on grandfathered stands are never blocked.
            app(StandEligibility::class)->assertCanAssign($eventStand);
        });
    }

    /** Marks the model's own UPDATE ... WHERE id query so the bulk guard does not re-route it through the model again. */
    protected function setKeysForSaveQuery($query)
    {
        $query = parent::setKeysForSaveQuery($query);

        return $query instanceof EventStandBuilder ? $query->forModelSave() : $query;
    }

    public function newEloquentBuilder($query): EventStandBuilder
    {
        return new EventStandBuilder($query);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function isPartnerStand(): bool
    {
        return $this->type === 'partner';
    }

    public function isCompanyStand(): bool
    {
        return $this->type === 'company';
    }
}
