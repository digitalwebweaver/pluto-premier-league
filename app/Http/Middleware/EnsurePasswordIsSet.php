<?php

namespace App\Http\Middleware;

use App\Support\Auth\Guards;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a freshly-issued account (`must_set_password = true`) through the
 * set-password screen before any other route (FR-AUTH-008). Applied globally
 * on the web group; a no-op for guests and for already-set users.
 */
class EnsurePasswordIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Guards::active($request);

        if ($guard !== null
            && $request->user($guard)->must_set_password
            && ! $request->routeIs('password.set', 'password.set.store', 'logout')
        ) {
            return redirect()->route('password.set');
        }

        return $next($request);
    }
}
