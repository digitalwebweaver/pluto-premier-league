<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\Guards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Change password for a signed-in user (FR-AUTH-009) — requires the current
 * password. Shown inside the app shell for whichever guard is active.
 */
class PasswordController extends Controller
{
    public function edit(Request $request): Response
    {
        $guard = Guards::active($request);

        return Inertia::render('Settings/Password', [
            'role' => $guard === Guards::LT ? 'lt' : 'captain',
            'status' => session('status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $guard = Guards::active($request);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:'.$guard],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $request->user($guard)->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Your password has been updated.');
    }
}
