<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * Record an administrative change made by the signed-in user. Callers pass
     * only the fields they mean to log; never pass passwords, hashes or tokens.
     *
     * @param  array<string, mixed>  $changes
     */
    public static function record(string $action, ?Model $subject = null, array $changes = [], ?string $label = null): self
    {
        return self::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $label ?? $subject?->getAttribute('name'),
            'changes' => $changes ?: null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
