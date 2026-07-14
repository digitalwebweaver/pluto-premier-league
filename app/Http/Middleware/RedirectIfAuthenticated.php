<?php

namespace App\Http\Middleware;

use App\Support\Auth\Guards;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * `guest` middleware for the two-guard setup: if the visitor is already
 * authenticated on either guard, send them to that guard's dashboard instead
 * of showing the login/guest screens.
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? Guards::ALL : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect()->route(Guards::dashboardRoute($guard));
            }
        }

        return $next($request);
    }
}
