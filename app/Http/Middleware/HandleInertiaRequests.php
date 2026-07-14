<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'guard' => $this->activeGuard($request),
                'user' => fn () => $this->activeUser($request),
            ],
            // Live count of entries awaiting LT approval (drives the nav badge).
            'pendingApprovals' => fn () => $this->activeGuard($request) === \App\Support\Auth\Guards::LT
                ? \App\Models\MeetingEntry::where('status', \App\Models\MeetingEntry::SUBMITTED)->count()
                : null,
            // Unread notification count for the captain (bell badge).
            'unreadNotifications' => fn () => $this->unreadForCaptain($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                // One-time issued/reset credential for LT to relay (1F).
                'issued' => fn () => $request->session()->get('issued'),
            ],
        ];
    }

    /** The guard the current request is authenticated on, if any. */
    protected function activeGuard(Request $request): ?string
    {
        foreach (\App\Support\Auth\Guards::ALL as $guard) {
            if ($request->user($guard)) {
                return $guard;
            }
        }

        return null;
    }

    /** Unread notification count for the signed-in captain (if any). */
    protected function unreadForCaptain(Request $request): ?int
    {
        $captain = $request->user('team');
        if (! $captain || ! $captain->team_id) {
            return null;
        }

        return \App\Models\Notification::where('team_id', $captain->team_id)->whereNull('read_at')->count();
    }

    /** A slim, safe view of the authenticated user for the frontend. */
    protected function activeUser(Request $request): ?array
    {
        $guard = $this->activeGuard($request);

        if (! $guard) {
            return null;
        }

        $user = $request->user($guard);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'guard' => $guard,
            'must_set_password' => (bool) $user->must_set_password,
        ];
    }
}
