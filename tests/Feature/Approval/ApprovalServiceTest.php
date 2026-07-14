<?php

namespace Tests\Feature\Approval;

use App\Exceptions\IllegalTransitionException;
use App\Models\Category;
use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Member;
use App\Models\ScoringRule;
use App\Models\Team;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): ApprovalService
    {
        return app(ApprovalService::class);
    }

    /** An entry with one Visitors Hot(300) line, ready to submit. */
    private function entryWithLine(string $status = MeetingEntry::DRAFT): array
    {
        $meeting = Meeting::factory()->open()->create();
        $team = Team::factory()->create();
        $vis = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE]);
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $meeting->categories()->attach($vis->id);
        $member = Member::factory()->create(['team_id' => $team->id]);

        $entry = MeetingEntry::factory()->create(['team_id' => $team->id, 'meeting_id' => $meeting->id, 'status' => $status]);
        $entry->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 1]);

        return [$entry, $hot];
    }

    public function test_submit_transitions_draft_to_submitted_with_history(): void
    {
        [$entry] = $this->entryWithLine(MeetingEntry::DRAFT);

        $this->svc()->submit($entry);

        $this->assertSame(MeetingEntry::SUBMITTED, $entry->fresh()->status);
        $this->assertNotNull($entry->fresh()->submitted_at);
        $this->assertSame(300, $entry->fresh()->computed_total); // recomputed
        $this->assertDatabaseHas('entry_status_history', [
            'meeting_entry_id' => $entry->id, 'to_status' => 'submitted', 'actor_type' => 'team',
        ]);
    }

    public function test_approve_snapshots_points_and_locks(): void
    {
        [$entry] = $this->entryWithLine(MeetingEntry::SUBMITTED);
        $lt = LtUser::factory()->create();

        $this->svc()->approve($entry, $lt);

        $fresh = $entry->fresh();
        $this->assertSame(MeetingEntry::APPROVED, $fresh->status);
        $this->assertSame(300, $fresh->computed_total);
        $this->assertSame($lt->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame(300, $fresh->points_snapshot['total']); // snapshot written
        $this->assertDatabaseHas('entry_status_history', ['meeting_entry_id' => $entry->id, 'to_status' => 'approved', 'actor_type' => 'lt']);
    }

    public function test_approved_total_survives_a_later_rule_edit(): void
    {
        // BR-SCO-003 / FR-SCO-012: editing a rule after approval never rewrites history.
        [$entry, $hot] = $this->entryWithLine(MeetingEntry::SUBMITTED);
        $this->svc()->approve($entry, LtUser::factory()->create());
        $this->assertSame(300, $entry->fresh()->computed_total);

        // LT bumps Hot 300 → 999 afterwards.
        $hot->update(['points' => 999]);

        // The approved entry's total is unchanged (snapshot honoured).
        $this->assertSame(300, $entry->fresh()->computed_total);
        $this->assertSame(300, $entry->fresh()->points_snapshot['total']);
    }

    public function test_send_back_sets_note_and_status(): void
    {
        [$entry] = $this->entryWithLine(MeetingEntry::SUBMITTED);

        $this->svc()->sendBack($entry, LtUser::factory()->create(), 'Please fix visitor counts.');

        $fresh = $entry->fresh();
        $this->assertSame(MeetingEntry::SENT_BACK, $fresh->status);
        $this->assertSame('Please fix visitor counts.', $fresh->sent_back_note);
        $this->assertDatabaseHas('entry_status_history', ['meeting_entry_id' => $entry->id, 'to_status' => 'sent_back', 'note' => 'Please fix visitor counts.']);
    }

    public function test_sent_back_can_be_resubmitted(): void
    {
        [$entry] = $this->entryWithLine(MeetingEntry::SENT_BACK);

        $this->svc()->submit($entry); // resubmit

        $this->assertSame(MeetingEntry::SUBMITTED, $entry->fresh()->status);
    }

    public function test_unlock_returns_approved_to_submitted(): void
    {
        [$entry] = $this->entryWithLine(MeetingEntry::SUBMITTED);
        $lt = LtUser::factory()->create();
        $this->svc()->approve($entry, $lt);

        $this->svc()->unlock($entry->fresh(), $lt);

        $fresh = $entry->fresh();
        $this->assertSame(MeetingEntry::SUBMITTED, $fresh->status);
        $this->assertNull($fresh->approved_by);
        $this->assertNull($fresh->points_snapshot);
    }

    public function test_illegal_transitions_are_rejected(): void
    {
        // Cannot approve a draft.
        [$draft] = $this->entryWithLine(MeetingEntry::DRAFT);
        $this->expectException(IllegalTransitionException::class);
        $this->svc()->approve($draft, LtUser::factory()->create());
    }

    public function test_cannot_unlock_a_submitted_entry(): void
    {
        [$entry] = $this->entryWithLine(MeetingEntry::SUBMITTED);
        $this->expectException(IllegalTransitionException::class);
        $this->svc()->unlock($entry, LtUser::factory()->create());
    }

    public function test_cannot_send_back_an_approved_entry(): void
    {
        [$entry] = $this->entryWithLine(MeetingEntry::SUBMITTED);
        $lt = LtUser::factory()->create();
        $this->svc()->approve($entry, $lt);

        $this->expectException(IllegalTransitionException::class);
        $this->svc()->sendBack($entry->fresh(), $lt, 'too late');
    }
}
