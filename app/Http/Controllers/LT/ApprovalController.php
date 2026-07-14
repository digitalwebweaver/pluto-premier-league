<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\MeetingEntry;
use App\Services\ApprovalService;
use App\Services\EntryScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT approval queue + read-only review (Phase 4B / FR-APR-001, 002). LT-only
 * (guard:lt). Approve / send-back / unlock actions land in 4C–4D.
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

    /** Read-only review of one entry with server-computed category detail. */
    public function review(MeetingEntry $entry, EntryScoringService $scorer): Response
    {
        $entry->load(['team:id,name,short_code,crest_color', 'meeting:id,sequence_no,meeting_date',
            'lines.category:id,name,code', 'lines.scoringRule:id,subtype_label', 'lines.member:id,name',
            'attendance.member:id,name']);

        $breakdown = $scorer->breakdown($entry); // authoritative recompute (FR-APR-010)

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
            'categories' => $breakdown['categories'],
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
            'attendance' => [
                'present' => $entry->attendance->where('is_present', true)->count(),
                'absent' => $entry->attendance->where('is_present', false)->count(),
                'late' => $entry->attendance->where('is_on_time', false)->count(),
                'total' => $entry->attendance->count(),
            ],
        ]);
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
