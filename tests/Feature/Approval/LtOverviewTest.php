<?php

namespace Tests\Feature\Approval;

use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The LT overview dashboard reads real data (was a static placeholder) —
 * pending-approval count, active teams, meeting counts, the standings
 * leader, and preview lists of what's awaiting review / recently approved.
 */
class LtOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_kpis_reflect_real_counts(): void
    {
        $season = Season::factory()->active()->create();
        Meeting::factory()->open()->create(['season_id' => $season->id]);
        Meeting::factory()->closed()->create(['season_id' => $season->id]);
        $teams = Team::factory()->count(3)->create(['is_active' => true]);
        Team::factory()->create(['is_active' => false]);
        // Reuse the active teams above so no extra (auto-factoried) teams sneak in.
        MeetingEntry::factory()->submitted()->create(['team_id' => $teams[0]->id]);
        MeetingEntry::factory()->submitted()->create(['team_id' => $teams[1]->id]);
        MeetingEntry::factory()->approved()->create(['team_id' => $teams[2]->id]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('LT/Overview')
                ->where('kpis.pending_approvals', 2)
                ->where('kpis.active_teams', 3)
                ->where('kpis.meetings_open', 1)
                ->where('kpis.meetings_total', 2)
            );
    }

    public function test_needs_attention_lists_submitted_entries_oldest_first(): void
    {
        $old = MeetingEntry::factory()->submitted()->create(['submitted_at' => now()->subDay()]);
        $new = MeetingEntry::factory()->submitted()->create(['submitted_at' => now()]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt')
            ->assertInertia(fn ($p) => $p
                ->component('LT/Overview')
                ->has('needsAttention', 2)
                ->where('needsAttention.0.id', $old->id)
                ->where('needsAttention.1.id', $new->id)
            );
    }

    public function test_recently_approved_shows_the_approver(): void
    {
        $lt = LtUser::factory()->create(['name' => 'Leadership Admin']);
        MeetingEntry::factory()->approved()->create(['approved_by' => $lt->id, 'approved_at' => now()]);

        $this->actingAs($lt, 'lt')
            ->get('/lt')
            ->assertInertia(fn ($p) => $p
                ->component('LT/Overview')
                ->has('recentlyApproved', 1)
                ->where('recentlyApproved.0.approved_by', 'Leadership Admin')
            );
    }

    public function test_captain_cannot_reach_the_lt_overview(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')->get('/lt')->assertForbidden();
    }
}
