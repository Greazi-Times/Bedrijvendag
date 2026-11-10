<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pages whose URL is itself a credential (invitation, enrollment and verification links). */
class PrivatePage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        // same-origin, not no-referrer: the token never reaches other sites, but form posts still carry the Referer
        // that Laravel's back() redirect needs to return validation errors to the page.
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
