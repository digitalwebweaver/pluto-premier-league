<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\Guards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service account / profile page (FR-AUTH-013): name, contact email,
 * notification preference. Works for whichever guard is signed in.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $guard = Guards::active($request);
        $user = $request->user($guard);

        return Inertia::render('Settings/Profile', [
            'role' => $guard === Guards::LT ? 'lt' : 'captain',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'notification_pref' => $user->notification_pref,
            ],
            'status' => session('status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $guard = Guards::active($request);
        $user = $request->user($guard);
        $table = $guard === Guards::LT ? 'lt_users' : 'team_users';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique($table, 'email')->ignore($user->id)],
            'notification_pref' => ['required', 'in:email,none'],
        ]);

        $user->update($validated);

        return back()->with('status', 'Your profile has been updated.');
    }
}
