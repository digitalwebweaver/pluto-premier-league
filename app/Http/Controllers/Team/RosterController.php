<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Captain roster management (FR-MBR-001..003, 007). Strictly own-team:
 * every query is team-scoped via `Member::forTeam()` (BelongsToTeam), and any
 * member touched by id is verified to belong to the captain's team (BR-MBR-003).
 */
class RosterController extends Controller
{
    public function index(Request $request): Response
    {
        $teamId = $request->user('team')->team_id;

        return Inertia::render('Team/Roster', [
            'hasTeam' => $teamId !== null,
            'members' => $teamId === null ? [] : Member::forTeam($teamId)
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map($this->present(...)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teamId = $request->user('team')->team_id;
        abort_if($teamId === null, 404, 'Your account is not linked to a team yet.');

        $data = $this->validateMember($request);

        Member::create($data + ['team_id' => $teamId, 'is_active' => true]);

        return back()->with('success', "{$data['name']} added to your roster.");
    }

    public function show(Request $request, Member $member): Response
    {
        $this->authorizeMember($request, $member);

        // Member detail + season contribution history is Phase 3+ (FR-MBR-006).
        return Inertia::render('Team/MemberDetail', [
            'member' => $this->present($member),
        ]);
    }

    public function edit(Request $request, Member $member): Response
    {
        $this->authorizeMember($request, $member);

        return Inertia::render('Team/MemberEdit', [
            'member' => $member->only('id', 'name', 'business_category', 'avatar_color', 'is_active'),
        ]);
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $this->authorizeMember($request, $member);

        $member->update($this->validateMember($request));

        return redirect()->route('team.roster')->with('success', 'Member updated.');
    }

    /** Deactivate / reactivate — never hard-deleted (BR-MBR-001). */
    public function toggleActive(Request $request, Member $member): RedirectResponse
    {
        $this->authorizeMember($request, $member);

        $member->update(['is_active' => ! $member->is_active]);

        return back()->with('success', $member->is_active
            ? "{$member->name} is active."
            : "{$member->name} set inactive (history kept).");
    }

    /** A captain may only ever touch members of their own team (BR-MBR-003). */
    private function authorizeMember(Request $request, Member $member): void
    {
        abort_unless($member->team_id === $request->user('team')->team_id, 403);
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
