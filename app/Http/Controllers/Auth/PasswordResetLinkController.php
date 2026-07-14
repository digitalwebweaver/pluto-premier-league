<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\Guards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /** Show the "forgot password" screen (Team / LT tabs). */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /** Email a reset link — always responds generically (non-enumerating). */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'guard' => ['required', 'string', 'in:'.implode(',', Guards::ALL)],
            'email' => ['required', 'email'],
        ]);

        Password::broker(Guards::broker($request->input('guard')))
            ->sendResetLink($request->only('email'));

        // Never reveal whether the address exists (FR-AUTH-006 / no enumeration).
        return back()->with(
            'status',
            'If that email is registered, a password reset link is on its way.'
        );
    }
}
