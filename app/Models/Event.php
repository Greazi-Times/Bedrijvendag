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
        'description',
        'header_image_path',
        'map_path',
        'google_photos_album_url',
    ];

    protected $casts = [
        'date' => 'date',
        'max_stands' => 'integer',
        'description' => 'array',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('stand_number', 'x_percent', 'y_percent')
            ->withTimestamps();
    }

    public function borrelEnrollments(): HasMany
    {
        return $this->hasMany(BorrelEnrollment::class);
    }

    public function partners()
    {
        return $this->belongsToMany(Partner::class);
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

            DB::table('company_event')
                ->where('event_id', $event->id)
                ->update([
                    'x_percent' => null,
                    'y_percent' => null,
                ]);
        });
    }
}
