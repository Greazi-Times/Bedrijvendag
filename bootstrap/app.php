<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Support\Audit;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Logs 403s and CSRF failures, then lets Laravel render the usual response.
        $exceptions->render(function (HttpExceptionInterface $e) {
            Audit::httpDenied($e);

            return null;
        });

        // Admin routes never confirm they exist: guests and users without access get the
        // same 404 as a missing page. 403, 404 and 419 render the site's Error page with
        // fixed props (no user, no database), so the page never varies with who is asking.
        // Everything else, 500 included, falls through to resources/views/errors.
        $exceptions->render(function (Throwable $e, Request $request) {
            $status = $e instanceof AuthenticationException ? 401 : ($e instanceof HttpExceptionInterface ? $e->getStatusCode() : null);

            if (in_array($status, [401, 403], true) && $request->routeIs('filament.*', 'filament-excel-download')) {
                $status = 404;
            }

            if ($request->expectsJson()) {
                return $status === 404 ? response()->json(['message' => 'Not Found'], 404) : null;
            }

            if (! in_array($status, [403, 404, 419], true)) {
                return null;
            }

            HandleAppearance::share($request);
            Inertia::flushShared();

            return Inertia::render('Error', [
                'status' => $status,
                'name' => config('app.name'),
                'deploymentVersion' => config('version.display'),
                'locale' => app()->getLocale(),
                'supportedLocales' => ['nl', 'en'],
                'auth' => ['user' => null, 'canAccessDashboard' => false],
                'media' => ['siteLogo' => '/favicon.svg'],
            ])->toResponse($request)->setStatusCode($status);
        });
    })->create();
