<?php

namespace Tests\Feature\Entry;

use App\Models\Category;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Member;
use App\Models\ScoringRule;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryLinesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a captain + open meeting with Visitors (Hot 300, Closed 50) applicable.
     *
     * @return array{0: TeamUser, 1: Team, 2: Meeting, 3: Category, 4: ScoringRule, 5: ScoringRule, 6: Member}
     */
    private function scenario(): array
    {
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->open()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);

        $visitors = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE, 'is_active' => true]);
        $hot = ScoringRule::factory()->create(['category_id' => $visitors->id, 'subtype_label' => 'Hot', 'points' => 300]);
        $closed = ScoringRule::factory()->create(['category_id' => $visitors->id, 'subtype_label' => 'Closed', 'points' => 50]);
        $meeting->categories()->attach($visitors->id);

        $member = Member::factory()->create(['team_id' => $team->id, 'is_active' => true]);

        return [$captain, $team, $meeting, $visitors, $hot, $closed, $member];
    }

    public function test_scorecard_payload_includes_scoped_rules_and_members(): void
    {
        [$captain, $team, $meeting, $visitors, , , $member] = $this->scenario();
        // A member on another team must NOT appear.
        Member::factory()->create();

        $this->actingAs($captain, 'team')->get("/team/submit/{$meeting->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->has('categories', 1)
                ->has('categories.0.rules', 2)
                ->has('members', 1) // only own-team active member
            );
    }

    public function test_saving_hot_visitor_x1_persists_a_line_worth_300(): void
    {
        [$captain, $team, $meeting, $visitors, $hot, , $member] = $this->scenario();

        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [[
                'category_id' => $visitors->id,
                'scoring_rule_id' => $hot->id,
                'member_id' => $member->id,
                'count' => 1,
            ]],
        ])->assertSessionHasNoErrors();

        $entry = MeetingEntry::where('team_id', $team->id)->where('meeting_id', $meeting->id)->first();
        $this->assertDatabaseHas('entry_lines', [
            'meeting_entry_id' => $entry->id,
            'scoring_rule_id' => $hot->id,
            'count' => 1,
            'computed_points' => 300, // server-computed
        ]);
        $this->assertSame(300, $entry->fresh()->computed_total);
    }

    public function test_multiple_rows_sum_including_same_member(): void
    {
        [$captain, $team, $meeting, $visitors, $hot, $closed, $member] = $this->scenario();

        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [
                ['category_id' => $visitors->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 2], // 600
                ['category_id' => $visitors->id, 'scoring_rule_id' => $closed->id, 'member_id' => $member->id, 'count' => 3], // 150
            ],
        ]);

        $entry = MeetingEntry::firstWhere(['team_id' => $team->id, 'meeting_id' => $meeting->id]);
        $this->assertSame(750, $entry->fresh()->computed_total);
        $this->assertSame(2, $entry->lines()->count());
    }

    public function test_resaving_replaces_previous_lines(): void
    {
        [$captain, $team, $meeting, $visitors, $hot, $closed, $member] = $this->scenario();
        $entry = MeetingEntry::create(['team_id' => $team->id, 'meeting_id' => $meeting->id, 'status' => 'draft']);

        $put = fn (array $lines) => $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", ['lines' => $lines]);

        $put([['category_id' => $visitors->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 5]]); // 1500
        $this->assertSame(1500, $entry->fresh()->computed_total);

        $put([['category_id' => $visitors->id, 'scoring_rule_id' => $closed->id, 'member_id' => $member->id, 'count' => 1]]); // 50
        $this->assertSame(1, $entry->fresh()->lines()->count());
        $this->assertSame(50, $entry->fresh()->computed_total);
    }

    public function test_zero_count_rows_are_dropped(): void
    {
        [$captain, $team, $meeting, $visitors, $hot, , $member] = $this->scenario();

        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [['category_id' => $visitors->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 0]],
        ]);

        $entry = MeetingEntry::firstWhere(['team_id' => $team->id, 'meeting_id' => $meeting->id]);
        $this->assertSame(0, $entry->lines()->count());
        $this->assertSame(0, $entry->computed_total);
    }

    public function test_cannot_use_another_teams_member(): void
    {
        [$captain, , $meeting, $visitors, $hot, , ] = $this->scenario();
        $foreignMember = Member::factory()->create(); // other team

        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [['category_id' => $visitors->id, 'scoring_rule_id' => $hot->id, 'member_id' => $foreignMember->id, 'count' => 1]],
        ])->assertSessionHasErrors('lines.0.member_id');
    }

    public function test_cannot_use_a_rule_from_a_different_category(): void
    {
        [$captain, , $meeting, $visitors, , , $member] = $this->scenario();
        $otherCat = Category::factory()->create();
        $otherRule = ScoringRule::factory()->create(['category_id' => $otherCat->id]);

        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [['category_id' => $visitors->id, 'scoring_rule_id' => $otherRule->id, 'member_id' => $member->id, 'count' => 1]],
        ])->assertSessionHasErrors('lines.0.scoring_rule_id');
    }

    public function test_cannot_save_when_meeting_closed(): void
    {
        [$captain, $team, , $visitors, $hot, , $member] = $this->scenario();
        $season = Season::factory()->active()->create();
        $closedMeeting = Meeting::factory()->closed()->create(['season_id' => $season->id]);
        MeetingEntry::create(['team_id' => $team->id, 'meeting_id' => $closedMeeting->id, 'status' => 'draft']);

        $this->actingAs($captain, 'team')->put("/team/submit/{$closedMeeting->id}", [
            'lines' => [['category_id' => $visitors->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 1]],
        ])->assertForbidden();
    }
}
