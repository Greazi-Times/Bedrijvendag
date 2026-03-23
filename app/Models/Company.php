<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'logo_path',
        'website_url',
        'description',
    ];

    protected $casts = [
        'description' => 'array',
    ];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_stands', 'company_id', 'event_id')
            ->wherePivot('type', 'company')
            ->withPivot('type', 'stand_number', 'x_percent', 'y_percent')
            ->withTimestamps();
    }

    public function educations(): BelongsToMany
    {
        return $this->belongsToMany(Education::class);
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class);
    }

    public function stands(): HasMany
    {
        return $this->hasMany(EventStand::class, 'company_id');
    }
}
