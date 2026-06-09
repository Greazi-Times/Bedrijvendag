<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'url',
        'image',
        'description',
    ];

    public function educations(): BelongsToMany
    {
        return $this->belongsToMany(Education::class)
            ->orderBy('name');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_stands', 'partner_id', 'event_id')
            ->wherePivot('type', 'partner')
            ->withPivot('type', 'stand_number', 'x_percent', 'y_percent')
            ->withTimestamps();
    }

    public function stands(): HasMany
    {
        return $this->hasMany(EventStand::class, 'partner_id');
    }
}
