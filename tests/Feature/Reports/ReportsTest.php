<?php

namespace Tests\Feature\Reports;

use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function approved(Team $team, Meeting $meeting, int $total, array $categories): MeetingEntry
    {
        return MeetingEntry::factory()->approved()->create([
            'team_id' => $team->id, 'meeting_id' => $meeting->id, 'computed_total' => $total,
            'points_snapshot' => ['total' => $total, 'categories' => $categories],
        ]);
    }

    public function test_team_report_aggregates_categories_and_meetings(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $m2 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 2]);
        $team = Team::factory()->create();

        $this->approved($team, $m1, 300, [
            ['id' => 1, 'name' => 'Visitors', 'code' => 'VIS', 'points' => 200],
            ['id' => 2, 'name' => 'Referrals', 'code' => 'REF', 'points' => 100],
        ]);
        $this->approved($team, $m2, 250, [
            ['id' => 1, 'name' => 'Visitors', 'code' => 'VIS', 'points' => 250],
        ]);

        $report = app(ReportService::class)->teamReport($season, $team);

        $this->assertSame(550, $report['total']);
        $vis = collect($report['by_category'])->firstWhere('code', 'VIS');
        $this->assertSame(450, $vis['points']); // 200 + 250
        $this->assertSame(100, collect($report['by_category'])->firstWhere('code', 'REF')['points']);
        $this->assertCount(2, $report['by_meeting']);
    }

    public function test_report_ignores_unapproved_entries(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $team = Team::factory()->create();

        MeetingEntry::factory()->submitted()->create([
            'team_id' => $team->id, 'meeting_id' => $m1->id, 'computed_total' => 999,
            'points_snapshot' => ['total' => 999, 'categories' => [['id' => 1, 'name' => 'Visitors', 'code' => 'VIS', 'points' => 999]]],
        ]);

        $report = app(ReportService::class)->teamReport($season, $team);
        $this->assertSame(0, $report['total']); // submitted excluded
    }

    public function test_category_leaders_finds_top_team_per_category(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $a = Team::factory()->create(['name' => 'Alpha']);
        $b = Team::factory()->create(['name' => 'Bravo']);

        $this->approved($a, $m1, 100, [['id' => 1, 'name' => 'Visitors', 'code' => 'VIS', 'points' => 100]]);
        $this->approved($b, $m1, 300, [['id' => 1, 'name' => 'Visitors', 'code' => 'VIS', 'points' => 300]]);

        $cats = app(ReportService::class)->categoryLeaders($season);
        $vis = collect($cats)->firstWhere('code', 'VIS');

        $this->assertSame(400, $vis['total']);       // 100 + 300
        $this->assertSame('Bravo', $vis['leader']['name']);
        $this->assertSame(300, $vis['leader']['points']);
    }

    public function test_report_routes_are_lt_only(): void
    {
        Season::factory()->active()->create();
        $team = Team::factory()->create();

        $this->actingAs(LtUser::factory()->create(), 'lt')->get('/lt/reports')
            ->assertOk()->assertInertia(fn ($p) => $p->component('LT/Reports/Index'));
        $this->actingAs(LtUser::factory()->create(), 'lt')->get('/lt/reports/categories')->assertOk();
        $this->actingAs(LtUser::factory()->create(), 'lt')->get("/lt/reports/teams/{$team->id}")->assertOk();
    }

    public function test_captain_cannot_reach_reports(): void
    {
        Season::factory()->active()->create();

        $this->actingAs(TeamUser::factory()->create(), 'team')->get('/lt/reports')->assertForbidden();
    }
}
