<?php

namespace Tests\Feature\Standings;

use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use App\Services\ApprovalService;
use App\Services\StandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandingsTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): StandingsService
    {
        return app(StandingsService::class);
    }

    private function approvedEntry(Team $team, Meeting $meeting, int $total): MeetingEntry
    {
        return MeetingEntry::factory()->approved()->create([
            'team_id' => $team->id, 'meeting_id' => $meeting->id, 'computed_total' => $total,
        ]);
    }

    public function test_only_approved_entries_count(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $team = Team::factory()->create();

        $this->approvedEntry($team, $m1, 300);
        // A submitted (unapproved) entry for a second meeting must NOT count.
        $m2 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 2]);
        MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $m2->id, 'computed_total' => 999]);

        $row = collect($this->svc()->forSeason($season)['rows'])->firstWhere('team.id', $team->id);
        $this->assertSame(300, $row['total']); // 999 excluded
        $this->assertSame(1, $row['meetings_approved']);
    }

    public function test_ranking_orders_by_total_desc_with_rings(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $a = Team::factory()->create(['name' => 'Alpha']);
        $b = Team::factory()->create(['name' => 'Bravo']);
        $c = Team::factory()->create(['name' => 'Charlie']);
        $this->approvedEntry($a, $m1, 100);
        $this->approvedEntry($b, $m1, 300);
        $this->approvedEntry($c, $m1, 200);

        $rows = $this->svc()->forSeason($season)['rows'];
        $this->assertSame(['Bravo', 'Charlie', 'Alpha'], array_map(fn ($r) => $r['team']['name'], $rows));
        $this->assertSame(['gold', 'silver', 'bronze'], array_map(fn ($r) => $r['ring'], $rows));
    }

    public function test_tiebreak_more_meetings_then_alphabetical(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $m2 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 2]);
        $x = Team::factory()->create(['name' => 'Xray']);
        $y = Team::factory()->create(['name' => 'Yankee']);

        // Both total 300, but Yankee got there over 2 approved meetings.
        $this->approvedEntry($x, $m1, 300);
        $this->approvedEntry($y, $m1, 150);
        $this->approvedEntry($y, $m2, 150);

        $rows = $this->svc()->forSeason($season)['rows'];
        $this->assertSame('Yankee', $rows[0]['team']['name']); // more meetings approved wins
    }

    public function test_viewer_team_row_is_flagged_current(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $team = Team::factory()->create();
        $this->approvedEntry($team, $m1, 100);

        $rows = $this->svc()->forSeason($season, $team->id)['rows'];
        $this->assertTrue(collect($rows)->firstWhere('team.id', $team->id)['is_current']);
    }

    public function test_dots_reflect_approved_meetings(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $m2 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 2]);
        $team = Team::factory()->create();
        $this->approvedEntry($team, $m1, 100); // only meeting 1 approved

        $row = collect($this->svc()->forSeason($season)['rows'])->firstWhere('team.id', $team->id);
        $this->assertTrue($row['dots'][0]['approved']);
        $this->assertFalse($row['dots'][1]['approved']);
    }

    public function test_table_recomputes_on_approve_and_unlock(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->open()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $team = Team::factory()->create();

        // A real line worth 250, so the authoritative recompute on approve yields 250.
        $cat = \App\Models\Category::factory()->create(['input_shape' => \App\Models\Category::COUNT_SUBTYPE]);
        $rule = \App\Models\ScoringRule::factory()->create(['category_id' => $cat->id, 'points' => 250]);
        $m1->categories()->attach($cat->id);
        $entry = MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $m1->id]);
        $entry->lines()->create(['category_id' => $cat->id, 'scoring_rule_id' => $rule->id, 'count' => 1]);
        $approval = app(ApprovalService::class);

        // Submitted → not counted.
        $row = collect($this->svc()->forSeason($season)['rows'])->firstWhere('team.id', $team->id);
        $this->assertSame(0, $row['total']);

        // Approve → counted.
        $approval->approve($entry->fresh(), LtUser::factory()->create());
        $row = collect($this->svc()->forSeason($season)['rows'])->firstWhere('team.id', $team->id);
        $this->assertSame(250, $row['total']);

        // Unlock → no longer counted.
        $approval->unlock($entry->fresh(), LtUser::factory()->create());
        $row = collect($this->svc()->forSeason($season)['rows'])->firstWhere('team.id', $team->id);
        $this->assertSame(0, $row['total']);
    }

    public function test_league_route_renders_for_both_guards(): void
    {
        Season::factory()->active()->create();

        // Guest first (actingAs persists within a test).
        $this->get('/league')->assertRedirect(route('login'));

        $this->actingAs(TeamUser::factory()->create(), 'team')->get('/league')
            ->assertOk()->assertInertia(fn ($p) => $p->component('League')->where('role', 'captain'));

        $this->actingAs(LtUser::factory()->create(), 'lt')->get('/league')
            ->assertOk()->assertInertia(fn ($p) => $p->where('role', 'lt'));
    }
}
