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
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private function csvBody(TestResponse $res): string
    {
        return $res->streamedContent();
    }

    public function test_standings_csv_downloads_with_data(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $team = Team::factory()->create(['name' => 'Alpha Wolves']);
        MeetingEntry::factory()->approved()->create(['team_id' => $team->id, 'meeting_id' => $m1->id, 'computed_total' => 300]);

        $res = $this->actingAs(LtUser::factory()->create(), 'lt')->get('/lt/exports/standings.csv');

        $res->assertOk();
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $body = $this->csvBody($res);
        $this->assertStringContainsString('Rank', $body);
        $this->assertStringContainsString('Meetings approved', $body);
        $this->assertStringContainsString('Alpha Wolves', $body);
        $this->assertStringContainsString('300', $body);
    }

    public function test_mvp_csv_matches_on_screen_data(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $vis = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE, 'code' => 'VIS']);
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $team = Team::factory()->create();
        $member = Member::factory()->create(['team_id' => $team->id, 'name' => 'P. Rao']);
        $entry = MeetingEntry::factory()->approved()->create(['team_id' => $team->id, 'meeting_id' => $m1->id]);
        $entry->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 2, 'computed_points' => 600]);

        $res = $this->actingAs(LtUser::factory()->create(), 'lt')->get('/lt/exports/mvp.csv');

        $res->assertOk();
        $body = $this->csvBody($res);
        $this->assertStringContainsString('P. Rao', $body);
        $this->assertStringContainsString('600', $body);
    }

    public function test_season_csv_has_meeting_columns(): void
    {
        $season = Season::factory()->active()->create();
        Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 2]);
        Team::factory()->create();

        $res = $this->actingAs(LtUser::factory()->create(), 'lt')->get('/lt/exports/season.csv');
        $res->assertOk();
        $this->assertStringContainsString('Team,M1,M2,Total', $this->csvBody($res));
    }

    public function test_exports_are_lt_only(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/lt/exports/standings.csv')->assertForbidden();
    }
}
