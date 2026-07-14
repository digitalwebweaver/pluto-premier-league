<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Member;
use App\Models\ScoringRule;
use App\Models\Season;
use App\Models\Team;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MvpLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    /** Two teams, each with an approved entry + member lines. */
    private function scenario(): array
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $vis = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE, 'code' => 'VIS']);
        $ref = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE, 'code' => 'REF']);
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $refRule = ScoringRule::factory()->create(['category_id' => $ref->id, 'points' => 100]);

        $teamA = Team::factory()->create(['name' => 'Alpha']);
        $teamB = Team::factory()->create(['name' => 'Bravo']);
        $pat = Member::factory()->create(['team_id' => $teamA->id, 'name' => 'P. Rao']);
        $sam = Member::factory()->create(['team_id' => $teamB->id, 'name' => 'S. Kapoor']);

        $eA = MeetingEntry::factory()->approved()->create(['team_id' => $teamA->id, 'meeting_id' => $m1->id]);
        $eB = MeetingEntry::factory()->approved()->create(['team_id' => $teamB->id, 'meeting_id' => $m1->id]);

        // P. Rao: 2 Hot visitors (600) + 1 referral (100) = 700
        $eA->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $pat->id, 'count' => 2, 'computed_points' => 600]);
        $eA->lines()->create(['category_id' => $ref->id, 'scoring_rule_id' => $refRule->id, 'member_id' => $pat->id, 'count' => 1, 'computed_points' => 100]);
        // S. Kapoor: 1 Hot visitor (300)
        $eB->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $sam->id, 'count' => 1, 'computed_points' => 300]);

        return compact('season');
    }

    public function test_mvp_leaderboard_aggregates_across_teams(): void
    {
        ['season' => $season] = $this->scenario();

        $leaders = app(ReportService::class)->mvpLeaderboard($season);

        $this->assertSame('P. Rao', $leaders[0]['name']);   // 700 total across categories
        $this->assertSame(700, $leaders[0]['points']);
        $this->assertSame('S. Kapoor', $leaders[1]['name']); // 300
        $this->assertSame(300, $leaders[1]['points']);
    }

    public function test_mvp_can_filter_by_category(): void
    {
        ['season' => $season] = $this->scenario();

        // Filter to Visitors only: P. Rao 600, S. Kapoor 300.
        $vis = app(ReportService::class)->mvpLeaderboard($season, 'VIS');
        $this->assertSame(600, collect($vis)->firstWhere('name', 'P. Rao')['points']);

        // Filter to Referrals only: just P. Rao 100.
        $ref = app(ReportService::class)->mvpLeaderboard($season, 'REF');
        $this->assertCount(1, $ref);
        $this->assertSame(100, $ref[0]['points']);
    }

    public function test_only_approved_lines_count(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $vis = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE, 'code' => 'VIS']);
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $team = Team::factory()->create();
        $member = Member::factory()->create(['team_id' => $team->id]);

        // A SUBMITTED (unapproved) entry's lines must not count.
        $entry = MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $m1->id]);
        $entry->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 5, 'computed_points' => 1500]);

        $this->assertEmpty(app(ReportService::class)->mvpLeaderboard($season));
    }

    public function test_mvp_route_renders_for_lt(): void
    {
        $this->scenario();

        $this->actingAs(LtUser::factory()->create(), 'lt')->get('/lt/reports/mvp')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('LT/Reports/Mvp')->has('leaders', 2));
    }
}
