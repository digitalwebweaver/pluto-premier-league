<?php

namespace Tests\Feature\Teams;

use App\Models\LtUser;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_lt_sees_the_teams_list(): void
    {
        Team::factory()->count(3)->create();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt/teams')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('LT/Teams/Index')->has('teams', 3));
    }

    public function test_lt_creates_a_team_with_auto_short_code(): void
    {
        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/teams', [
                'name' => 'Digital Titans',
                'short_code' => '',
                'crest_color' => '#1B2F52',
            ])
            ->assertRedirect(route('lt.teams'));

        $team = Team::where('name', 'Digital Titans')->first();
        $this->assertNotNull($team);
        $this->assertSame('DT', $team->short_code); // auto-derived
        $this->assertTrue($team->is_active);
    }

    public function test_team_name_must_be_unique(): void
    {
        Team::factory()->create(['name' => 'Growth Circle']);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/teams', ['name' => 'Growth Circle', 'crest_color' => '#3F6F8F'])
            ->assertSessionHasErrors('name');
    }

    public function test_crest_color_must_be_hex(): void
    {
        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/teams', ['name' => 'Bad Color', 'crest_color' => 'blue'])
            ->assertSessionHasErrors('crest_color');
    }

    public function test_lt_updates_a_team(): void
    {
        $team = Team::factory()->create(['name' => 'Old', 'crest_color' => '#1B2F52']);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->put("/lt/teams/{$team->id}", [
                'name' => 'New Name',
                'short_code' => 'NN',
                'crest_color' => '#3F8F6B',
            ])->assertRedirect(route('lt.teams'));

        $team->refresh();
        $this->assertSame('New Name', $team->name);
        $this->assertSame('#3F8F6B', $team->crest_color);
    }

    public function test_deactivate_preserves_the_record(): void
    {
        $team = Team::factory()->create(['is_active' => true]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->patch("/lt/teams/{$team->id}/toggle");

        $this->assertFalse($team->fresh()->is_active);
        $this->assertDatabaseHas('teams', ['id' => $team->id]); // not deleted (BR-TEAM-001)
    }

    // --- Captain scoping ---

    public function test_captain_cannot_reach_lt_team_management(): void
    {
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);

        $this->actingAs($captain, 'team')->get('/lt/teams')->assertForbidden();
        $this->actingAs($captain, 'team')->get("/lt/teams/{$team->id}/edit")->assertForbidden();
        $this->actingAs($captain, 'team')
            ->post('/lt/teams', ['name' => 'Hax', 'crest_color' => '#000000'])
            ->assertForbidden();
    }

    public function test_captain_can_edit_only_own_crest_color(): void
    {
        $team = Team::factory()->create(['name' => 'Apex', 'crest_color' => '#1B2F52']);
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);

        $this->actingAs($captain, 'team')->get('/team/profile')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Team/Profile'));

        // Captain updates crest colour — allowed.
        $this->actingAs($captain, 'team')->put('/team/profile', ['crest_color' => '#B5473A'])
            ->assertSessionHasNoErrors();
        $this->assertSame('#B5473A', $team->fresh()->crest_color);

        // Name is NOT editable by the captain — the field is ignored, name unchanged.
        $this->actingAs($captain, 'team')->put('/team/profile', [
            'crest_color' => '#3F8F6B',
            'name' => 'Hacked Name',
        ]);
        $this->assertSame('Apex', $team->fresh()->name);
    }

    public function test_lt_cannot_reach_captain_team_profile(): void
    {
        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/team/profile')->assertForbidden();
    }
}
