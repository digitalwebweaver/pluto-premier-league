<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\LtUser;
use App\Models\TeamUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT-only login administration (FR-TEAM-004/005, FR-AUTH-008, BR-AUTH-003).
 *
 * Issuing or resetting a login generates a one-time temporary password shown
 * to the LT to relay; the account is flagged `must_set_password`, so the holder
 * is forced to choose their own password on first sign-in (see 1D flow).
 */
class LoginManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LT/Logins', [
            'captains' => TeamUser::orderBy('name')->get()->map($this->present(...)),
            'leadership' => LtUser::orderBy('name')->get()->map($this->present(...)),
        ]);
    }

    public function storeCaptain(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('team_users', 'email')],
        ]);

        $temp = $this->tempPassword();

        TeamUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temp),
            'must_set_password' => true,
            'notification_pref' => 'email',
            'is_active' => true,
        ]);

        return back()->with('issued', $this->issued('captain', 'issued', $data['name'], $data['email'], $temp));
    }

    public function resetCaptain(TeamUser $teamUser): RedirectResponse
    {
        $temp = $this->tempPassword();
        $teamUser->update(['password' => Hash::make($temp), 'must_set_password' => true]);

        return back()->with('issued', $this->issued('captain', 'reset', $teamUser->name, $teamUser->email, $temp));
    }

    public function storeLt(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('lt_users', 'email')],
        ]);

        $temp = $this->tempPassword();

        LtUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temp),
            'must_set_password' => true,
            'notification_pref' => 'email',
            'is_active' => true,
        ]);

        return back()->with('issued', $this->issued('lt', 'issued', $data['name'], $data['email'], $temp));
    }

    public function resetLt(LtUser $ltUser): RedirectResponse
    {
        $temp = $this->tempPassword();
        $ltUser->update(['password' => Hash::make($temp), 'must_set_password' => true]);

        return back()->with('issued', $this->issued('lt', 'reset', $ltUser->name, $ltUser->email, $temp));
    }

    /** Letters + numbers, 12 chars — satisfies BR-AUTH-002 and stays relayable. */
    private function tempPassword(): string
    {
        return Str::password(12, letters: true, numbers: true, symbols: false, spaces: false);
    }

    private function present(TeamUser|LtUser $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'pending_setup' => (bool) $user->must_set_password,
            'is_active' => (bool) $user->is_active,
        ];
    }

    private function issued(string $kind, string $action, string $name, string $email, string $password): array
    {
        return compact('kind', 'action', 'name', 'email', 'password');
    }
}
