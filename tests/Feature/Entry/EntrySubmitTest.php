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

class EntrySubmitTest extends TestCase
{
    use RefreshDatabase;

    private function base(): array
    {
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->open()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);

        return [$captain, $team, $meeting];
    }

    private function attach(Meeting $meeting, Category $c): Category
    {
        $meeting->categories()->attach($c->id);

        return $c;
    }

    // ---- binary_flat ----

    public function test_binary_awards_flat_when_on(): void
    {
        [$captain, $team, $meeting] = $this->base();
        $gold = $this->attach($meeting, Category::factory()->create(['input_shape' => Category::BINARY_FLAT, 'code' => 'GLD']));
        $rule = ScoringRule::factory()->create(['category_id' => $gold->id, 'points' => 200]);

        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [['category_id' => $gold->id, 'scoring_rule_id' => $rule->id, 'count' => 1]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(200, MeetingEntry::firstWhere(['team_id' => $team->id, 'meeting_id' => $meeting->id])->computed_total);
    }

    public function test_binary_off_scores_zero(): void
    {
        [$captain, $team, $meeting] = $this->base();
        $gold = $this->attach($meeting, Category::factory()->create(['input_shape' => Category::BINARY_FLAT, 'code' => 'GLD']));
        ScoringRule::factory()->create(['category_id' => $gold->id, 'points' => 200]);

        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", ['lines' => []]);

        $this->assertSame(0, MeetingEntry::firstWhere(['team_id' => $team->id, 'meeting_id' => $meeting->id])->computed_total);
    }

    // ---- conditional_multiplier (Trainings) ----

    public function test_training_base_and_doubled(): void
    {
        [$captain, $team, $meeting] = $this->base();
        $trn = $this->attach($meeting, Category::factory()->create(['input_shape' => Category::CONDITIONAL_MULTIPLIER, 'code' => 'TRN']));
        $rule = ScoringRule::factory()->create(['category_id' => $trn->id, 'points' => 50, 'extra_params' => ['multiplier' => 2]]);

        // 4 present, not whole team → 4 × 50 = 200
        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [['category_id' => $trn->id, 'scoring_rule_id' => $rule->id, 'count' => 4, 'whole_team' => false]],
        ]);
        $this->assertSame(200, MeetingEntry::firstWhere(['team_id' => $team->id, 'meeting_id' => $meeting->id])->computed_total);

        // 4 present, whole team → 4 × 100 = 400
        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [['category_id' => $trn->id, 'scoring_rule_id' => $rule->id, 'count' => 4, 'whole_team' => true]],
        ]);
        $this->assertSame(400, MeetingEntry::firstWhere(['team_id' => $team->id, 'meeting_id' => $meeting->id])->fresh()->computed_total);
    }

    // ---- Review + authoritative submit ----

    public function test_review_recomputes_and_renders_breakdown(): void
    {
        [$captain, , $meeting] = $this->base();
        $vis = $this->attach($meeting, Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE, 'code' => 'VIS']));
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $member = Member::factory()->create(['team_id' => $captain->team_id]);

        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 2]],
        ]);

        $this->actingAs($captain, 'team')->get("/team/submit/{$meeting->id}/review")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Team/Review')->where('total', 600)->has('categories', 1));
    }

    public function test_submit_sets_status_and_uses_server_total_not_client(): void
    {
        [$captain, $team, $meeting] = $this->base();
        $vis = $this->attach($meeting, Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE, 'code' => 'VIS']));
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $member = Member::factory()->create(['team_id' => $team->id]);

        // Save 1 Hot = 300.
        $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'lines' => [['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 1]],
        ]);

        // Submit — even if a bogus client total were sent, the server ignores it.
        $this->actingAs($captain, 'team')->post("/team/submit/{$meeting->id}/submit", [
            'computed_total' => 999999, // ignored
        ])->assertRedirect(route('team.submit'));

        $entry = MeetingEntry::firstWhere(['team_id' => $team->id, 'meeting_id' => $meeting->id])->fresh();
        $this->assertSame(MeetingEntry::SUBMITTED, $entry->status);
        $this->assertNotNull($entry->submitted_at);
        $this->assertSame(300, $entry->computed_total); // server value wins (BR-ENT-001)
    }

    public function test_submit_requires_attendance_when_roster_applies(): void
    {
        [$captain, $team, $meeting] = $this->base();
        $att = $this->attach($meeting, Category::factory()->create(['input_shape' => Category::ROSTER_FLAT_PENALTY, 'code' => 'ATT']));
        ScoringRule::factory()->create(['category_id' => $att->id, 'extra_params' => ['flat' => 300, 'penalty' => -200, 'metric' => 'present']]);
        // Create the draft with NO attendance marks.
        MeetingEntry::create(['team_id' => $team->id, 'meeting_id' => $meeting->id, 'status' => 'draft']);

        $this->actingAs($captain, 'team')->post("/team/submit/{$meeting->id}/submit")
            ->assertSessionHasErrors('attendance');

        $this->assertSame('draft', MeetingEntry::firstWhere(['team_id' => $team->id, 'meeting_id' => $meeting->id])->status);
    }

    public function test_cannot_submit_a_closed_meeting(): void
    {
        [$captain, $team] = $this->base();
        $season = Season::factory()->active()->create();
        $closed = Meeting::factory()->closed()->create(['season_id' => $season->id]);
        MeetingEntry::create(['team_id' => $team->id, 'meeting_id' => $closed->id, 'status' => 'draft']);

        $this->actingAs($captain, 'team')->post("/team/submit/{$closed->id}/submit")->assertForbidden();
    }
}
