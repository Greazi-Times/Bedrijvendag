<?php

namespace App\Models\Concerns;

use App\Support\Privacy\EmailCrypto;
use Illuminate\Database\Eloquent\Builder;

/**
 * Keeps a keyed lookup hash next to an encrypted email column so exact matches still work in SQL.
 * Declare `protected array $emailHashes = ['contact_email' => 'contact_email_hash'];` on the model.
 */
trait HasEmailHash
{
    public static function bootHasEmailHash(): void
    {
        static::saving(function ($model): void {
            foreach ($model->emailHashes as $column => $hashColumn) {
                if ($model->isDirty($column) || ! $model->exists || $model->{$hashColumn} === null && $model->{$column} !== null) {
                    $model->{$hashColumn} = EmailCrypto::hash($model->{$column});
                }
            }
        });
    }

    /** Exact (normalised) match. Rows not yet backfilled are matched on their legacy plaintext, so nothing is missed in between. */
    public function scopeWhereEmail(Builder $query, string $column, ?string $email): Builder
    {
        $hashes = EmailCrypto::lookupHashes($email);

        if ($hashes === []) {
            return $query->whereRaw('1 = 0');
        }

        $hashColumn = $this->emailHashes[$column];
        $normalized = EmailCrypto::normalize($email);

        return $query->where(fn (Builder $q) => $q->whereIn($hashColumn, $hashes)
            ->orWhere(fn (Builder $legacy) => $legacy->whereNull($hashColumn)->whereRaw("lower({$column}) = ?", [$normalized])));
    }
}
