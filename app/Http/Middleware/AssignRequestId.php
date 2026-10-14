<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every request a correlation ID. Audit entries store it, Laravel's log
 * context carries it into application logs and queued jobs, and the response
 * returns it as X-Request-Id. Incoming IDs are not trusted, so a client cannot
 * tie its requests to someone else's entries.
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        Context::add('request_id', $id = (string) Str::uuid());

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}
