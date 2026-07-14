<?php

namespace Tests\Feature\Approval;

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
use Tests\TestCase;

class UnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_lists_approved_entries_newest_first(): void
    {
        MeetingEntry::factory()->approved()->create(['approved_at' => now()->subDay()]);
        MeetingEntry::factory()->approved()->create(['approved_at' => now()]);
        MeetingEntry::factory()->submitted()->create(); // excluded

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->get('/lt/recent')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('LT/Recent/Index')->has('entries', 2));
    }

    public function test_lt_unlocks_an_approved_entry_back_to_submitted(): void
    {
        $entry = MeetingEntry::factory()->approved()->create([
            'approved_by' => LtUser::factory()->create()->id,
            'points_snapshot' => ['total' => 300, 'categories' => []],
        ]);

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post("/lt/recent/{$entry->id}/unlock")
            ->assertRedirect(route('lt.recent'));

        $fresh = $entry->fresh();
        $this->assertSame(MeetingEntry::SUBMITTED, $fresh->status);
        $this->assertNull($fresh->approved_by);
        $this->assertNull($fresh->points_snapshot);

        // It reappears in the queue.
        $this->actingAs(LtUser::factory()->create(), 'lt')->get('/lt/queue')
            ->assertInertia(fn ($p) => $p->has('entries', 1));
    }

    public function test_captain_cannot_unlock(): void
    {
        $entry = MeetingEntry::factory()->approved()->create();

        $this->actingAs(TeamUser::factory()->create(), 'team')
            ->post("/lt/recent/{$entry->id}/unlock")->assertForbidden();
    }

    public function test_stale_version_approval_is_prevented(): void
    {
        // Build a real submitted entry.
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->open()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $vis = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE]);
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $meeting->categories()->attach($vis->id);
        $member = Member::factory()->create(['team_id' => $team->id]);
        $entry = MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $meeting->id]);
        $entry->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 1]);

        // LT approves with a STALE version token (entry has since changed).
        $staleVersion = $entry->updated_at->copy()->subMinute()->toIso8601String();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post("/lt/queue/{$entry->id}/approve", ['version' => $staleVersion])
            ->assertRedirect(route('lt.queue.review', $entry))
            ->assertSessionHas('error');

        // Not approved.
        $this->assertSame(MeetingEntry::SUBMITTED, $entry->fresh()->status);
    }

    public function test_approve_with_current_version_succeeds(): void
    {
        $entry = MeetingEntry::factory()->submitted()->create();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post("/lt/queue/{$entry->id}/approve", ['version' => $entry->updated_at->toIso8601String()])
            ->assertRedirect(route('lt.queue'));

        $this->assertSame(MeetingEntry::APPROVED, $entry->fresh()->status);
    }
}
