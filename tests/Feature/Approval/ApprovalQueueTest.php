<?php

namespace Tests\Feature\Approval;

use App\Models\Category;
use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Member;
use App\Models\ScoringRule;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_lists_only_submitted_entries(): void
    {
        MeetingEntry::factory()->submitted()->count(2)->create();
        MeetingEntry::factory()->create(['status' => 'draft']);
        MeetingEntry::factory()->approved()->create();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt/queue')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('LT/Queue/Index')->has('entries', 2));
    }

    public function test_captain_cannot_reach_the_queue(): void
    {
        $entry = MeetingEntry::factory()->submitted()->create();

        $this->actingAs(TeamUser::factory()->create(), 'team')->get('/lt/queue')->assertForbidden();
        $this->actingAs(TeamUser::factory()->create(), 'team')->get("/lt/queue/{$entry->id}")->assertForbidden();
    }

    public function test_review_shows_server_computed_detail(): void
    {
        $meeting = Meeting::factory()->open()->create();
        $team = Team::factory()->create();
        $vis = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE]);
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $meeting->categories()->attach($vis->id);
        $member = Member::factory()->create(['team_id' => $team->id]);

        $entry = MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $meeting->id]);
        $entry->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 2]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get("/lt/queue/{$entry->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('LT/Queue/Review')
                ->where('entry.computed_total', 600) // recomputed authoritatively
                ->has('lines', 1)
            );
    }

    public function test_pending_count_is_shared_for_lt(): void
    {
        MeetingEntry::factory()->submitted()->count(3)->create();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt')
            ->assertInertia(fn ($p) => $p->where('pendingApprovals', 3));
    }
}
