<?php

namespace Tests\Feature\Approval;

use App\Models\Category;
use App\Models\EntryStatusHistory;
use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Member;
use App\Models\Notification;
use App\Models\ScoringRule;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LT can correct a submitted entry directly instead of sending it back and
 * waiting on the team — a required reason is audited and the team notified
 * (owner request, not a numbered requirement).
 */
class LtEditEntryTest extends TestCase
{
    use RefreshDatabase;

    /** A submitted entry (open meeting) with a captain + one Hot visitor line. */
    private function submitted(): array
    {
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->open()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);
        $vis = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE]);
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'subtype_label' => 'Hot', 'points' => 300]);
        $open = ScoringRule::factory()->create(['category_id' => $vis->id, 'subtype_label' => 'Open', 'points' => 200]);
        $meeting->categories()->attach($vis->id);
        $member = Member::factory()->create(['team_id' => $team->id]);

        $entry = MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $meeting->id, 'computed_total' => 300]);
        $entry->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 1, 'computed_points' => 300]);

        return [$captain, $team, $meeting, $entry, $vis, $hot, $open, $member];
    }

    public function test_lt_corrects_a_line_with_a_reason_and_total_recomputes(): void
    {
        [, $team, , $entry, $vis, , $open, $member] = $this->submitted();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->put("/lt/queue/{$entry->id}", [
                'reason' => 'Was logged Hot but is actually Open — corrected the subtype.',
                'lines' => [
                    ['category_id' => $vis->id, 'scoring_rule_id' => $open->id, 'member_id' => $member->id, 'count' => 1],
                ],
                'attendance' => [],
            ])
            ->assertRedirect(route('lt.queue.review', $entry));

        $fresh = $entry->fresh();
        $this->assertSame(200, $fresh->computed_total); // Open (200) not Hot (300)
        $this->assertSame(MeetingEntry::SUBMITTED, $fresh->status); // not a status transition
        $this->assertCount(1, $fresh->lines);
        $this->assertSame($open->id, $fresh->lines->first()->scoring_rule_id);
    }

    public function test_reason_is_required(): void
    {
        [, , , $entry, $vis, $hot, , $member] = $this->submitted();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->put("/lt/queue/{$entry->id}", [
                'reason' => '',
                'lines' => [
                    ['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 2],
                ],
                'attendance' => [],
            ])
            ->assertSessionHasErrors('reason');

        $this->assertSame(300, $entry->fresh()->computed_total); // unchanged
    }

    public function test_edit_is_audited_and_the_team_is_notified(): void
    {
        [, $team, , $entry, $vis, , $open, $member] = $this->submitted();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->put("/lt/queue/{$entry->id}", [
                'reason' => 'Fixed the subtype.',
                'lines' => [
                    ['category_id' => $vis->id, 'scoring_rule_id' => $open->id, 'member_id' => $member->id, 'count' => 1],
                ],
                'attendance' => [],
            ]);

        $history = EntryStatusHistory::where('meeting_entry_id', $entry->id)->latest('id')->first();
        $this->assertSame('lt', $history->actor_type);
        $this->assertSame('submitted', $history->from_status);
        $this->assertSame('submitted', $history->to_status);
        $this->assertSame('Fixed the subtype.', $history->note);

        $notification = Notification::where('team_id', $team->id)->where('type', 'corrected')->first();
        $this->assertNotNull($notification);
        $this->assertSame('Fixed the subtype.', $notification->payload['reason']);
        $this->assertSame(200, $notification->payload['total']);
    }

    public function test_lt_cannot_edit_a_non_submitted_entry(): void
    {
        [, , , $entry] = $this->submitted();
        $lt = LtUser::factory()->create();
        $this->actingAs($lt, 'lt')->post("/lt/queue/{$entry->id}/approve"); // now approved

        $this->actingAs($lt, 'lt')
            ->put("/lt/queue/{$entry->id}", ['reason' => 'too late', 'lines' => [], 'attendance' => []])
            ->assertRedirect(route('lt.queue'))
            ->assertSessionHas('error');

        $this->assertSame(MeetingEntry::APPROVED, $entry->fresh()->status);
    }

    public function test_cannot_assign_a_member_from_another_team(): void
    {
        [, , , $entry, $vis, $hot] = $this->submitted();
        $stranger = Member::factory()->create(['team_id' => Team::factory()->create()->id]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->put("/lt/queue/{$entry->id}", [
                'reason' => 'testing cross-team guard',
                'lines' => [
                    ['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $stranger->id, 'count' => 1],
                ],
                'attendance' => [],
            ])
            ->assertSessionHasErrors('lines.0.member_id');
    }

    public function test_captain_cannot_reach_the_lt_edit_route(): void
    {
        [$captain, , , $entry] = $this->submitted();

        $this->actingAs($captain, 'team')
            ->put("/lt/queue/{$entry->id}", ['reason' => 'x', 'lines' => [], 'attendance' => []])
            ->assertForbidden();
    }
}
