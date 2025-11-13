<?php
// php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnsureUserIsEditorOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // If not authenticated, let other middleware (e.g. auth) handle it
        if (! $user) {
            return $next($request);
        }

        // Allow admins and editors
        if (in_array($user->role, ['admin', 'editor'])) {
            return $next($request);
        }

        // If the user is requesting the pending page already, allow it
        if ($request->routeIs('auth/Pending')) {
            return $next($request);
        }

        // Render the Pending Inertia page for non-editor/admin users
        return Inertia::render('auth/Pending', [
            'message' => 'Your account was created successfully. An admin will review your request.',
        ])->toResponse($request);
    }
}
