<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Meeting;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT meeting administration (FR-MTG-001..004, 008). LT-only (guard:lt).
 * Meetings hang off the single active season.
 */
class MeetingController extends Controller
{
    public function index(): Response
    {
        $season = Season::current();
        $activeTeams = Team::active()->count();

        return Inertia::render('LT/Meetings/Index', [
            'season' => $season?->only('id', 'name'),
            'activeTeams' => $activeTeams,
            'meetings' => $season
                ? $season->meetings()->orderBy('sequence_no')->get()->map(fn (Meeting $m) => [
                    'id' => $m->id,
                    'sequence_no' => $m->sequence_no,
                    'meeting_date' => $m->meeting_date->toDateString(),
                    'status' => $m->status,
                    // Submission counts become real in Phase 3 (entries).
                    'submitted' => 0,
                    'approved' => 0,
                ])
                : [],
        ]);
    }

    public function store(Request $request, \App\Services\NotificationService $notifications): RedirectResponse
    {
        $season = Season::current();

        if (! $season) {
            throw ValidationException::withMessages([
                'meeting_date' => 'No active season. Create/activate a season first.',
            ]);
        }

        $data = $request->validate([
            'meeting_date' => ['required', 'date'],
            'sequence_no' => [
                'nullable', 'integer', 'min:1',
                Rule::unique('meetings', 'sequence_no')->where('season_id', $season->id),
            ],
        ]);

        $meeting = $season->meetings()->create([
            'meeting_date' => $data['meeting_date'],
            'sequence_no' => $data['sequence_no'] ?? (($season->meetings()->max('sequence_no') ?? 0) + 1),
            'status' => Meeting::SCHEDULED,
        ]);

        // Default applicable set = the full active category list (BR-MTG-002).
        $meeting->categories()->sync(Category::active()->pluck('id'));

        $notifications->broadcastToActiveTeams('new_meeting', [
            'meeting' => $meeting->sequence_no,
            'date' => $meeting->meeting_date->toDateString(),
        ]);

        return back()->with('success', 'Meeting created.');
    }

    /** Choose which categories apply to a meeting (FR-MTG-005). */
    public function editCategories(Meeting $meeting): Response
    {
        return Inertia::render('LT/Meetings/Categories', [
            'meeting' => [
                'id' => $meeting->id,
                'sequence_no' => $meeting->sequence_no,
                'meeting_date' => $meeting->meeting_date->toDateString(),
            ],
            'categories' => Category::active()->ordered()->get(['id', 'name', 'code']),
            'selectedIds' => $meeting->categories()->pluck('categories.id'),
        ]);
    }

    public function updateCategories(Request $request, Meeting $meeting): RedirectResponse
    {
        $data = $request->validate([
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $meeting->categories()->sync($data['category_ids'] ?? []);

        return redirect()->route('lt.meetings')->with('success', "Meeting {$meeting->sequence_no} categories updated.");
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $data = $request->validate([
            'meeting_date' => ['required', 'date'],
            'sequence_no' => [
                'required', 'integer', 'min:1',
                Rule::unique('meetings', 'sequence_no')
                    ->where('season_id', $meeting->season_id)
                    ->ignore($meeting->id),
            ],
        ]);

        $meeting->update($data);

        return back()->with('success', 'Meeting updated.');
    }

    /** Toggle open ⇆ closed (FR-MTG-004). Scheduled meetings open first. */
    public function toggleStatus(Meeting $meeting): RedirectResponse
    {
        $meeting->update([
            'status' => $meeting->isOpen() ? Meeting::CLOSED : Meeting::OPEN,
        ]);

        return back()->with('success', $meeting->isOpen()
            ? "Meeting {$meeting->sequence_no} is open for submissions."
            : "Meeting {$meeting->sequence_no} closed.");
    }
}
