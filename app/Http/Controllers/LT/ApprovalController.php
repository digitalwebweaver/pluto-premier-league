<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Member;
use App\Models\MeetingEntry;
use App\Services\ApprovalService;
use App\Services\EntryScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT approval queue + review (Phase 4B / FR-APR-001, 002). LT-only
 * (guard:lt). Approve / send-back / unlock actions land in 4C–4D. A
 * submitted entry can also be corrected directly in review (`update`) —
 * requires a reason, audited, team notified (not a Phase 4 requirement,
 * added on owner request so obvious captain mistakes don't need a
 * send-back round trip).
 */
class ApprovalController extends Controller
{
    /** All submitted entries across teams, oldest submission first. */
    public function queue(): Response
    {
        $entries = MeetingEntry::where('status', MeetingEntry::SUBMITTED)
            ->with(['team:id,name,short_code,crest_color', 'meeting:id,sequence_no,meeting_date'])
            ->orderBy('submitted_at')
            ->get()
            ->map(fn (MeetingEntry $e) => [
                'id' => $e->id,
                'computed_total' => $e->computed_total,
                'submitted_at' => optional($e->submitted_at)->toIso8601String(),
                'team' => [
                    'name' => $e->team->name,
                    'short_code' => $e->team->short_code,
                    'crest_color' => $e->team->crest_color,
                ],
                'meeting' => [
                    'sequence_no' => $e->meeting->sequence_no,
                    'meeting_date' => $e->meeting->meeting_date->toDateString(),
                ],
            ]);

        return Inertia::render('LT/Queue/Index', ['entries' => $entries]);
    }

