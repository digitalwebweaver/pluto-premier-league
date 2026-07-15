<?php

namespace App\Services;

use App\Exceptions\IllegalTransitionException;
use App\Models\Category;
use App\Models\EntryStatusHistory;
use App\Models\LtUser;
use App\Models\MeetingEntry;
use Illuminate\Support\Facades\DB;

/**
 * The approval state machine (Phase 4 / requirements 07). All entry status
 * transitions go through here so they're validated and audited
 * (`entry_status_history`, FR-APR-008). The server re-verifies points on
 * submit/approve — stored client totals are never trusted (FR-APR-010).
 *
 *   draft ─submit→ submitted ─approve→ approved ─unlock→ submitted
 *   sent_back ─resubmit→ submitted ; submitted ─send back→ sent_back
 *
 * `editByLt` is not a transition (submitted stays submitted) — it lets LT fix
 * a team's mistake directly instead of bouncing it back and waiting, but
 * still requires and audits a reason so nothing changes silently.
 */
class ApprovalService
{
    public function __construct(
        private EntryScoringService $scorer,
        private NotificationService $notifications,
    ) {}

    /** Team submits or resubmits (draft|sent_back → submitted). */
    public function submit(MeetingEntry $entry): MeetingEntry
    {
        $this->assertFrom($entry, [MeetingEntry::DRAFT, MeetingEntry::SENT_BACK]);

        return DB::transaction(function () use ($entry) {
            $this->scorer->recompute($entry);
            $from = $entry->status;
            $entry->update(['status' => MeetingEntry::SUBMITTED, 'submitted_at' => now()]);
            $this->record($entry, $from, MeetingEntry::SUBMITTED, 'team', $entry->team_id);

            return $entry;
        });
    }

    /** LT approves (submitted → approved): recompute, snapshot, lock. */
    public function approve(MeetingEntry $entry, LtUser $lt): MeetingEntry
    {
        $this->assertFrom($entry, [MeetingEntry::SUBMITTED]);

        return DB::transaction(function () use ($entry, $lt) {
            $breakdown = $this->scorer->breakdown($entry); // authoritative (FR-APR-010)
            $from = $entry->status;
            $entry->update([
                'status' => MeetingEntry::APPROVED,
                'computed_total' => $breakdown['total'],
                'points_snapshot' => $breakdown,      // frozen so rule edits never rewrite it (BR-SCO-003)
                'approved_by' => $lt->id,
                'approved_at' => now(),
            ]);
            $this->record($entry, $from, MeetingEntry::APPROVED, 'lt', $lt->id);
            $this->notifications->notifyTeam($entry->team_id, 'approved', [
                'meeting' => $entry->meeting->sequence_no,
                'total' => $breakdown['total'],
            ]);

            return $entry;
        });
    }

    /** LT sends back (submitted → sent_back) with a required note. */
    public function sendBack(MeetingEntry $entry, LtUser $lt, string $note): MeetingEntry
    {
        $this->assertFrom($entry, [MeetingEntry::SUBMITTED]);

        return DB::transaction(function () use ($entry, $lt, $note) {
            $from = $entry->status;
            $entry->update(['status' => MeetingEntry::SENT_BACK, 'sent_back_note' => $note]);
            $this->record($entry, $from, MeetingEntry::SENT_BACK, 'lt', $lt->id, $note);
            $this->notifications->notifyTeam($entry->team_id, 'sent_back', [
                'meeting' => $entry->meeting->sequence_no,
                'note' => $note,
            ]);

            return $entry;
        });
    }

    /**
     * LT corrects a submitted entry's lines/attendance directly (instead of
     * sending it back and waiting on the team) — a reason is required **per
     * changed category** (not one blanket reason), logged to the audit trail
     * and shown to the team, so nothing changes silently. Status stays
     * `submitted`; the corrected entry is recomputed and can then be
     * approved/sent back as normal.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array<string, mixed>>  $attendance
     * @param  array<int, string>  $reasons  category_id => reason
     */
    public function editByLt(MeetingEntry $entry, LtUser $lt, array $lines, array $attendance, array $reasons): MeetingEntry
    {
        $this->assertFrom($entry, [MeetingEntry::SUBMITTED]);

        return DB::transaction(function () use ($entry, $lt, $lines, $attendance, $reasons) {
            $entry->lines()->delete();
            foreach ($lines as $line) {
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
            foreach ($attendance as $mark) {
                $entry->attendance()->create([
                    'member_id' => $mark['member_id'],
                    'is_present' => $mark['is_present'],
                    'is_on_time' => $mark['is_on_time'],
                ]);
            }

            $breakdown = $this->scorer->breakdown($entry); // authoritative recompute

            $categoryNames = Category::whereIn('id', array_keys($reasons))->pluck('name', 'id');
            $reasonLines = collect($reasons)
                ->map(fn ($reason, $categoryId) => sprintf('%s: %s', $categoryNames[$categoryId] ?? "Category #{$categoryId}", $reason))
                ->values()->all();
            $note = implode("\n", $reasonLines);

            // Same status in/out — this is a correction, not a transition — but
            // still goes through the same audit trail as every other change.
            $this->record($entry, $entry->status, $entry->status, 'lt', $lt->id, $note);

            $this->notifications->notifyTeam($entry->team_id, 'corrected', [
                'meeting' => $entry->meeting->sequence_no,
                'reasons' => $reasonLines,
                'total' => $breakdown['total'],
            ]);

            return $entry;
        });
    }

    /** LT unlocks an approved entry back to submitted for correction. */
    public function unlock(MeetingEntry $entry, LtUser $lt): MeetingEntry
    {
        $this->assertFrom($entry, [MeetingEntry::APPROVED]);

        return DB::transaction(function () use ($entry, $lt) {
            $from = $entry->status;
            $entry->update([
                'status' => MeetingEntry::SUBMITTED,
                'approved_by' => null,
                'approved_at' => null,
                'points_snapshot' => null,
            ]);
            $this->record($entry, $from, MeetingEntry::SUBMITTED, 'lt', $lt->id, 'Unlocked for correction');

            return $entry;
        });
    }

    /**
     * @param  list<string>  $allowed
     */
    private function assertFrom(MeetingEntry $entry, array $allowed): void
    {
        if (! in_array($entry->status, $allowed, true)) {
            throw IllegalTransitionException::from($entry->status, 'requested');
        }
    }

    private function record(MeetingEntry $entry, ?string $from, string $to, string $actorType, ?int $actorId, ?string $note = null): void
    {
        EntryStatusHistory::create([
            'meeting_entry_id' => $entry->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
