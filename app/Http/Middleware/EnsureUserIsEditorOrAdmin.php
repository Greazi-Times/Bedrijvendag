<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsEditorOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->user()->role, ['admin', 'editor'])) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
