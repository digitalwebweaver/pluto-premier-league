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
 * Forced first-login set-password (FR-AUTH-008). Reached when a freshly-issued
 * account has `must_set_password = true`; EnsurePasswordIsSet keeps the user
 * here until they choose a password.
 */
class SetPasswordController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/SetPassword');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $guard = Guards::active($request);

        abort_if($guard === null, 403);

        $user = $request->user($guard);
        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_set_password' => false,
        ])->save();

        return redirect()
            ->intended(route(Guards::dashboardRoute($guard)))
            ->with('status', 'Your password is set — welcome aboard.');
    }
}
