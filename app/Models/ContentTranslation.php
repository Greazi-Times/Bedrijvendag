<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentTranslation extends Model
{
    protected $fillable = [
        'field',
        'source_locale',
        'locale',
        'source_hash',
        'value',
        'status',
        'error',
    ];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
