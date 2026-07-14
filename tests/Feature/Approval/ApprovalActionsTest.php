<?php

namespace Tests\Feature\Approval;

use App\Models\Category;
use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Member;
use App\Models\ScoringRule;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalActionsTest extends TestCase
{
    use RefreshDatabase;

    /** A submitted entry (open meeting) with a captain + one Hot line. */
    private function submitted(): array
    {
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->open()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);
        $vis = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE]);
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $meeting->categories()->attach($vis->id);
        $member = Member::factory()->create(['team_id' => $team->id]);

        $entry = MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $meeting->id]);
        $entry->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 1]);

        return [$captain, $team, $meeting, $entry, $hot];
    }

    public function test_lt_approves_and_the_entry_locks_to_the_team(): void
    {
        [$captain, , $meeting, $entry] = $this->submitted();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post("/lt/queue/{$entry->id}/approve")
            ->assertRedirect(route('lt.queue'));

        $fresh = $entry->fresh();
        $this->assertSame(MeetingEntry::APPROVED, $fresh->status);
        $this->assertSame(300, $fresh->points_snapshot['total']);

        // Team can no longer edit or submit an approved entry (BR-APR-001).
        $this->actingAs($captain, 'team')
            ->put("/team/submit/{$meeting->id}", ['lines' => []])
            ->assertForbidden();
        $this->actingAs($captain, 'team')
            ->post("/team/submit/{$meeting->id}/submit")
            ->assertForbidden();
    }

    public function test_send_back_requires_a_note(): void
    {
        [, , , $entry] = $this->submitted();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post("/lt/queue/{$entry->id}/send-back", ['note' => ''])
            ->assertSessionHasErrors('note');

        $this->assertSame(MeetingEntry::SUBMITTED, $entry->fresh()->status);
    }

    public function test_send_back_returns_to_team_with_note_and_can_resubmit(): void
    {
        [$captain, , $meeting, $entry] = $this->submitted();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post("/lt/queue/{$entry->id}/send-back", ['note' => 'Fix visitor counts.'])
            ->assertRedirect(route('lt.queue'));

        $fresh = $entry->fresh();
        $this->assertSame(MeetingEntry::SENT_BACK, $fresh->status);
        $this->assertSame('Fix visitor counts.', $fresh->sent_back_note);

        // Captain sees the note on the scorecard and can edit again.
        $this->actingAs($captain, 'team')->get("/team/submit/{$meeting->id}")
            ->assertInertia(fn ($p) => $p->where('entry.sent_back_note', 'Fix visitor counts.')->where('entry.editable', true));

        // ...and resubmit → back to submitted.
        $this->actingAs($captain, 'team')->post("/team/submit/{$meeting->id}/submit")
            ->assertRedirect(route('team.submit'));
        $this->assertSame(MeetingEntry::SUBMITTED, $entry->fresh()->status);
    }

    public function test_captain_cannot_approve_or_send_back(): void
    {
        [$captain, , , $entry] = $this->submitted();

        $this->actingAs($captain, 'team')->post("/lt/queue/{$entry->id}/approve")->assertForbidden();
        $this->actingAs($captain, 'team')->post("/lt/queue/{$entry->id}/send-back", ['note' => 'x'])->assertForbidden();
    }

    public function test_approving_a_non_submitted_entry_is_rejected_gracefully(): void
    {
        [, , , $entry] = $this->submitted();
        $lt = LtUser::factory()->create();
        $this->actingAs($lt, 'lt')->post("/lt/queue/{$entry->id}/approve"); // now approved

        // A second approve is a no-op with a friendly message (not a 500).
        $this->actingAs($lt, 'lt')->post("/lt/queue/{$entry->id}/approve")
            ->assertRedirect(route('lt.queue'))
            ->assertSessionHas('error');
    }
}
