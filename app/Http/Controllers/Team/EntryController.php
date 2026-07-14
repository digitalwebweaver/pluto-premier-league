<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\EntryLine;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Season;
use App\Services\EntryScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Captain score entry (Phase 3). One entry per (team, meeting) — reopening
 * loads the same record (FR-ENT-013). Own-team + open-meeting scoping
 * (BR-ENT-002). The server recompute is authoritative (BR-ENT-001).
 */
class EntryController extends Controller
{
    /** The count/amount input shapes that use repeatable line rows (3B). */
    private const LINE_SHAPES = [Category::COUNT_SUBTYPE, Category::AMOUNT_SUBTYPE];

    public function index(Request $request): Response
    {
        $teamId = $request->user('team')->team_id;
        $season = Season::current();

        $statuses = $teamId
            ? MeetingEntry::where('team_id', $teamId)->pluck('status', 'meeting_id')
            : collect();

        $meetings = $season
            ? $season->meetings()->orderBy('sequence_no')->get()->map(fn (Meeting $m) => [
                'id' => $m->id,
                'sequence_no' => $m->sequence_no,
                'meeting_date' => $m->meeting_date->toDateString(),
                'window' => $m->status,
                'entry_status' => $statuses[$m->id] ?? null,
            ])
            : collect();

        return Inertia::render('Team/Submit', [
            'hasTeam' => $teamId !== null,
            'season' => $season?->only('name'),
            'meetings' => $meetings,
        ]);
    }

