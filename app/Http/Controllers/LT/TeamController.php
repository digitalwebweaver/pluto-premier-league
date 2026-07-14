<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT team administration (FR-TEAM-001..003, 007, 008). LT-only — the route
 * group's `guard:lt` middleware is the authorization boundary.
 */
class TeamController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LT/Teams/Index', [
            'teams' => Team::with('captain:id,team_id,name,email,must_set_password')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map(fn (Team $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'short_code' => $t->short_code,
                    'crest_color' => $t->crest_color,
                    'is_active' => $t->is_active,
                    'captain' => $t->captain ? [
                        'name' => $t->captain->name,
                        'email' => $t->captain->email,
                        'pending_setup' => (bool) $t->captain->must_set_password,
                    ] : null,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTeam($request);

        Team::create([
            'name' => $data['name'],
            'crest_color' => $data['crest_color'],
            'short_code' => $data['short_code'] ?: Team::deriveShortCode($data['name']),
            'is_active' => true,
        ]);

        return redirect()->route('lt.teams')->with('success', "Team “{$data['name']}” created.");
    }

    public function edit(Team $team): Response
    {
        return Inertia::render('LT/Teams/Edit', [
            'team' => $team->only('id', 'name', 'short_code', 'crest_color', 'is_active'),
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $this->validateTeam($request, $team);

        $team->update([
            'name' => $data['name'],
            'crest_color' => $data['crest_color'],
            'short_code' => $data['short_code'] ?: Team::deriveShortCode($data['name']),
        ]);

        return redirect()->route('lt.teams')->with('success', 'Team updated.');
    }

    /** Activate / deactivate — never hard-deleted (BR-TEAM-001). */
    public function toggleActive(Team $team): RedirectResponse
    {
        $team->update(['is_active' => ! $team->is_active]);

        return back()->with('success', $team->is_active
            ? "“{$team->name}” is active."
            : "“{$team->name}” deactivated (history preserved).");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTeam(Request $request, ?Team $team = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191', Rule::unique('teams', 'name')->ignore($team?->id)],
            'short_code' => ['nullable', 'string', 'max:4'],
            'crest_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
    }
}
