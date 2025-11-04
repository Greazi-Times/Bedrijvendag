<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Edition;
use App\Models\Sector;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'logo',
        'href',
        'visible',
    ];

    protected $casts = [
        'visible' => 'boolean',
    ];

    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class, 'company_edition')->withTimestamps();
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'company_sector')->withTimestamps();
    }
}
