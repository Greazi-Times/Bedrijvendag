<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        return $this->belongsToMany(Event::class)
            ->withPivot('stand_number')
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
}
