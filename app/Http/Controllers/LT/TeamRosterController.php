<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT roster management for any team (owner request) — mirrors the captain's
 * own RosterController, but every route carries the target {team} since LT
 * has no team_id of its own and manages every team's roster, not just one.
 */
class TeamRosterController extends Controller
{
    public function index(Team $team): Response
    {
        return Inertia::render('LT/Teams/Roster', [
            'team' => $team->only('id', 'name', 'short_code', 'crest_color'),
            'members' => Member::forTeam($team->id)
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map($this->present(...)),
        ]);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        $data = $this->validateMember($request);

        Member::create($data + ['team_id' => $team->id, 'is_active' => true]);

        return back()->with('success', "{$data['name']} added to {$team->name}’s roster.");
    }

    public function edit(Team $team, Member $member): Response
    {
        $this->authorizeMember($team, $member);

        return Inertia::render('LT/Teams/MemberEdit', [
            'team' => $team->only('id', 'name'),
            'member' => $member->only('id', 'name', 'business_category', 'avatar_color', 'is_active'),
        ]);
    }

    public function update(Request $request, Team $team, Member $member): RedirectResponse
    {
        $this->authorizeMember($team, $member);

        $member->update($this->validateMember($request));

        return redirect()->route('lt.teams.roster', $team)->with('success', 'Member updated.');
    }

    /** Deactivate / reactivate — never hard-deleted (BR-MBR-001). */
    public function toggleActive(Team $team, Member $member): RedirectResponse
    {
        $this->authorizeMember($team, $member);

        $member->update(['is_active' => ! $member->is_active]);

        return back()->with('success', $member->is_active
            ? "{$member->name} is active."
            : "{$member->name} set inactive (history kept).");
    }

    /** The {member} in the URL must actually belong to the {team} in the URL. */
    private function authorizeMember(Team $team, Member $member): void
    {
        abort_unless($member->team_id === $team->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMember(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'business_category' => ['nullable', 'string', 'max:191'],
            'avatar_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
    }

    private function present(Member $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'business_category' => $member->business_category,
            'avatar_color' => $member->avatar_color,
            'is_active' => $member->is_active,
        ];
    }
}
