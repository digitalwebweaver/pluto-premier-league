<?php

namespace Tests\Feature\Notifications;

use App\Models\Category;
use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Member;
use App\Models\Notification;
use App\Models\ScoringRule;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function submittedEntry(): array
    {
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->open()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $vis = Category::factory()->create(['input_shape' => Category::COUNT_SUBTYPE]);
        $hot = ScoringRule::factory()->create(['category_id' => $vis->id, 'points' => 300]);
        $meeting->categories()->attach($vis->id);
        $member = Member::factory()->create(['team_id' => $team->id]);
        $entry = MeetingEntry::factory()->submitted()->create(['team_id' => $team->id, 'meeting_id' => $meeting->id]);
        $entry->lines()->create(['category_id' => $vis->id, 'scoring_rule_id' => $hot->id, 'member_id' => $member->id, 'count' => 1]);

        return [$entry, $team, $season];
    }

    public function test_approve_notifies_the_team(): void
    {
        [$entry, $team] = $this->submittedEntry();

        app(ApprovalService::class)->approve($entry, LtUser::factory()->create());

        $this->assertDatabaseHas('notifications', ['team_id' => $team->id, 'type' => 'approved']);
    }

    public function test_send_back_notifies_the_team_with_the_note(): void
    {
        [$entry, $team] = $this->submittedEntry();

        app(ApprovalService::class)->sendBack($entry, LtUser::factory()->create(), 'Fix visitor counts.');

        $note = Notification::where('team_id', $team->id)->where('type', 'sent_back')->first();
        $this->assertNotNull($note);
        $this->assertSame('Fix visitor counts.', $note->payload['note']);
    }

    public function test_creating_a_meeting_broadcasts_to_active_teams(): void
    {
        $season = Season::factory()->active()->create();
        $t1 = Team::factory()->create();
        $t2 = Team::factory()->create();
        Team::factory()->inactive()->create(); // excluded

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/meetings', ['meeting_date' => '2026-10-01']);

        $this->assertSame(2, Notification::where('type', 'new_meeting')->count());
    }

    public function test_lt_announcement_broadcasts_to_active_teams(): void
    {
        Team::factory()->count(3)->create();
        Team::factory()->inactive()->create();

        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/announcements', ['body' => 'Welcome to Season 4!'])
            ->assertRedirect();

        $this->assertDatabaseHas('announcements', ['body' => 'Welcome to Season 4!']);
        $this->assertSame(3, Notification::where('type', 'announcement')->count());
    }

    public function test_announcement_requires_a_body(): void
    {
        $this->actingAs(LtUser::factory()->create(), 'lt')
            ->post('/lt/announcements', ['body' => ''])->assertSessionHasErrors('body');
    }

    public function test_captain_sees_own_notifications_and_they_mark_read(): void
    {
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);
        Notification::create(['team_id' => $team->id, 'type' => 'approved', 'payload' => ['meeting' => 1, 'total' => 300]]);
        Notification::create(['team_id' => Team::factory()->create()->id, 'type' => 'approved', 'payload' => []]); // other team

        $this->actingAs($captain, 'team')->get('/team/notifications')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Team/Notifications')->has('notifications', 1)); // own only

        // Marked read after viewing.
        $this->assertSame(0, Notification::forTeam($team->id)->unread()->count());
    }

    public function test_announcements_are_lt_only(): void
    {
        $this->actingAs(TeamUser::factory()->create(), 'team')->get('/lt/announcements')->assertForbidden();
    }
}
