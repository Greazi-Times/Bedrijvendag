<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Company;

class Edition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'date',
        'images',
        'thumbnail',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_edition');
    }
}
