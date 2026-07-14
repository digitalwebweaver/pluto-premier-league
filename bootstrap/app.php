<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsurePasswordIsSet::class,
        ]);

        // `guest` checks both guards (team + lt) — see RedirectIfAuthenticated.
        // `guard` enforces guard isolation (cross-guard → 403) — see EnsureGuard.
        $middleware->alias([
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'guard' => \App\Http\Middleware\EnsureGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Render on-brand Inertia error pages for common HTTP errors in production.
        // (In local/debug we keep Laravel's detailed error page.)
        $exceptions->respond(function (Response $response, Throwable $exception, $request) {
            if (app()->hasDebugModeEnabled() || $request->is('design/error/*')) {
                return $response;
            }

            $status = $response->getStatusCode();

            if (in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
