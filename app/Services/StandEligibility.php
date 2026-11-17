<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventStand;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The single place that decides whether a company may be NEWLY placed on a stand.
 *
 * - Eligible = an approved enrollment for the edition the stand actually belongs to, and nothing else. Approval of the
 *   FIRST (enrollment) form is the only input: whether the second information form was completed, the profile check,
 *   dietary answers, contacts or the state of the invitation never change eligibility, so a company can be given a stand
 *   the moment it is approved and keeps it whatever happens to the second form.
 * - Partners are a separate model with their own stands and are never checked here.
 * - Only a Super Admin can place a non-approved company on a stand of any edition, through EventStand::assignWithEnrollmentOverride() with a reason; it is logged.
 * - Grandfathering needs no flag: a check only runs when a write changes WHO is on a stand or WHICH edition it is in.
 *   An existing assignment keeps working (layout, markers), but re-placing it, moving it to another stand or edition,
 *   or re-adding it after removal is a new assignment and must pass.
 */
class StandEligibility
{
    /** Strictly before today in Amsterdam; the date is a plain calendar date, so compare dates, not instants. */
    public function isHistorical(Event $event): bool
    {
        return $event->date !== null && $event->date->toDateString() < now('Europe/Amsterdam')->toDateString();
    }

    /** The only authorization for the override: a real Super Admin, for any edition. */
    public function canOverride(?User $user): bool
    {
        return $user !== null && $user->isSuperAdmin();
    }

    public function isEligible(Event $event, Company|int $company): bool
    {
        return $event->approvedCompanies()->whereKey($company instanceof Company ? $company->getKey() : $company)->exists();
    }

    /** @return Builder<Company> companies that may be newly assigned a stand in this edition */
    public function eligibleCompanies(Event $event): Builder
    {
        return Company::query()->whereIn('companies.id', $event->approvedCompanies()->select('companies.id'));
    }

    /** What the stand dropdowns may offer: approved companies, or every company when the user may and does override. */
    public function assignableCompanies(Event $event, bool $override = false): Builder
    {
        return $override && $this->canOverride(auth()->user()) ? Company::query() : $this->eligibleCompanies($event);
    }

    /** Does this pending write place a company on a stand in a way that must be checked? */
    public function requiresCheck(EventStand $stand): bool
    {
        if ($stand->type !== 'company' || $stand->company_id === null) {
            return false; // partner stand, or the stand is being emptied
        }

        return ! $stand->exists || $stand->isDirty('company_id') || $stand->isDirty('event_id') || $stand->isDirty('type');
    }

    /** @throws ValidationException when the company is not allowed on a stand of this stand's own edition */
    public function assertCanAssign(EventStand $stand): void
    {
        if (! $this->requiresCheck($stand)) {
            return;
        }

        // Always the edition stored on the stand itself, never a value from the request.
        $event = Event::query()->find($stand->event_id);

        if (! $event || $this->isEligible($event, (int) $stand->company_id)) {
            return;
        }

        $reason = trim((string) $stand->enrollmentOverrideReason);

        if ($reason !== '' && $this->canOverride(auth()->user())) {
            $stand->forceFill(['participation_confirmed_at' => now()]);
            $stand->overrideUsed = true;

            return;
        }

        throw ValidationException::withMessages(['company_id' => $this->message($event, (int) $stand->company_id)]);
    }

    /** @throws ValidationException */
    public function assertCompanyEligible(Event $event, int $companyId): void
    {
        if ($this->isEligible($event, $companyId)) {
            return;
        }

        throw ValidationException::withMessages(['company_id' => $this->message($event, $companyId)]);
    }

    /**
     * Company stands of an active or future edition whose company has no approved enrollment.
     * The stands are untouched; this only reports them.
     *
     * @return Collection<int, EventStand>
     */
    public function flaggedStands(Event $event): Collection
    {
        if ($this->isHistorical($event)) {
            return collect(); // history is not "flagged"
        }

        return EventStand::query()
            ->with('company')
            ->where('event_id', $event->id)
            ->where('type', 'company')
            ->whereNotNull('company_id')
            ->whereNull('participation_confirmed_at') // explicitly overridden by a Super Admin: not a grandfathered leftover
            ->whereNotIn('company_id', $event->approvedCompanies()->select('companies.id'))
            ->orderByRaw('CAST(stand_number AS UNSIGNED)')
            ->get();
    }

    /** @return array{label: string, short: string, enrollment: ?Enrollment} why a stand is flagged, with the enrollment to open if there is one */
    public function explain(EventStand $stand): array
    {
        $enrollment = Enrollment::query()
            ->where('event_id', $stand->event_id)
            ->where(fn ($q) => $q->where('company_id', $stand->company_id)->orWhere('selected_company_id', $stand->company_id))
            ->latest('id')->first();

        return [
            'enrollment' => $enrollment,
            'short' => $enrollment ? "Enrollment {$enrollment->status}" : 'No enrollment',
            'label' => $enrollment
                ? "Enrollment is {$enrollment->status}, not approved"
                : 'No enrollment for this edition (assignment predates enrollments)',
        ];
    }

    private function message(Event $event, int $companyId): string
    {
        $name = Company::query()->whereKey($companyId)->value('name') ?? "#{$companyId}";

        return "{$name} has no approved enrollment for {$event->name}, so it cannot be given a stand.";
    }
}
