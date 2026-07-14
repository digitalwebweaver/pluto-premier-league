<?php

namespace App\Services;

use App\Models\Category;
use App\Models\EntryLine;
use App\Models\MeetingEntry;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Reporting aggregations (Phase 6A). Reads ONLY approved entries and their
 * frozen `points_snapshot` (never recomputes an approved entry — BR-SCO-003 /
 * BR-RPT-001). Category points come from each snapshot's `categories` array.
 */
class ReportService
{
    /**
     * @return Collection<int, MeetingEntry>
     */
    private function approvedForSeason(Season $season): Collection
    {
        return MeetingEntry::where('status', MeetingEntry::APPROVED)
            ->whereIn('meeting_id', $season->meetings()->pluck('id'))
            ->with(['team:id,name,short_code,crest_color', 'meeting:id,sequence_no'])
            ->get();
    }

    /**
     * A team's approved performance: points by category and by meeting.
     *
     * @return array{by_category: array<int, array<string,mixed>>, by_meeting: array<int, array<string,mixed>>, total: int}
     */
    public function teamReport(Season $season, Team $team): array
    {
        $entries = $this->approvedForSeason($season)->where('team_id', $team->id);

        $byCategory = [];
        foreach ($entries as $entry) {
            foreach ($entry->points_snapshot['categories'] ?? [] as $c) {
                $key = $c['code'];
                $byCategory[$key] ??= ['code' => $c['code'], 'name' => $c['name'], 'points' => 0];
                $byCategory[$key]['points'] += (int) $c['points'];
            }
        }

        $byMeeting = $entries
            ->sortBy(fn ($e) => $e->meeting->sequence_no)
            ->map(fn ($e) => ['sequence_no' => $e->meeting->sequence_no, 'total' => (int) $e->computed_total])
            ->values()->all();

        return [
            'by_category' => collect($byCategory)->sortByDesc('points')->values()->all(),
            'by_meeting' => $byMeeting,
            'total' => (int) $entries->sum('computed_total'),
        ];
    }

    /**
     * Per-category league totals + the leading team in each (FR-RPT-002).
     *
     * @return array<int, array<string,mixed>>
     */
    public function categoryLeaders(Season $season): array
    {
        $entries = $this->approvedForSeason($season);

        // code => ['name'=>, 'total'=>, 'byTeam'=>[teamId=>points], 'teams'=>[id=>team]]
        $cats = [];
        foreach ($entries as $entry) {
            foreach ($entry->points_snapshot['categories'] ?? [] as $c) {
                $code = $c['code'];
                $cats[$code] ??= ['code' => $code, 'name' => $c['name'], 'total' => 0, 'byTeam' => [], 'teams' => []];
                $cats[$code]['total'] += (int) $c['points'];
                $cats[$code]['byTeam'][$entry->team_id] = ($cats[$code]['byTeam'][$entry->team_id] ?? 0) + (int) $c['points'];
                $cats[$code]['teams'][$entry->team_id] = $entry->team;
            }
        }

        return collect($cats)->map(function ($c) {
            $leaderId = collect($c['byTeam'])->sortDesc()->keys()->first();
            $leader = $leaderId ? $c['teams'][$leaderId] : null;

            return [
                'code' => $c['code'],
                'name' => $c['name'],
                'total' => $c['total'],
                'leader' => $leader ? [
                    'name' => $leader->name,
                    'short_code' => $leader->short_code,
                    'crest_color' => $leader->crest_color,
                    'points' => $c['byTeam'][$leaderId],
                ] : null,
            ];
        })->sortByDesc('total')->values()->all();
    }

    /**
     * Individual MVP leaderboard (Phase 6B / FR-RPT-003..007): per-member
     * contribution aggregated from `entry_lines` on APPROVED entries, across
     * all teams. Optionally filtered to one category (by code).
     *
     * @return array<int, array<string,mixed>>
     */
    public function mvpLeaderboard(Season $season, ?string $categoryCode = null, int $limit = 20): array
    {
        $approvedIds = MeetingEntry::where('status', MeetingEntry::APPROVED)
            ->whereIn('meeting_id', $season->meetings()->pluck('id'))
            ->pluck('id');

        $query = EntryLine::whereIn('meeting_entry_id', $approvedIds)
            ->whereNotNull('member_id')
            ->where('computed_points', '>', 0)
            ->with(['member:id,name,team_id,avatar_color', 'member.team:id,name']);

        if ($categoryCode) {
            $query->where('category_id', Category::where('code', $categoryCode)->value('id'));
        }

        $byMember = [];
        foreach ($query->get() as $line) {
            if (! $line->member) {
                continue;
            }
            $id = $line->member_id;
            $byMember[$id] ??= ['member' => $line->member, 'points' => 0, 'count' => 0];
            $byMember[$id]['points'] += (int) $line->computed_points;
            $byMember[$id]['count'] += (int) $line->count;
        }

        return collect($byMember)
            ->sortByDesc('points')
            ->take($limit)
            ->values()
            ->map(fn ($m) => [
                'name' => $m['member']->name,
                'avatar_color' => $m['member']->avatar_color,
                'team' => $m['member']->team?->name,
                'points' => $m['points'],
                'count' => $m['count'],
            ])->all();
    }

    /** Count/amount categories eligible for the member leaderboard filter. */
    public function memberCategories(): array
    {
        return Category::whereIn('input_shape', [Category::COUNT_SUBTYPE, Category::AMOUNT_SUBTYPE])
            ->where('is_active', true)->orderBy('display_order')->get(['code', 'name'])->all();
    }

    /** Teams that have any approved data this season (for the report picker). */
    public function teamsWithData(Season $season): array
    {
        $ids = $this->approvedForSeason($season)->pluck('team_id')->unique();

        return Team::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'short_code', 'crest_color'])->all();
    }
}
