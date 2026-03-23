<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'name',
        'date',
        'max_stands',
        'partner_stand_count',
        'description',
        'header_image_path',
        'map_path',
        'google_photos_album_url',
    ];

    protected $casts = [
        'date' => 'date',
        'max_stands' => 'integer',
        'partner_stand_count' => 'integer',
        'description' => 'array',
    ];

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
}