    public function open(Request $request, Meeting $meeting): RedirectResponse|Response
    {
        [$entry, $teamId] = $this->resolveEntry($request, $meeting, createIfOpen: true);

        if ($entry instanceof RedirectResponse) {
            return $entry;
        }

        $members = \App\Models\Member::forTeam($teamId)->active()->orderBy('name')->get(['id', 'name']);

        $categories = $meeting->categories()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->with(['scoringRules' => fn ($q) => $q->where('is_active', true)->orderBy('display_order')->orderBy('id')])
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'input_shape' => $c->input_shape,
                'rules' => $c->scoringRules->map(fn ($r) => [
                    'id' => $r->id,
                    'subtype_label' => $r->subtype_label,
                    'points' => $r->points,
                    'extra_params' => $r->extra_params, // flat/penalty/metric for roster (3C)
                ]),
            ]);

        $lines = $entry->lines()->get(['id', 'category_id', 'scoring_rule_id', 'member_id', 'visitor_name', 'count', 'amount']);
        $attendance = $entry->attendance()->get(['member_id', 'is_present', 'is_on_time']);

        return Inertia::render('Team/Scorecard', [
            'meeting' => [
                'id' => $meeting->id,
                'sequence_no' => $meeting->sequence_no,
                'meeting_date' => $meeting->meeting_date->toDateString(),
                'window' => $meeting->status,
                'is_open' => $meeting->isOpen(),
            ],
            'entry' => [
                'id' => $entry->id,
                'status' => $entry->status,
                'computed_total' => $entry->computed_total,
                'editable' => $meeting->isOpen() && $entry->isEditableByTeam(),
                'sent_back_note' => $entry->status === MeetingEntry::SENT_BACK ? $entry->sent_back_note : null,
            ],
            'categories' => $categories,
            'members' => $members,
            'lines' => $lines,
            'attendance' => $attendance,
        ]);
    }

    /** Persist the draft's count/amount line rows and recompute the total. */
    public function saveDraft(Request $request, Meeting $meeting, EntryScoringService $scorer): RedirectResponse
    {
        $teamId = $request->user('team')->team_id;
        abort_if($teamId === null, 404, 'Your account is not linked to a team yet.');

        // Only open meetings are writable — check before creating anything.
        abort_unless($meeting->isOpen(), 403, 'This meeting is not open for submissions.');

        $entry = MeetingEntry::firstOrCreate(
            ['team_id' => $teamId, 'meeting_id' => $meeting->id],
            ['status' => MeetingEntry::DRAFT],
        );

        abort_unless($entry->isEditableByTeam(), 403, 'This scorecard is not editable.');

        $data = $this->validatePayload($request);

        $this->validateLineOwnership($meeting, $teamId, $data['lines'] ?? []);
        $this->validateAttendanceOwnership($teamId, $data['attendance'] ?? []);

        $this->persist($entry, $data, $scorer);

        return back()->with('success', 'Draft saved.');
    }

    /** Pre-submit review — recomputes server-side and shows a read-back. */
    public function review(Request $request, Meeting $meeting, EntryScoringService $scorer): RedirectResponse|Response
    {
        [$entry, $teamId] = $this->resolveEntry($request, $meeting, createIfOpen: false);

        if (! $entry) {
            return redirect()->route('team.submit')->with('error', 'Nothing to review yet.');
        }

        $breakdown = $scorer->breakdown($entry); // authoritative recompute

        return Inertia::render('Team/Review', [
            'meeting' => [
                'id' => $meeting->id,
                'sequence_no' => $meeting->sequence_no,
                'meeting_date' => $meeting->meeting_date->toDateString(),
            ],
            'entry' => [
                'status' => $entry->status,
                'editable' => $meeting->isOpen() && $entry->isEditableByTeam(),
            ],
            'categories' => $breakdown['categories'],
            'total' => $breakdown['total'],
        ]);
    }

    /** Submit to LT — routed through ApprovalService (server total authoritative + audited). */
    public function submit(Request $request, Meeting $meeting, \App\Services\ApprovalService $approval): RedirectResponse
    {
        $teamId = $request->user('team')->team_id;
        abort_if($teamId === null, 404);
        abort_unless($meeting->isOpen(), 403, 'This meeting is not open for submissions.');

        $entry = MeetingEntry::firstWhere(['team_id' => $teamId, 'meeting_id' => $meeting->id]);
        abort_if($entry === null, 404);
        abort_unless($entry->isEditableByTeam(), 403, 'This scorecard is not editable.');

        // FR-ENT-012: if a roster category applies, at least one attendance mark is required.
        $hasRoster = $meeting->categories()->where('input_shape', Category::ROSTER_FLAT_PENALTY)->exists();
        if ($hasRoster && $entry->attendance()->count() === 0) {
            throw ValidationException::withMessages([
                'attendance' => 'Mark attendance before submitting.',
            ]);
        }

        $approval->submit($entry); // recompute + status submitted + history

        return redirect()->route('team.submit')
            ->with('success', "Meeting {$meeting->sequence_no} submitted to the Leadership Team.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'lines' => ['array'],
            'lines.*.category_id' => ['required', 'integer'],
            'lines.*.scoring_rule_id' => ['required', 'integer'],
            'lines.*.member_id' => ['nullable', 'integer'],
            'lines.*.visitor_name' => ['nullable', 'string', 'max:191'],
            'lines.*.count' => ['required', 'integer', 'min:0'],
            'lines.*.whole_team' => ['nullable', 'boolean'],
            'lines.*.amount' => ['nullable', 'numeric', 'min:0'],
            'attendance' => ['array'],
            'attendance.*.member_id' => ['required', 'integer'],
            'attendance.*.is_present' => ['required', 'boolean'],
            'attendance.*.is_on_time' => ['required', 'boolean'],
        ]);
    }

    /**
     * Replace all lines + attendance for the entry and recompute (transaction).
     *
     * @param  array<string, mixed>  $data
     */
    private function persist(MeetingEntry $entry, array $data, EntryScoringService $scorer): void
    {
        DB::transaction(function () use ($entry, $data, $scorer) {
            $entry->lines()->delete();
            foreach ($data['lines'] ?? [] as $line) {
                if (($line['count'] ?? 0) <= 0) {
                    continue; // skip empty rows (binary "off" = count 0)
                }
                $entry->lines()->create([
                    'category_id' => $line['category_id'],
                    'scoring_rule_id' => $line['scoring_rule_id'],
                    'member_id' => $line['member_id'] ?? null,
                    'visitor_name' => $line['visitor_name'] ?? null,
                    'count' => $line['count'],
                    'whole_team' => $line['whole_team'] ?? null,
                    'amount' => $line['amount'] ?? null,
                ]);
            }

            $entry->attendance()->delete();
            foreach ($data['attendance'] ?? [] as $mark) {
                $entry->attendance()->create([
                    'member_id' => $mark['member_id'],
                    'is_present' => $mark['is_present'],
                    'is_on_time' => $mark['is_on_time'],
                ]);
            }

            $scorer->recompute($entry);
        });
    }

    /**
     * Resolve the (team) entry for a meeting. Returns [entry|RedirectResponse|null, teamId].
     */
    private function resolveEntry(Request $request, Meeting $meeting, bool $createIfOpen): array
    {
        $teamId = $request->user('team')->team_id;
        abort_if($teamId === null, 404, 'Your account is not linked to a team yet.');

        $entry = MeetingEntry::firstWhere(['team_id' => $teamId, 'meeting_id' => $meeting->id]);

        if (! $entry && $createIfOpen) {
            if (! $meeting->isOpen()) {
                return [redirect()->route('team.submit')->with('error', 'This meeting is not open for submissions.'), $teamId];
            }
            $entry = MeetingEntry::create([
                'team_id' => $teamId,
                'meeting_id' => $meeting->id,
                'status' => MeetingEntry::DRAFT,
            ]);
        }

        return [$entry, $teamId];
    }

    /**
     * Every line's category must apply to the meeting, its rule must belong to
     * that category, and any member must be an active member of the captain's
     * team (FR-ENT-012 / scoping).
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateLineOwnership(Meeting $meeting, int $teamId, array $lines): void
    {
        $applicable = $meeting->categories()->where('is_active', true)
            ->with('scoringRules:id,category_id')->get()->keyBy('id');

        $memberIds = \App\Models\Member::forTeam($teamId)->active()->pluck('id')->flip();

        foreach ($lines as $i => $line) {
            $category = $applicable->get($line['category_id']);
            if (! $category) {
                throw ValidationException::withMessages(["lines.$i.category_id" => 'Category does not apply to this meeting.']);
            }
            if (! $category->scoringRules->contains('id', $line['scoring_rule_id'])) {
                throw ValidationException::withMessages(["lines.$i.scoring_rule_id" => 'Subtype does not belong to this category.']);
            }
            if (! empty($line['member_id']) && ! $memberIds->has($line['member_id'])) {
                throw ValidationException::withMessages(["lines.$i.member_id" => 'Member is not on your active roster.']);
            }
        }
    }

    /**
     * Attendance marks may only be for the captain's own active members.
     *
     * @param  array<int, array<string, mixed>>  $attendance
     */
    private function validateAttendanceOwnership(int $teamId, array $attendance): void
    {
        $memberIds = \App\Models\Member::forTeam($teamId)->active()->pluck('id')->flip();

        foreach ($attendance as $i => $mark) {
            if (! $memberIds->has($mark['member_id'])) {
                throw ValidationException::withMessages(["attendance.$i.member_id" => 'Member is not on your active roster.']);
            }
        }
    }
}
