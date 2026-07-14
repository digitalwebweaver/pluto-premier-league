<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Captain's own team profile (FR-TEAM-006). A captain may edit ONLY the crest
 * colour here; name/short-code/status are LT-only (a captain hitting the LT
 * team routes already gets 403 via the `guard:lt` group). The team is resolved
 * from the authenticated captain — never a route param — so there is no
 * cross-team surface.
 */
class TeamProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $team = $request->user('team')->team;

        return Inertia::render('Team/Profile', [
            'team' => $team ? $team->only('id', 'name', 'short_code', 'crest_color', 'is_active') : null,
            'status' => session('success'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $team = $request->user('team')->team;

        abort_if($team === null, 404, 'Your account is not linked to a team yet.');

        $data = $request->validate([
            'crest_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $team->update(['crest_color' => $data['crest_color']]);

        return back()->with('success', 'Your team crest colour has been updated.');
    }
}