    /** Review of one entry: server-computed category detail + editable data (correction UI). */
    public function review(MeetingEntry $entry, EntryScoringService $scorer): Response
    {
        $entry->load(['team:id,name,short_code,crest_color', 'meeting:id,sequence_no,meeting_date',
            'lines.category:id,name,code', 'lines.scoringRule:id,subtype_label', 'lines.member:id,name',
            'attendance.member:id,name', 'statusHistory' => fn ($q) => $q->orderByDesc('created_at')]);

        $breakdown = $scorer->breakdown($entry); // authoritative recompute (FR-APR-010)

        $members = Member::forTeam($entry->team_id)->active()->orderBy('name')->get(['id', 'name']);

        $categories = $entry->meeting->categories()
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
                    'extra_params' => $r->extra_params,
                ]),
            ]);

        return Inertia::render('LT/Queue/Review', [
            'entry' => [
                'id' => $entry->id,
                'status' => $entry->status,
                'computed_total' => $breakdown['total'],
                'submitted_at' => optional($entry->submitted_at)->toIso8601String(),
                'sent_back_note' => $entry->sent_back_note,
                // Optimistic-lock token — approve is rejected if the team edits after this.
                'version' => $entry->updated_at->toIso8601String(),
            ],
            'team' => [
                'name' => $entry->team->name,
                'short_code' => $entry->team->short_code,
                'crest_color' => $entry->team->crest_color,
            ],
            'meeting' => [
                'sequence_no' => $entry->meeting->sequence_no,
                'meeting_date' => $entry->meeting->meeting_date->toDateString(),
            ],
            'categories' => $categories, // editable shape (rules etc.) for ScorecardEditor
            'breakdownCategories' => $breakdown['categories'], // read-only computed totals
            'members' => $members,
            // Display shape for the read-only breakdown list below each category.
            'lines' => $entry->lines->map(fn ($l) => [
                'category_id' => $l->category_id,
                'category' => $l->category->name,
                'member' => $l->member?->name,
                'visitor_name' => $l->visitor_name,
                'subtype' => $l->scoringRule?->subtype_label,
                'count' => $l->count,
                'whole_team' => $l->whole_team,
                'amount' => $l->amount,
                'points' => $l->computed_points,
            ]),
            // Raw editable shape for ScorecardEditor — same shape Team\EntryController::open() returns.
            'editableLines' => $entry->lines->map(fn ($l) => [
                'category_id' => $l->category_id,
                'scoring_rule_id' => $l->scoring_rule_id,
                'member_id' => $l->member_id,
                'visitor_name' => $l->visitor_name,
                'count' => $l->count,
                'whole_team' => $l->whole_team,
                'amount' => $l->amount,
            ]),
            'editableAttendance' => $entry->attendance->map(fn ($a) => [
                'member_id' => $a->member_id,
                'is_present' => $a->is_present,
                'is_on_time' => $a->is_on_time,
            ]),
            'attendance' => [
                'present' => $entry->attendance->where('is_present', true)->count(),
                'absent' => $entry->attendance->where('is_present', false)->count(),
                'late' => $entry->attendance->where('is_on_time', false)->count(),
                'total' => $entry->attendance->count(),
            ],
            // Correction/audit history with a note (send-backs + LT edits) — most recent first.
            'history' => $entry->statusHistory->whereNotNull('note')->values()->map(fn ($h) => [
                'to_status' => $h->to_status,
                'actor_type' => $h->actor_type,
                'note' => $h->note,
                'created_at' => $h->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * LT corrects a submitted entry's lines/attendance directly, with a
     * required reason (audited + team-notified) — an alternative to
     * send-back for obvious mistakes that don't need the team's input.
     */
    public function update(Request $request, MeetingEntry $entry, ApprovalService $approval): RedirectResponse
    {
        if ($entry->status !== MeetingEntry::SUBMITTED) {
            return redirect()->route('lt.queue')
                ->with('error', 'That entry is no longer awaiting approval.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
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

        $this->validateLineOwnership($entry, $data['lines'] ?? []);
        $this->validateAttendanceOwnership($entry, $data['attendance'] ?? []);

        $approval->editByLt($entry, $request->user('lt'), $data['lines'] ?? [], $data['attendance'] ?? [], $data['reason']);

        return redirect()->route('lt.queue.review', $entry)->with('success', 'Entry updated.');
    }

    /**
     * Every line's category must apply to the entry's meeting, its rule must
     * belong to that category, and any member must be an active member of
     * the entry's (submitting) team — not the LT's, who has no team.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateLineOwnership(MeetingEntry $entry, array $lines): void
    {
        $applicable = $entry->meeting->categories()->where('is_active', true)
            ->with('scoringRules:id,category_id')->get()->keyBy('id');

        $memberIds = Member::forTeam($entry->team_id)->active()->pluck('id')->flip();

        foreach ($lines as $i => $line) {
            $category = $applicable->get($line['category_id']);
            if (! $category) {
                throw ValidationException::withMessages(["lines.$i.category_id" => 'Category does not apply to this meeting.']);
            }
            if (! $category->scoringRules->contains('id', $line['scoring_rule_id'])) {
                throw ValidationException::withMessages(["lines.$i.scoring_rule_id" => 'Subtype does not belong to this category.']);
            }
            if (! empty($line['member_id']) && ! $memberIds->has($line['member_id'])) {
                throw ValidationException::withMessages(["lines.$i.member_id" => 'Member is not on that team\'s active roster.']);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $attendance
     */
    private function validateAttendanceOwnership(MeetingEntry $entry, array $attendance): void
    {
        $memberIds = Member::forTeam($entry->team_id)->active()->pluck('id')->flip();

        foreach ($attendance as $i => $mark) {
            if (! $memberIds->has($mark['member_id'])) {
                throw ValidationException::withMessages(["attendance.$i.member_id" => 'Member is not on that team\'s active roster.']);
            }
        }
    }

    /** Approve → lock + snapshot (FR-APR-003). */
    public function approve(Request $request, MeetingEntry $entry, ApprovalService $approval): RedirectResponse
    {
        // Only a still-submitted entry can be approved (guards against a stale/edited version).
        if ($entry->status !== MeetingEntry::SUBMITTED) {
            return redirect()->route('lt.queue')
                ->with('error', 'That entry is no longer awaiting approval.');
        }

        // Optimistic lock: reject if the team edited the entry after the LT opened review.
        if ($request->filled('version') && $request->input('version') !== $entry->updated_at->toIso8601String()) {
            return redirect()->route('lt.queue.review', $entry)
                ->with('error', 'This submission changed since you opened it — please review again.');
        }

        $approval->approve($entry, $request->user('lt'));

        return redirect()->route('lt.queue')
            ->with('success', "{$entry->team->name} · Meeting {$entry->meeting->sequence_no} approved.");
    }

    /** Recently approved — quick access to unlock (FR-APR-009). */
    public function recent(): Response
    {
        $entries = MeetingEntry::where('status', MeetingEntry::APPROVED)
            ->with(['team:id,name,short_code,crest_color', 'meeting:id,sequence_no,meeting_date', 'approver:id,name'])
            ->orderByDesc('approved_at')
            ->limit(50)
            ->get()
            ->map(fn (MeetingEntry $e) => [
                'id' => $e->id,
                'computed_total' => $e->computed_total,
                'approved_at' => optional($e->approved_at)->toIso8601String(),
                'approved_by' => $e->approver?->name,
                'team' => [
                    'name' => $e->team->name,
                    'short_code' => $e->team->short_code,
                    'crest_color' => $e->team->crest_color,
                ],
                'meeting' => ['sequence_no' => $e->meeting->sequence_no],
            ]);

        return Inertia::render('LT/Recent/Index', ['entries' => $entries]);
    }

    /** Unlock an approved entry back to submitted for correction (FR-APR-006). */
    public function unlock(MeetingEntry $entry, ApprovalService $approval): RedirectResponse
    {
        if ($entry->status !== MeetingEntry::APPROVED) {
            return redirect()->route('lt.recent')->with('error', 'That entry is not approved.');
        }

        $approval->unlock($entry, request()->user('lt'));

        return redirect()->route('lt.recent')
            ->with('success', "{$entry->team->name} · Meeting {$entry->meeting->sequence_no} unlocked — it's back in the queue.");
    }

    /** Send back with a required note (FR-APR-004 / BR-APR-002). */
    public function sendBack(Request $request, MeetingEntry $entry, ApprovalService $approval): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        if ($entry->status !== MeetingEntry::SUBMITTED) {
            return redirect()->route('lt.queue')
                ->with('error', 'That entry is no longer awaiting approval.');
        }

        $approval->sendBack($entry, $request->user('lt'), $data['note']);

        return redirect()->route('lt.queue')
            ->with('success', "{$entry->team->name} · Meeting {$entry->meeting->sequence_no} sent back.");
    }
}
