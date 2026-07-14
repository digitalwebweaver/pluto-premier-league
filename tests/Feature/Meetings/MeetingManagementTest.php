<?php

namespace Tests\Feature\Meetings;

use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\Season;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function activeSeason(): Season
    {
        return Season::factory()->active()->create();
    }

    public function test_meetings_list_renders_for_lt(): void
    {
        $season = $this->activeSeason();
        Meeting::factory()->count(3)->sequence(fn ($s) => ['sequence_no' => $s->index + 1])
            ->create(['season_id' => $season->id]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt/meetings')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('LT/Meetings/Index')->has('meetings', 3));
    }

    public function test_lt_creates_a_meeting_with_auto_sequence(): void
    {
        $season = $this->activeSeason();
        Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 1]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/meetings', ['meeting_date' => '2026-09-23'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('meetings', [
            'season_id' => $season->id,
            'sequence_no' => 2, // auto next
            'status' => Meeting::SCHEDULED,
        ]);
    }

    public function test_new_meeting_starts_scheduled_then_toggles_open_and_closed(): void
    {
        $season = $this->activeSeason();
        $meeting = Meeting::factory()->create(['season_id' => $season->id, 'status' => Meeting::SCHEDULED]);
        $lt = LtUser::factory()->create();

        $this->assertFalse($meeting->isOpen());

        $this->actingAs($lt, 'lt')->patch("/lt/meetings/{$meeting->id}/toggle");
        $this->assertSame(Meeting::OPEN, $meeting->fresh()->status);

        $this->actingAs($lt, 'lt')->patch("/lt/meetings/{$meeting->id}/toggle");
        $this->assertSame(Meeting::CLOSED, $meeting->fresh()->status);
    }

    public function test_sequence_is_unique_within_a_season(): void
    {
        $season = $this->activeSeason();
        Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 3]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/meetings', ['meeting_date' => '2026-09-01', 'sequence_no' => 3])
            ->assertSessionHasErrors('sequence_no');
    }

    public function test_only_one_active_season_is_returned_as_current(): void
    {
        Season::factory()->create(['is_active' => false]);
        $active = Season::factory()->active()->create();

        $this->assertTrue(Season::current()->is($active));
    }

    public function test_open_scope_filters_meetings(): void
    {
        $season = $this->activeSeason();
        Meeting::factory()->open()->create(['season_id' => $season->id, 'sequence_no' => 1]);
        Meeting::factory()->closed()->create(['season_id' => $season->id, 'sequence_no' => 2]);
        Meeting::factory()->create(['season_id' => $season->id, 'sequence_no' => 3]); // scheduled

        $this->assertSame(1, Meeting::open()->count());
    }

    // --- Guard isolation ---

    public function test_captain_cannot_reach_meeting_management(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->get('/lt/meetings')->assertForbidden();

        $season = $this->activeSeason();
        $meeting = Meeting::factory()->create(['season_id' => $season->id]);
        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->patch("/lt/meetings/{$meeting->id}/toggle")->assertForbidden();
    }
}
