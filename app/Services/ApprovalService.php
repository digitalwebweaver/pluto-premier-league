<?php

namespace App\Services;

use App\Exceptions\IllegalTransitionException;
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
