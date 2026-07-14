<?php

namespace Tests\Feature\Public;

use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_need_no_login(): void
    {
        Season::factory()->active()->create();

        $this->get('/public/league')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Public/League'));
        $this->get('/public/season')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Public/Season'));
        $this->get('/public/live')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Public/Live'));
    }

    public function test_public_league_shows_only_approved_totals(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $m2 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 2]);
        $team = Team::factory()->create(['name' => 'Alpha']);

        MeetingEntry::factory()->approved()->create(['team_id' => $team->id, 'meeting_id' => $m1->id, 'computed_total' => 300]);
        // A SUBMITTED entry must NOT leak into the public total (BR-PUB-002).
        MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $m2->id, 'computed_total' => 999]);

        $this->get('/public/league')->assertInertia(fn (Assert $p) => $p
            ->where('rows.0.total', 300) // 999 excluded
        );
    }

    public function test_public_payload_carries_no_private_detail(): void
    {
        $season = Season::factory()->active()->create();
        $m1 = Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        $team = Team::factory()->create();
        MeetingEntry::factory()->approved()->create(['team_id' => $team->id, 'meeting_id' => $m1->id, 'computed_total' => 300]);

        // Public rows expose only standings-safe fields — no workflow/entry detail.
        $this->get('/public/league')->assertInertia(fn (Assert $p) => $p
            ->where('rows.0.total', 300)
            ->has('rows.0.team.name')
            ->missing('rows.0.status')
            ->missing('rows.0.points_snapshot')
            ->missing('rows.0.sent_back_note')
            ->missing('rows.0.submitted_at')
            ->missing('rows.0.team.status')
        );
    }
}
