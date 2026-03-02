<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sector extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }
}
