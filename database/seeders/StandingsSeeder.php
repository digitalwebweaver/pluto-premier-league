<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\LtUser;
use App\Models\Meeting;
use App\Models\MeetingEntry;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Dev-convenience: seeds approved entries across the closed meetings so the
 * League Table + season summary have realistic data to render. Totals mirror
 * the design mockup. (Not domain-critical — real data flows through the app.)
 */
class StandingsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('meeting_entries')) {
            return;
        }

        $lt = LtUser::where('email', 'leadership@pluto.local')->first();
        $closed = Meeting::where('status', Meeting::CLOSED)->orderBy('sequence_no')->get();
        if ($closed->isEmpty()) {
            return;
        }

        // Team name → season total (from the mockup), spread over the closed meetings.
        $totals = [
            'Digital Titans' => 1860, 'Growth Circle' => 1640, 'Momentum Makers' => 1420,
            'Prime Movers' => 1180, 'Apex Alliance' => 1050, 'Vertex Group' => 920,
            'Summit Squad' => 780, 'Catalyst Crew' => 610,
        ];

        // Categories used to build a plausible per-meeting breakdown for reports.
        $breakdownCats = Category::whereIn('code', ['VIS', 'REF', 'ATT', 'PUN'])->get(['id', 'name', 'code']);
        $weights = ['VIS' => 0.4, 'REF' => 0.2, 'ATT' => 0.2, 'PUN' => 0.2];

        foreach (Team::all() as $team) {
            $seasonTotal = $totals[$team->name] ?? 0;
            if ($seasonTotal === 0) {
                continue;
            }
            $per = intdiv($seasonTotal, $closed->count());
            $remainder = $seasonTotal - ($per * $closed->count());

            foreach ($closed as $i => $meeting) {
                $meetingTotal = $per + ($i === 0 ? $remainder : 0);

                MeetingEntry::updateOrCreate(
                    ['team_id' => $team->id, 'meeting_id' => $meeting->id],
                    [
                        'status' => MeetingEntry::APPROVED,
                        'computed_total' => $meetingTotal,
                        'points_snapshot' => [
                            'total' => $meetingTotal,
                            'categories' => $this->breakdown($breakdownCats, $weights, $meetingTotal),
                        ],
                        'approved_by' => $lt?->id,
                        'approved_at' => now()->subDays(($closed->count() - $i) * 3),
                        'submitted_at' => now()->subDays(($closed->count() - $i) * 3 + 1),
                    ]
                );
            }
        }

        $this->seedMemberLines();
    }

    /**
     * Attribute some member-level lines on Apex Alliance's approved entries so
     * the MVP leaderboard (Phase 6B) has data to show. Uses Apex's seeded roster.
     */
    private function seedMemberLines(): void
    {
        if (! Schema::hasTable('entry_lines')) {
            return;
        }

        $apex = Team::where('name', 'Apex Alliance')->first();
        $vis = Category::where('code', 'VIS')->first();
        $rule = $vis?->scoringRules()->where('subtype_label', 'Hot')->first();
        if (! $apex || ! $vis || ! $rule) {
            return;
        }

        $members = \App\Models\Member::where('team_id', $apex->id)->where('is_active', true)->get();
        $entries = MeetingEntry::where('team_id', $apex->id)->where('status', MeetingEntry::APPROVED)->get();
        if ($members->isEmpty() || $entries->isEmpty()) {
            return;
        }

        foreach ($entries as $e) {
            // Give a rotating trio of members a Hot visitor each (points from the rule).
            foreach ($members->take(3) as $m) {
                \App\Models\EntryLine::firstOrCreate(
                    ['meeting_entry_id' => $e->id, 'category_id' => $vis->id, 'member_id' => $m->id],
                    ['scoring_rule_id' => $rule->id, 'count' => 1, 'computed_points' => $rule->points]
                );
            }
            $members = $members->push($members->shift()); // rotate
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function breakdown($cats, array $weights, int $total): array
    {
        $out = [];
        $running = 0;
        foreach ($cats as $idx => $cat) {
            $pts = $idx === $cats->count() - 1
                ? $total - $running // last takes the remainder
                : (int) round($total * ($weights[$cat->code] ?? 0));
            $running += $pts;
            $out[] = ['id' => $cat->id, 'name' => $cat->name, 'code' => $cat->code, 'points' => $pts];
        }

        return $out;
    }
}
