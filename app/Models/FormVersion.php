<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Immutable once created: updating or deleting a version would make old answers unreadable. */
class FormVersion extends Model
{
    protected $fillable = ['form_id', 'version', 'schema', 'published_by', 'published_at'];

    protected $casts = ['schema' => 'array', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Published form versions are immutable.'));
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
