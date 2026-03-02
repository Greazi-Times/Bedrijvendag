<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Education extends Model
{
    protected $fillable = [
        'name',
        'description',
        'website_url',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }
}
