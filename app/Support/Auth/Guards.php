<?php

namespace App\Support\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Central definition of the two auth guards and their post-login dashboards
 * (FR-AUTH-003). Reused by the login flow, guard middleware, and RBAC so the
 * guard → dashboard mapping lives in exactly one place.
 */
final class Guards
{
    public const TEAM = 'team';

    public const LT = 'lt';

    /** @var list<string> */
    public const ALL = [self::TEAM, self::LT];

    public static function isValid(string $guard): bool
    {
        return in_array($guard, self::ALL, true);
    }

    /** Route name for a guard's home dashboard. */
    public static function dashboardRoute(string $guard): string
    {
        return $guard === self::LT ? 'lt.overview' : 'team.dashboard';
    }

    /** The guard the request is currently authenticated on, if any. */
    public static function active(Request $request): ?string
    {
        foreach (self::ALL as $guard) {
            if ($request->user($guard)) {
                return $guard;
            }
        }

        return null;
    }

    /** Password broker name for a guard (matches config/auth.php `passwords`). */
    public static function broker(string $guard): string
    {
        return $guard.'_users';
    }
}
