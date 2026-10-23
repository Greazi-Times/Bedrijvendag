<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        self::share($request);

        return $next($request);
    }

    /** Also called for error pages, which can render without this middleware. */
    public static function share(Request $request): void
    {
        $appearance = $request->cookie('appearance');

        View::share(
            'appearance',
            in_array($appearance, ['light', 'dark', 'system'], true) ? $appearance : 'light',
        );
    }
}
