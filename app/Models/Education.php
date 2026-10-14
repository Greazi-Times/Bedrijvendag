<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasAutomaticTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Education extends Model
{
    use Audited, HasAutomaticTranslations;

    protected string $auditCategory = 'education';

    protected $fillable = [
        'name',
        'description',
        'color',
        'website_url',
    ];

    public function automaticTranslationFields(): array
    {
        return ['name', 'description'];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }
}
