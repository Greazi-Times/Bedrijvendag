<?php

namespace App\Support\Privacy;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/** Table search for an encrypted email column: only an exact (normalised) address matches, through its keyed hash. */
class EmailSearch
{
    public static function exact(string $column): Closure
    {
        return fn (Builder $query, string $search): Builder => $query->orWhere(fn (Builder $q) => $q->whereEmail($column, $search));
    }
}
