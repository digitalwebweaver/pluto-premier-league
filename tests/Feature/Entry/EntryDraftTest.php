<?php

namespace Tests\Feature\Entry;

use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryDraftTest extends TestCase
{
    use RefreshDatabase;

    private function setup_captain_open_meeting(): array
    {
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->open()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);

        return [$captain, $team, $meeting];
    }

    public function test_submit_list_renders_season_meetings(): void
    {
        [$captain, , ] = $this->setup_captain_open_meeting();

        $this->actingAs($captain, 'team')->get('/team/submit')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Team/Submit')->has('meetings', 1));
    }

    public function test_opening_an_open_meeting_creates_a_single_draft(): void
    {
        [$captain, $team, $meeting] = $this->setup_captain_open_meeting();

        // First open → creates the draft.
        $this->actingAs($captain, 'team')->get("/team/submit/{$meeting->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Team/Scorecard'));

        $this->assertDatabaseHas('meeting_entries', [
            'team_id' => $team->id,
            'meeting_id' => $meeting->id,
            'status' => MeetingEntry::DRAFT,
        ]);

        // Re-opening does NOT create a duplicate (FR-ENT-013).
        $this->actingAs($captain, 'team')->get("/team/submit/{$meeting->id}")->assertOk();
        $this->assertSame(1, MeetingEntry::where('team_id', $team->id)->where('meeting_id', $meeting->id)->count());
    }

    public function test_cannot_start_a_new_entry_for_a_closed_meeting(): void
    {
        $season = Season::factory()->active()->create();
        $closed = Meeting::factory()->closed()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);

        $this->actingAs($captain, 'team')->get("/team/submit/{$closed->id}")
            ->assertRedirect(route('team.submit'));

        $this->assertDatabaseMissing('meeting_entries', ['meeting_id' => $closed->id]);
    }

    public function test_existing_entry_loads_even_if_meeting_later_closed(): void
    {
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->closed()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);
        // An entry already exists (was started while open).
        MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $meeting->id]);

        $this->actingAs($captain, 'team')->get("/team/submit/{$meeting->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('entry.editable', false)); // closed → read-only
    }

    public function test_entry_is_scoped_to_own_team(): void
    {
        // Draft belongs to a DIFFERENT team; opening the meeting as this captain
        // must create a fresh draft for THIS team, not load the other team's.
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->open()->create(['season_id' => $season->id]);
        $otherTeam = Team::factory()->create();
        MeetingEntry::factory()->create(['team_id' => $otherTeam->id, 'meeting_id' => $meeting->id]);

        $myTeam = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $myTeam->id]);

        $this->actingAs($captain, 'team')->get("/team/submit/{$meeting->id}")->assertOk();

        $this->assertSame(1, MeetingEntry::where('team_id', $myTeam->id)->count());
        $this->assertSame(2, MeetingEntry::where('meeting_id', $meeting->id)->count()); // one per team
    }

    public function test_lt_cannot_reach_captain_entry(): void
    {
        [, , $meeting] = $this->setup_captain_open_meeting();

        $this->actingAs(LtUser::factory()->create(), 'lt')->get('/team/submit')->assertForbidden();
        $this->actingAs(LtUser::factory()->create(), 'lt')->get("/team/submit/{$meeting->id}")->assertForbidden();
    }

    public function test_unique_constraint_prevents_duplicate_entries(): void
    {
        $team = Team::factory()->create();
        $meeting = Meeting::factory()->create();
        MeetingEntry::factory()->create(['team_id' => $team->id, 'meeting_id' => $meeting->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        MeetingEntry::factory()->create(['team_id' => $team->id, 'meeting_id' => $meeting->id]);
    }
}
