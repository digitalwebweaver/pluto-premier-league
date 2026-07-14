<?php

namespace Tests\Feature\Standings;

use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use App\Services\StandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function svc(): StandingsService
    {
        return app(StandingsService::class);
    }

    public function test_grid_cells_show_approved_points_and_pending_as_null(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $m2 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 2]);
        $team = Team::factory()->create();

        // Meeting 1 approved (0 pts — a real scored zero); meeting 2 not scored.
        MeetingEntry::factory()->approved()->create(['team_id' => $team->id, 'meeting_id' => $m1->id, 'computed_total' => 0]);

        $row = collect($this->svc()->seasonGrid($season)['rows'])->firstWhere('team.id', $team->id);
        $this->assertSame(0, $row['cells'][0]['points']);   // scored 0 (not pending)
        $this->assertNull($row['cells'][1]['points']);       // pending (—), NOT 0
        $this->assertSame(0, $row['total']);
    }

    public function test_season_total_sums_only_approved(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $m2 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 2]);
        $team = Team::factory()->create();

        MeetingEntry::factory()->approved()->create(['team_id' => $team->id, 'meeting_id' => $m1->id, 'computed_total' => 300]);
        MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $m2->id, 'computed_total' => 999]);

        $row = collect($this->svc()->seasonGrid($season)['rows'])->firstWhere('team.id', $team->id);
        $this->assertSame(300, $row['total']); // 999 (submitted) excluded
    }

    public function test_leader_is_flagged_champion(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $a = Team::factory()->create(['name' => 'Alpha']);
        $b = Team::factory()->create(['name' => 'Bravo']);
        MeetingEntry::factory()->approved()->create(['team_id' => $a->id, 'meeting_id' => $m1->id, 'computed_total' => 100]);
        MeetingEntry::factory()->approved()->create(['team_id' => $b->id, 'meeting_id' => $m1->id, 'computed_total' => 400]);

        $rows = $this->svc()->seasonGrid($season)['rows'];
        $this->assertSame('Bravo', $rows[0]['team']['name']);
        $this->assertTrue($rows[0]['is_champion']);
        $this->assertFalse($rows[1]['is_champion']);
    }

    public function test_season_route_renders_for_both_guards(): void
    {
        Season::factory()->active()->create();

        $this->get('/season')->assertRedirect(route('login')); // guest

        $this->actingAs(TeamUser::factory()->create(), 'team')->get('/season')
            ->assertOk()->assertInertia(fn ($p) => $p->component('Season')->where('role', 'captain'));

        $this->actingAs(LtUser::factory()->create(), 'lt')->get('/season')
            ->assertOk()->assertInertia(fn ($p) => $p->where('role', 'lt'));
    }
}
