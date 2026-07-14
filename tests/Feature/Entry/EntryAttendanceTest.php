<?php

namespace Tests\Feature\Entry;

use App\Models\Category;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Member;
use App\Models\ScoringRule;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{captain: TeamUser, team: Team, meeting: Meeting, members: \Illuminate\Support\Collection}
     */
    private function scenario(int $memberCount = 5): array
    {
        $season = Season::factory()->active()->create();
        $meeting = Meeting::factory()->open()->create(['season_id' => $season->id]);
        $team = Team::factory()->create();
        $captain = TeamUser::factory()->create(['team_id' => $team->id]);

        $attendance = Category::factory()->create(['input_shape' => Category::ROSTER_FLAT_PENALTY, 'is_active' => true, 'code' => 'ATT']);
        ScoringRule::factory()->create([
            'category_id' => $attendance->id, 'subtype_label' => 'Attendance', 'points' => 0,
            'extra_params' => ['flat' => 300, 'penalty' => -200, 'metric' => 'present'],
        ]);

        $punctuality = Category::factory()->create(['input_shape' => Category::ROSTER_FLAT_PENALTY, 'is_active' => true, 'code' => 'PUN']);
        ScoringRule::factory()->create([
            'category_id' => $punctuality->id, 'subtype_label' => 'Punctuality', 'points' => 0,
            'extra_params' => ['flat' => 100, 'penalty' => -20, 'metric' => 'on_time'],
        ]);

        $meeting->categories()->attach([$attendance->id, $punctuality->id]);
        $members = Member::factory()->count($memberCount)->create(['team_id' => $team->id, 'is_active' => true]);

        return compact('captain', 'team', 'meeting', 'members');
    }

    private function save(TeamUser $captain, Meeting $meeting, array $attendance)
    {
        return $this->actingAs($captain, 'team')->put("/team/submit/{$meeting->id}", [
            'attendance' => $attendance,
        ]);
    }

    private function marks(\Illuminate\Support\Collection $members, int $absent = 0, int $late = 0): array
    {
        return $members->values()->map(fn ($m, $i) => [
            'member_id' => $m->id,
            'is_present' => $i >= $absent,   // first $absent members marked absent
            'is_on_time' => $i >= $late,     // first $late members marked late
        ])->all();
    }

    public function test_full_attendance_awards_flat_300_and_100(): void
    {
        ['captain' => $c, 'team' => $t, 'meeting' => $m, 'members' => $mem] = $this->scenario();

        $this->save($c, $m, $this->marks($mem, absent: 0, late: 0))->assertSessionHasNoErrors();

        // 0 absent → +300, 0 late → +100 = 400
        $this->assertSame(400, MeetingEntry::firstWhere(['team_id' => $t->id, 'meeting_id' => $m->id])->computed_total);
    }

    public function test_two_absent_scores_minus_400(): void
    {
        ['captain' => $c, 'team' => $t, 'meeting' => $m, 'members' => $mem] = $this->scenario();

        // 2 absent → 2×−200 = −400; 0 late → +100. Total = −300.
        $this->save($c, $m, $this->marks($mem, absent: 2, late: 0));

        $entry = MeetingEntry::firstWhere(['team_id' => $t->id, 'meeting_id' => $m->id]);
        $this->assertSame(-300, $entry->computed_total);
        // attendance persisted
        $this->assertSame(5, $entry->attendance()->count());
        $this->assertSame(2, $entry->attendance()->where('is_present', false)->count());
    }

    public function test_three_late_scores_minus_60(): void
    {
        ['captain' => $c, 'team' => $t, 'meeting' => $m, 'members' => $mem] = $this->scenario();

        // 0 absent → +300; 3 late → 3×−20 = −60. Total = 240.
        $this->save($c, $m, $this->marks($mem, absent: 0, late: 3));

        $this->assertSame(240, MeetingEntry::firstWhere(['team_id' => $t->id, 'meeting_id' => $m->id])->computed_total);
    }

    public function test_attendance_cannot_reference_foreign_member(): void
    {
        ['captain' => $c, 'meeting' => $m] = $this->scenario();
        $foreign = Member::factory()->create();

        $this->save($c, $m, [['member_id' => $foreign->id, 'is_present' => true, 'is_on_time' => true]])
            ->assertSessionHasErrors('attendance.0.member_id');
    }

    public function test_resaving_attendance_replaces_marks(): void
    {
        ['captain' => $c, 'team' => $t, 'meeting' => $m, 'members' => $mem] = $this->scenario(3);

        $this->save($c, $m, $this->marks($mem, absent: 1));
        $entry = MeetingEntry::firstWhere(['team_id' => $t->id, 'meeting_id' => $m->id]);
        $this->assertSame(3, $entry->attendance()->count());

        $this->save($c, $m, $this->marks($mem, absent: 0));
        $this->assertSame(3, $entry->fresh()->attendance()->count()); // replaced, not duplicated
        $this->assertSame(0, $entry->fresh()->attendance()->where('is_present', false)->count());
    }
}
