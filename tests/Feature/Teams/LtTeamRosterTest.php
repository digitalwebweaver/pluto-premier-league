<?php

namespace Tests\Feature\Teams;

use App\Models\LtUser;
use App\Models\Member;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LT can manage any team's roster (owner request) — mirrors the captain's
 * own RosterController, but LT is not scoped to one team, so every route
 * carries the target {team} and any team can be reached.
 */
class LtTeamRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_lt_sees_a_specific_teams_roster(): void
    {
        $team = Team::factory()->create();
        Member::factory()->count(3)->create(['team_id' => $team->id]);
        Member::factory()->count(2)->create(); // a different team

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get("/lt/teams/{$team->id}/roster")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('LT/Teams/Roster')->has('members', 3));
    }

    public function test_lt_adds_a_member_to_any_team(): void
    {
        $team = Team::factory()->create();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post("/lt/teams/{$team->id}/roster", [
                'name' => 'New Member',
                'business_category' => 'Legal Services',
                'avatar_color' => '#3F8F6B',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('members', [
            'team_id' => $team->id,
            'name' => 'New Member',
            'is_active' => true,
        ]);
    }

    public function test_lt_edits_a_members_details(): void
    {
        $team = Team::factory()->create();
        $member = Member::factory()->create(['team_id' => $team->id, 'name' => 'Old']);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->put("/lt/teams/{$team->id}/roster/{$member->id}", [
                'name' => 'Updated',
                'business_category' => 'Real Estate',
                'avatar_color' => '#B5473A',
            ])->assertRedirect(route('lt.teams.roster', $team));

        $this->assertSame('Updated', $member->fresh()->name);
    }

    public function test_lt_deactivates_a_member_without_deleting(): void
    {
        $team = Team::factory()->create();
        $member = Member::factory()->create(['team_id' => $team->id, 'is_active' => true]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->patch("/lt/teams/{$team->id}/roster/{$member->id}/toggle");

        $this->assertFalse($member->fresh()->is_active);
        $this->assertDatabaseHas('members', ['id' => $member->id]); // BR-MBR-001
    }

    /** A member id from a different team can't be reached through this team's URL. */
    public function test_member_must_belong_to_the_url_team(): void
    {
        $team = Team::factory()->create();
        $otherTeamsMember = Member::factory()->create(['name' => 'Theirs']);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get("/lt/teams/{$team->id}/roster/{$otherTeamsMember->id}/edit")
            ->assertNotFound();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->put("/lt/teams/{$team->id}/roster/{$otherTeamsMember->id}", [
                'name' => 'Hijacked',
                'avatar_color' => '#000000',
            ])->assertNotFound();

        $this->assertSame('Theirs', $otherTeamsMember->fresh()->name);
    }

    public function test_captain_cannot_reach_lt_team_roster_management(): void
    {
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);

        $this->actingAs($captain, 'team')->get("/lt/teams/{$team->id}/roster")->assertForbidden();
    }
}
