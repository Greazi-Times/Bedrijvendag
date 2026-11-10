<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasAutomaticTranslations;
use App\Services\FormResolver;
use App\Services\StandCapacity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Event extends Model
{
    use Audited, HasAutomaticTranslations;

    protected $fillable = [
        'name',
        'date',
        'max_stands',
        'partner_stand_count',
        'description',
        'header_image_path',
        'map_path',
        'google_photos_album_url',
        'enrollment_enabled',
        'enrollment_opens_at',
        'enrollment_closes_at',
        'info_closes_at',
        'fee_cents',
        'currency',
        'terms_html',
        'terms_version',
        'max_lunch_participants',
        'enrollment_form_id',
        'info_form_id',
    ];

    protected $casts = [
        'date' => 'date',
        'max_stands' => 'integer',
        'partner_stand_count' => 'integer',
        'description' => 'array',
        'enrollment_enabled' => 'boolean',
        'enrollment_opens_at' => 'datetime',
        'enrollment_closes_at' => 'datetime',
        'info_closes_at' => 'datetime',
        'fee_cents' => 'integer',
        'terms_version' => 'integer',
        'max_lunch_participants' => 'integer',
    ];

    /** The token is deliberately not fillable: it is only ever set by the model itself. */
    protected $hidden = ['enrollment_token'];

    public function automaticTranslationFields(): array
    {
        return ['name', 'description'];
    }

    public static function newEnrollmentToken(): string
    {
        do {
            $token = Str::random(40);
        } while (self::query()->where('enrollment_token', $token)->exists());

        return $token;
    }

    /** Invalidates the previous public link. */
    public function regenerateEnrollmentToken(): void
    {
        $this->forceFill(['enrollment_token' => self::newEnrollmentToken()])->save();
    }

    public function enrollmentUrl(): string
    {
        return route('enrollment.show', ['token' => $this->enrollment_token]);
    }

    /** 'open' | 'disabled' | 'not_open_yet' | 'closed' */
    public function enrollmentState(): string
    {
        return match (true) {
            ! $this->enrollment_enabled => 'disabled',
            $this->enrollment_opens_at?->isFuture() => 'not_open_yet',
            $this->enrollment_closes_at?->isPast() => 'closed',
            default => 'open',
        };
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Companies approved for this edition, independent of whether they hold a stand. */
    public function approvedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'enrollments', 'event_id', 'company_id')
            ->wherePivot('status', Enrollment::STATUS_APPROVED);
    }

    /** Companies may fill in or edit the second form until this moment (null: no deadline). */
    public function infoIsEditable(): bool
    {
        return $this->info_closes_at === null || $this->info_closes_at->isFuture();
    }

    /** The edition's own enrollment form, or null while it inherits the global default. */
    public function enrollmentFormOverride(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'enrollment_form_id');
    }

    public function infoFormOverride(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'info_form_id');
    }

    /** The form new enrollments are answered against: the override, else the global default. */
    protected function enrollmentForm(): Attribute
    {
        return Attribute::get(fn (): ?Form => app(FormResolver::class)->resolve($this, 'enrollment'));
    }

    protected function infoForm(): Attribute
    {
        return Attribute::get(fn (): ?Form => app(FormResolver::class)->resolve($this, 'info'));
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'event_stands', 'event_id', 'company_id')
            ->wherePivot('type', 'company')
            ->withPivot('type', 'stand_number', 'x_percent', 'y_percent')
            ->withTimestamps();
    }

    public function stands(): HasMany
    {
        return $this->hasMany(EventStand::class);
    }

    public function companyStands(): HasMany
    {
        return $this->hasMany(EventStand::class)->where('type', 'company');
    }

    public function partnerStands(): HasMany
    {
        return $this->hasMany(EventStand::class)->where('type', 'partner');
    }

    public function borrelEnrollments(): HasMany
    {
        return $this->hasMany(BorrelEnrollment::class);
    }

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'event_stands', 'event_id', 'partner_id')
            ->wherePivot('type', 'partner')
            ->withPivot('type', 'stand_number', 'x_percent', 'y_percent')
            ->withTimestamps();
    }

    public function getNextPartnerStandNumber(): string
    {
        $availableNumbers = $this->getAvailablePartnerStandNumbers();

        if ($availableNumbers !== []) {
            return $availableNumbers[0];
        }

        $lastNumber = $this->partnerStands()
            ->pluck('stand_number')
            ->map(fn (?string $standNumber): int => (int) $standNumber)
            ->max() ?? 0;

        return (string) ($lastNumber + 1);
    }

    public function getAvailablePartnerStandNumbers(): array
    {
        $count = max(0, (int) ($this->partner_stand_count ?? 0));

        if ($count === 0) {
            return [];
        }

        $usedNumbers = $this->partnerStands()
            ->pluck('stand_number')
            ->map(fn (?string $standNumber): string => (string) ((int) $standNumber))
            ->filter()
            ->values()
            ->all();

        return collect(range(1, $count))
            ->map(fn (int $number): string => (string) $number)
            ->reject(fn (string $standNumber): bool => in_array($standNumber, $usedNumbers, true))
            ->values()
            ->all();
    }

    public function scopeNextOrLatest(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $today = now()->startOfDay();

        // Prefer the next upcoming event (today or future). If none exists, fall back to the latest past event.
        $upcomingId = (clone $query)
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->value('id');

        if ($upcomingId) {
            return $query->whereKey($upcomingId);
        }

        $pastId = (clone $query)
            ->whereDate('date', '<', $today)
            ->orderByDesc('date')
            ->value('id');

        return $pastId ? $query->whereKey($pastId) : $query->whereRaw('1=0');
    }

    protected static function booted(): void
    {
        // Explicit and ordered: enrollments (and their private files) must go before the forms whose versions they reference.
        static::deleting(function (self $event): void {
            $event->enrollments()->each(fn (Enrollment $enrollment) => $enrollment->delete());
        });

        static::creating(function (self $event): void {
            $event->enrollment_token ??= self::newEnrollmentToken();
        });

        static::updating(function (self $event): void {
            foreach (['enrollment_form_id' => 'enrollment', 'info_form_id' => 'info'] as $column => $kind) {
                if ($event->isDirty($column) && $event->{$column} !== null) {
                    $form = Form::query()->find($event->{$column});

                    // Only this edition's own form of the right kind may be an override. A global default is inherited,
                    // never selected, and another edition's form is never usable here: that is how changes leaked before.
                    if (! $form || $form->kind !== $kind || ! $form->isEditionSpecific() || $form->event_id !== $event->id) {
                        throw ValidationException::withMessages([$column => 'An edition can only use its own custom form of the right kind. Use "Customize for this edition" to create one.']);
                    }
                }
            }

            // Blocks any save (Filament or code) that would drop an occupied stand; the stand-row sync is the second guard.
            app(StandCapacity::class)->assertCanResize($event);

            // Fee or terms changed: bump the version so acceptances can say which text they accepted.
            if ($event->isDirty('terms_html') && ! $event->isDirty('terms_version')) {
                $event->terms_version = max(1, (int) $event->getOriginal('terms_version')) + 1;
            }
        });

        static::updated(function (self $event): void {
            // Only clear markers if the map image actually changed
            if (! $event->wasChanged('map_path')) {
                return;
            }

            DB::table('event_stands')
                ->where('event_id', $event->id)
                ->update([
                    'x_percent' => null,
                    'y_percent' => null,
                ]);
        });
    }

    public function eventPartners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'event_partner', 'event_id', 'partner_id')
            ->withTimestamps();
    }
}
