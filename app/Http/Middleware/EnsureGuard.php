<?php

namespace App\Http\Middleware;

use App\Support\Auth\Guards;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard-isolation gate (BR-AUTH-001 / FR-ROLE-001..003).
 *
 * - Authenticated on one of the allowed guards → continue (that guard becomes
 *   the request's default).
 * - Authenticated on a DIFFERENT guard (cross-guard) → 403 Forbidden.
 * - Genuine guest → redirect to login, preserving the intended URL.
 *
 * Usage: `->middleware('guard:team')`, `'guard:lt'`, or `'guard:team,lt'`.
 */
class EnsureGuard
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);

                return $next($request);
            }
        }

        // Signed in on a guard that isn't allowed here → forbidden, not a login bounce.
        foreach (Guards::ALL as $guard) {
            if (! in_array($guard, $guards, true) && Auth::guard($guard)->check()) {
                abort(403);
            }
        }

        // Genuine guest (e.g. expired session): bounce to login, preserving the
        // intended destination so re-auth returns them there (FR-AUTH-010).
        return redirect()->guest(route('login'))
            ->with('status', 'Please sign in to continue.');
    }
}
