<?php

namespace Tests\Feature\Teams;

use App\Models\LtUser;
use App\Models\Member;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterTest extends TestCase
{
    use RefreshDatabase;

    /** A captain with their own team + a member on it. */
    private function captainWithTeam(): array
    {
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);

        return [$captain, $team];
    }

    public function test_captain_sees_only_their_own_roster(): void
    {
        [$captain, $team] = $this->captainWithTeam();
        Member::factory()->count(3)->create(['team_id' => $team->id]);
        Member::factory()->count(2)->create(); // other teams

        $this->actingAs($captain, 'team')->get('/team/roster')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Team/Roster')->has('members', 3));
    }

    public function test_captain_adds_a_member_to_own_team(): void
    {
        [$captain, $team] = $this->captainWithTeam();

        $this->actingAs($captain, 'team')->post('/team/roster', [
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

    public function test_captain_edits_own_member(): void
    {
        [$captain, $team] = $this->captainWithTeam();
        $member = Member::factory()->create(['team_id' => $team->id, 'name' => 'Old']);

        $this->actingAs($captain, 'team')->put("/team/roster/{$member->id}", [
            'name' => 'Updated',
            'business_category' => 'Real Estate',
            'avatar_color' => '#B5473A',
        ])->assertRedirect(route('team.roster'));

        $this->assertSame('Updated', $member->fresh()->name);
    }

    public function test_deactivating_a_member_keeps_the_record(): void
    {
        [$captain, $team] = $this->captainWithTeam();
        $member = Member::factory()->create(['team_id' => $team->id, 'is_active' => true]);

        $this->actingAs($captain, 'team')->patch("/team/roster/{$member->id}/toggle");

        $this->assertFalse($member->fresh()->is_active);
        $this->assertDatabaseHas('members', ['id' => $member->id]); // BR-MBR-001
    }

    public function test_inactive_members_excluded_from_active_scope(): void
    {
        $team = Team::factory()->create();
        Member::factory()->count(2)->create(['team_id' => $team->id, 'is_active' => true]);
        Member::factory()->inactive()->create(['team_id' => $team->id]);

        $this->assertSame(2, Member::forTeam($team->id)->active()->count());
        $this->assertSame(3, Member::forTeam($team->id)->count());
    }

    // --- Cross-team isolation (BR-MBR-003) ---

    public function test_captain_cannot_view_another_teams_member(): void
    {
        [$captain] = $this->captainWithTeam();
        $otherMember = Member::factory()->create(); // different team

        $this->actingAs($captain, 'team')->get("/team/roster/{$otherMember->id}")->assertForbidden();
        $this->actingAs($captain, 'team')->get("/team/roster/{$otherMember->id}/edit")->assertForbidden();
    }

    public function test_captain_cannot_edit_or_toggle_another_teams_member(): void
    {
        [$captain] = $this->captainWithTeam();
        $otherMember = Member::factory()->create(['name' => 'Theirs']);

        $this->actingAs($captain, 'team')->put("/team/roster/{$otherMember->id}", [
            'name' => 'Hijacked',
            'avatar_color' => '#000000',
        ])->assertForbidden();

        $this->actingAs($captain, 'team')
            ->patch("/team/roster/{$otherMember->id}/toggle")->assertForbidden();

        $this->assertSame('Theirs', $otherMember->fresh()->name);
    }

    public function test_lt_cannot_reach_captain_roster(): void
    {
        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/team/roster')->assertForbidden();
    }

    public function test_captain_without_team_sees_empty_state(): void
    {
        $captain = TeamUser::factory()->create(['team_id' => null]);

        $this->actingAs($captain, 'team')->get('/team/roster')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('hasTeam', false)->has('members', 0));
    }
}
